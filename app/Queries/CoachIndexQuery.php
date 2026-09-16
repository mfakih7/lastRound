<?php

namespace App\Queries;

use App\Models\User;
use App\Support\AdminListing;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

class CoachIndexQuery
{
    public const SORTABLE = ['name', 'username', 'created_at'];

    public function __construct(protected Request $request) {}

    public function paginate(): LengthAwarePaginator
    {
        $query = User::query()
            ->with('coachProfile')
            ->withCoachProfile()
            ->withCount([
                'preferredClients',
                'trainingSessions',
                'trainingSessions as upcoming_sessions_count' => fn (Builder $sessions) => $sessions->upcoming(),
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
        $term = trim($this->request->string('search')->toString());

        if ($term === '') {
            return;
        }

        $like = '%'.addcslashes(mb_strtolower($term), '%_\\').'%';

        $query->where(function (Builder $inner) use ($like) {
            $inner->whereRaw('lower(name) like ?', [$like])
                ->orWhereRaw('lower(username) like ?', [$like])
                ->orWhereRaw("lower(coalesce(email, '')) like ?", [$like])
                ->orWhereHas('coachProfile', function (Builder $profile) use ($like) {
                    $profile->whereRaw("lower(coalesce(phone, '')) like ?", [$like]);
                });
        });
    }

    protected function applyFilters(Builder $query): void
    {
        $status = $this->request->string('status')->toString();

        if ($status === 'active') {
            $query->where('is_active', true);
        }

        if ($status === 'inactive') {
            $query->where('is_active', false);
        }

        $availability = $this->request->string('availability')->toString();

        if ($availability === 'available') {
            $query->whereHas('coachProfile', fn (Builder $profile) => $profile->where('is_available', true));
        }

        if ($availability === 'unavailable') {
            $query->whereHas('coachProfile', fn (Builder $profile) => $profile->where('is_available', false));
        }

        $type = $this->request->string('type')->toString();

        if ($type === 'admin') {
            $query->admins();
        }

        if ($type === 'coach') {
            $query->coaches();
        }
    }

    protected function applySort(Builder $query): void
    {
        $column = $this->request->string('sort')->toString();
        $direction = strtolower($this->request->string('direction')->toString()) === 'asc' ? 'asc' : 'desc';

        if (! in_array($column, self::SORTABLE, true)) {
            $query->orderBy('name');

            return;
        }

        $query->orderBy($column, $direction);
    }
}
