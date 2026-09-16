<?php

namespace App\Queries;

use App\Enums\ClientPackageStatus;
use App\Enums\ClientStatus;
use App\Models\Client;
use App\Support\AdminListing;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

class ClientIndexQuery
{
    public const SORTABLE = ['full_name', 'created_at', 'remaining_sessions'];

    public function __construct(
        protected Request $request,
        protected int $threshold = 2,
    ) {}

    public function paginate(): LengthAwarePaginator
    {
        $query = Client::query()
            ->with([
                'preferredCoach:id,name,role',
                'currentPackage',
            ])
            ->withCount(['clientPackages', 'trainingSessions'])
            ->withExists([
                'clientPackages as has_completed_package' => fn (Builder $packageQuery) => $packageQuery
                    ->where('status', ClientPackageStatus::Completed),
            ]);

        $this->applySearch($query);
        $this->applyFilters($query);
        $this->applySort($query);

        return $query
            ->paginate(AdminListing::perPage($this->request))
            ->withQueryString();
    }

    protected function applySearch(Builder $query): void
    {
        $query->search($this->request->string('search')->toString());
    }

    protected function applyFilters(Builder $query): void
    {
        $status = $this->request->string('status')->toString();

        if ($status === ClientStatus::Active->value || $status === ClientStatus::Inactive->value) {
            $query->where('status', $status);
        }

        if ($this->request->filled('preferred_coach_id')) {
            $query->where('preferred_coach_id', $this->request->integer('preferred_coach_id'));
        }

        if ($this->request->filled('package_id')) {
            $packageId = $this->request->integer('package_id');
            $query->whereHas('currentPackage', fn (Builder $packageQuery) => $packageQuery->where('package_id', $packageId));
        }

        match ($this->request->string('balance')->toString()) {
            'healthy' => $query->whereHas('currentPackage', function (Builder $packageQuery) {
                $packageQuery->whereRaw('(purchased_sessions - used_sessions) > ?', [$this->threshold]);
            }),
            'low' => $query->whereHas('currentPackage', function (Builder $packageQuery) {
                $packageQuery
                    ->whereRaw('(purchased_sessions - used_sessions) > 0')
                    ->whereRaw('(purchased_sessions - used_sessions) <= ?', [$this->threshold]);
            }),
            'recharge' => $query->rechargeRequired(),
            'none' => $query->withoutPackage(),
            'attention' => $query->needingAttention($this->threshold),
            default => null,
        };
    }

    protected function applySort(Builder $query): void
    {
        $column = $this->request->string('sort')->toString();
        $direction = strtolower($this->request->string('direction')->toString()) === 'asc' ? 'asc' : 'desc';

        if (! in_array($column, self::SORTABLE, true)) {
            $query->orderByDesc('created_at')->orderByDesc('id');

            return;
        }

        if ($column === 'remaining_sessions') {
            $query->orderByRaw(
                '(
                    select (purchased_sessions - used_sessions)
                    from client_packages
                    where client_packages.client_id = clients.id
                      and client_packages.status = ?
                    order by client_packages.id desc
                    limit 1
                ) '.$direction,
                [ClientPackageStatus::Active->value],
            )->orderBy('clients.id', $direction);

            return;
        }

        $query->orderBy($column, $direction);
    }
}
