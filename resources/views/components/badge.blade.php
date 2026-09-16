@props([
    'tone' => 'neutral',
])

@php
    $tones = [
        'neutral' => 'badge-neutral',
        'success' => 'badge-success',
        'warning' => 'badge-warning',
        'danger' => 'badge-danger',
        'info' => 'badge-info',
    ];
@endphp

<span {{ $attributes->merge(['class' => 'badge '.($tones[$tone] ?? $tones['neutral'])]) }}>
    {{ $slot }}
</span>
