@props([
    'href' => null,
    'danger' => false,
    'disabled' => false,
])

@php
    $classes = 'menu-item';
    if ($danger) {
        $classes .= ' menu-item-danger';
    }
    if ($disabled) {
        $classes .= ' menu-item-disabled';
    }
@endphp

@if ($href && ! $disabled)
    <a href="{{ $href }}" {{ $attributes->merge(['class' => $classes]) }}>
        {{ $slot }}
    </a>
@else
    <div {{ $attributes->merge(['class' => $classes]) }}>
        {{ $slot }}
    </div>
@endif
