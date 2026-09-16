@props([
    'status',
])

@php
    $icon = match ($status->value) {
        'healthy' => 'check',
        'low' => 'warning',
        'recharge' => 'credit-card',
        default => 'inbox',
    };
@endphp

<x-badge :tone="$status->tone()">
    <x-icon :name="$icon" class="h-3 w-3" />
    {{ $status->label() }}
</x-badge>
