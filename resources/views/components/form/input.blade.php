@props([
    'name',
    'label' => null,
    'type' => 'text',
    'value' => null,
])

<div>
    @if ($label)
        <x-form.label :for="$name" :required="$attributes->has('required')">{{ $label }}</x-form.label>
    @endif
    <input
        type="{{ $type }}"
        name="{{ $name }}"
        id="{{ $name }}"
        value="{{ $type === 'password' ? '' : old($name, $value) }}"
        @class(['form-input', 'form-input-error' => $errors->has($name)])
        {{ $attributes }}
    >
    <x-form.error :name="$name" />
</div>
