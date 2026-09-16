@props([
    'column',
    'label',
])

@php
    $current = request('sort');
    $direction = request('direction', 'desc');
    $isActive = $current === $column;
    $nextDirection = $isActive && $direction === 'asc' ? 'desc' : 'asc';
    $url = request()->fullUrlWithQuery([
        'sort' => $column,
        'direction' => $nextDirection,
        'page' => null,
    ]);
@endphp

<a href="{{ $url }}" class="inline-flex items-center gap-1 hover:text-ink">
    <span>{{ $label }}</span>
    @if ($isActive)
        <x-icon :name="$direction === 'asc' ? 'chevron-up' : 'chevron-down'" class="h-3.5 w-3.5" />
    @endif
</a>
