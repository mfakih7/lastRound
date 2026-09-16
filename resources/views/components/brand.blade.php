@props([
    'theme' => 'dark',
    'size' => 'md',
])

@php
    $appName = app_name();
    $logoUrl = app_logo_url();
    $markSize = $size === 'lg' ? 'h-12 w-12 text-xl' : ($size === 'sm' ? 'h-8 w-8 text-sm' : 'h-9 w-9 text-base');
    $wordSize = $size === 'lg' ? 'text-3xl' : ($size === 'sm' ? 'text-lg' : 'text-xl');
    $isDefaultName = strtolower(str_replace(' ', '', $appName)) === 'lastround';
@endphp

<span {{ $attributes->class(['inline-flex items-center gap-3']) }}>
    @if ($logoUrl)
        <img src="{{ $logoUrl }}" alt="{{ $appName }}" class="{{ $size === 'lg' ? 'h-12 max-w-[8rem]' : ($size === 'sm' ? 'h-8 max-w-[5.5rem]' : 'h-9 max-w-[6.5rem]') }} w-auto object-contain">
    @else
        <span class="flex {{ $markSize }} items-center justify-center rounded-lg bg-brand font-display font-bold tracking-wide text-white">LR</span>
    @endif
    <span @class(['font-display tracking-wide', $wordSize, 'text-white' => $theme === 'dark', 'text-ink' => $theme === 'light'])>
        @if ($isDefaultName)
            LAST<span class="text-brand">ROUND</span>
        @else
            {{ $appName }}
        @endif
    </span>
</span>
