@props([
    'padding' => true,
])

<div {{ $attributes->merge(['class' => 'card']) }}>
    <div @class(['p-5 sm:p-6' => $padding])>
        {{ $slot }}
    </div>
</div>
