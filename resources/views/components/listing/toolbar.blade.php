@props([
    'action' => null,
    'searchName' => 'search',
    'searchPlaceholder' => 'Search...',
    'searchValue' => null,
])

@php
    $action ??= url()->current();
    $searchValue ??= request($searchName);
    $perPage = (int) request('per_page', 20);
@endphp

<div class="card mb-5">
    <form method="GET" action="{{ $action }}" class="p-4 sm:p-5">
        @foreach (['sort', 'direction'] as $keep)
            @if (request()->filled($keep))
                <input type="hidden" name="{{ $keep }}" value="{{ request($keep) }}">
            @endif
        @endforeach

        <div class="flex flex-col gap-3 sm:flex-row sm:items-end">
            <div class="min-w-0 flex-1">
                <label for="listing-search" class="form-label">Search</label>
                <div class="relative">
                    <x-icon name="search" class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-zinc-400" />
                    <input
                        id="listing-search"
                        type="search"
                        name="{{ $searchName }}"
                        value="{{ $searchValue }}"
                        placeholder="{{ $searchPlaceholder }}"
                        class="form-input pl-9"
                    >
                </div>
            </div>
            @if (isset($actions))
                <div class="toolbar-actions flex w-full shrink-0 sm:w-auto">{{ $actions }}</div>
            @endif
        </div>

        @php
            $hasActiveFilters = collect(request()->except(['search', $searchName, 'sort', 'direction', 'page', 'per_page']))
                ->filter(fn ($value) => $value !== null && $value !== '')
                ->isNotEmpty();
        @endphp

        <details class="filter-details mt-4" @open($hasActiveFilters)>
            <summary class="py-1">Filters</summary>
            <div class="mt-3 grid gap-3 sm:grid-cols-2 xl:grid-cols-4 md:mt-0">
                {{ $filters ?? '' }}

                <div>
                    <label for="per_page" class="form-label">Per page</label>
                    <select name="per_page" id="per_page" class="form-select">
                        @foreach (\App\Support\AdminListing::PER_PAGE_OPTIONS as $option)
                            <option value="{{ $option }}" @selected($perPage === $option)>{{ $option }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
        </details>

        <div class="mt-4 flex flex-wrap gap-2">
            <button type="submit" class="btn btn-primary btn-sm min-h-11 sm:min-h-9">Apply filters</button>
            <a href="{{ $action }}" class="btn btn-secondary btn-sm min-h-11 sm:min-h-9">Reset</a>
        </div>
    </form>
</div>
