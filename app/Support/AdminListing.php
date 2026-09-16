<?php

namespace App\Support;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

class AdminListing
{
    public const PER_PAGE_OPTIONS = [10, 20, 50];

    public static function perPage(Request $request, int $default = 20): int
    {
        $perPage = $request->integer('per_page', $default);

        return in_array($perPage, self::PER_PAGE_OPTIONS, true) ? $perPage : $default;
    }

    /**
     * Apply a safe sort using an allow-list of columns.
     *
     * @param  array<int, string>  $allowed
     */
    public static function applySort(
        Builder $query,
        Request $request,
        array $allowed,
        string $defaultColumn,
        string $defaultDirection = 'desc',
    ): Builder {
        $column = $request->string('sort')->toString();
        $direction = strtolower($request->string('direction')->toString()) === 'asc' ? 'asc' : 'desc';

        if (! in_array($column, $allowed, true)) {
            $column = $defaultColumn;
            $direction = $defaultDirection;
        }

        return $query->orderBy($column, $direction);
    }

    /**
     * Query parameters that listing pages should preserve while paginating.
     *
     * @param  array<int, string>  $except
     * @return array<string, mixed>
     */
    public static function preservedQuery(Request $request, array $except = ['page']): array
    {
        return $request->except($except);
    }
}
