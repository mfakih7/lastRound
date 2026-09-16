@props([
    'period',
    'routeName',
    'routeParams' => [],
])

<div class="segmented grid-cols-3">
    @foreach (\App\Support\SchedulePeriod::allowed() as $value)
        <a
            href="{{ route($routeName, array_merge($routeParams, ['period' => $value])) }}"
            @class([
                'segmented-item',
                'segmented-item-active' => $period === $value,
            ])
        >
            {{ \App\Support\SchedulePeriod::label($value) }}
        </a>
    @endforeach
</div>
