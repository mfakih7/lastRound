@props([
    'label',
    'value',
    'hint' => null,
    'url' => null,
])

<div class="stat-card">
    <p class="ui-label">{{ $label }}</p>
    <p class="stat-value">{{ $value }}</p>
    @if ($hint)
        <p class="mt-2 text-[13px] text-muted">{{ $hint }}</p>
    @endif
    @if ($url)
        <a href="{{ $url }}" class="mt-4 inline-flex items-center gap-1 text-[13px] font-semibold text-brand hover:text-brand-dark">
            View
            <x-icon name="arrow-up-right" class="h-3.5 w-3.5" />
        </a>
    @endif
</div>
