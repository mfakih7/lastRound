@props([
    'for',
    'value' => null,
    'required' => false,
])

<label for="{{ $for }}" {{ $attributes->merge(['class' => 'form-label']) }}>
    {{ $value ?? $slot }}
    @if ($required)
        <span class="text-brand" aria-hidden="true">*</span>
    @endif
</label>
