@props([
    'title',
    'description' => null,
])

<section {{ $attributes->class(['space-y-4']) }}>
    <div>
        <h2 class="ui-section-title">{{ $title }}</h2>
        @if ($description)
            <p class="ui-section-desc">{{ $description }}</p>
        @endif
    </div>
    {{ $slot }}
</section>
