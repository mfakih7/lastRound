@props([
    'type' => 'info',
])

@php
    $styles = [
        'success' => 'border-emerald-200 bg-emerald-50 text-emerald-900',
        'error' => 'border-red-200 bg-red-50 text-brand',
        'warning' => 'border-amber-200 bg-amber-50 text-amber-950',
        'info' => 'border-line bg-white text-zinc-700',
    ];
    $icons = [
        'success' => 'check',
        'error' => 'ban',
        'warning' => 'warning',
        'info' => 'inbox',
    ];
@endphp

<div {{ $attributes->merge(['class' => 'flex items-start gap-3 rounded-[12px] border px-4 py-3 text-sm '.$styles[$type]]) }} role="alert">
    <x-icon :name="$icons[$type]" class="mt-0.5 h-4 w-4 shrink-0" />
    <div>{{ $slot }}</div>
</div>
