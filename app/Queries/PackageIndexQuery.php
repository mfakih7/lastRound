<?php

namespace App\Queries;

use App\Models\Package;
use App\Support\AdminListing;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

class PackageIndexQuery
{
    public const SORTABLE = ['name', 'sessions_count', 'price', 'created_at', 'purchases_count'];

    public function __construct(protected Request $request) {}

    public function paginate(): LengthAwarePaginator
    {
        $query = Package::query()->withCount('clientPackages as purchases_count');

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

        if ($status === 'active') {
            $query->where('is_active', true);
        }

        if ($status === 'inactive') {
            $query->where('is_active', false);
        }
    }

    protected function applySort(Builder $query): void
    {
        $column = $this->request->string('sort')->toString();
        $direction = strtolower($this->request->string('direction')->toString()) === 'asc' ? 'asc' : 'desc';

        if (! in_array($column, self::SORTABLE, true)) {
            $query->orderByDesc('created_at');

            return;
        }

        $query->orderBy($column, $direction);
    }
}
