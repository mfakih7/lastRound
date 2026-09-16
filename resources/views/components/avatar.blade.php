@props([
    'name',
    'src' => null,
    'size' => 'md',
])

@php
    $initials = collect(preg_split('/\s+/', trim((string) $name)) ?: [])
        ->filter()
        ->take(2)
        ->map(fn ($part) => mb_strtoupper(mb_substr($part, 0, 1)))
        ->implode('');

    $sizeClass = match ($size) {
        'lg' => 'h-16 w-16 text-xl',
        'sm' => 'h-8 w-8 text-xs',
        default => 'h-10 w-10 text-sm',
    };
@endphp

@if ($src)
    <img src="{{ $src }}" alt="" @class(['rounded-full object-cover', $sizeClass])>
@else
    <span @class(['inline-flex items-center justify-center rounded-full bg-ink font-display font-semibold text-white', $sizeClass]) aria-hidden="true">
        {{ $initials !== '' ? $initials : '?' }}
    </span>
@endif
