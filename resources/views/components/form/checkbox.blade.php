@props([
    'name',
    'label',
    'value' => '1',
    'checked' => false,
])

<label class="flex items-center gap-2 text-sm text-zinc-700">
    <input
        type="checkbox"
        name="{{ $name }}"
        id="{{ $name }}"
        value="{{ $value }}"
        @checked(old($name, $checked))
        {{ $attributes->merge(['class' => 'form-checkbox']) }}
    >
    <span>{{ $label }}</span>
</label>
