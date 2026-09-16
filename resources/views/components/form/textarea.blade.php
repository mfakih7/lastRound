@props([
    'name',
    'label' => null,
    'value' => null,
])

<div>
    @if ($label)
        <x-form.label :for="$name" :required="$attributes->has('required')">{{ $label }}</x-form.label>
    @endif
    <textarea
        name="{{ $name }}"
        id="{{ $name }}"
        @class(['form-textarea', 'form-input-error' => $errors->has($name)])
        {{ $attributes }}
    >{{ old($name, $value) }}</textarea>
    <x-form.error :name="$name" />
</div>
