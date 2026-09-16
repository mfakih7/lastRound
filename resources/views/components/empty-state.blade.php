@props([
    'title',
    'description' => null,
    'icon' => 'inbox',
])

<div {{ $attributes->merge(['class' => 'flex flex-col items-center justify-center px-4 py-10 text-center']) }}>
    <div class="mb-3 flex h-11 w-11 items-center justify-center rounded-full bg-zinc-100 text-zinc-500">
        <x-icon :name="$icon" class="h-5 w-5" />
    </div>
    <h3 class="text-sm font-semibold text-ink">{{ $title }}</h3>
    @if ($description)
        <p class="mt-1.5 max-w-sm text-sm text-muted">{{ $description }}</p>
    @endif
    @if ($slot->isNotEmpty())
        <div class="mt-4">{{ $slot }}</div>
    @endif
</div>
