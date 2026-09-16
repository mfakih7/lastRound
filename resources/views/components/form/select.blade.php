@props([
    'name',
    'label' => null,
])

<div>
    @if ($label)
        <x-form.label :for="$name" :required="$attributes->has('required')">{{ $label }}</x-form.label>
    @endif
    <select
        name="{{ $name }}"
        id="{{ $name }}"
        @class(['form-select', 'form-input-error' => $errors->has($name)])
        {{ $attributes }}
    >
        {{ $slot }}
    </select>
    <x-form.error :name="$name" />
</div>
