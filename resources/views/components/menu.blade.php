@props([
    'label' => 'More actions',
    'align' => 'right',
])

<div {{ $attributes->class(['relative inline-flex']) }} data-menu>
    <button
        type="button"
        class="btn-icon"
        data-menu-button
        aria-haspopup="true"
        aria-expanded="false"
        aria-label="{{ $label }}"
        title="{{ $label }}"
    >
        <x-icon name="dots" class="h-4 w-4" />
        <span class="sr-only">{{ $label }}</span>
    </button>
    <div @class(['menu-panel hidden', 'right-0' => $align === 'right', 'left-0' => $align === 'left']) data-menu-panel>
        {{ $slot }}
    </div>
</div>
