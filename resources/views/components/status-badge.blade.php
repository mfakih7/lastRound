@props(['status'])

@php
    $value = $status instanceof \BackedEnum ? $status->value : (string) $status;
    $label = is_object($status) && method_exists($status, 'label')
        ? $status->label()
        : \Illuminate\Support\Str::headline($value);

    $tone = match ($value) {
        'active', 'done', 'completed', 'available' => 'success',
        'pending' => 'warning',
        'cancelled', 'inactive', 'unavailable' => 'danger',
        default => 'neutral',
    };

    $icon = match ($value) {
        'active', 'done', 'completed', 'available' => 'check',
        'pending' => 'clock',
        'cancelled', 'inactive', 'unavailable' => 'ban',
        default => null,
    };
@endphp

<x-badge :tone="$tone">
    @if ($icon)
        <x-icon :name="$icon" class="h-3 w-3" />
    @endif
    {{ $label }}
</x-badge>
