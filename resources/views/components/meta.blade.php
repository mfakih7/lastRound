@props([
    'label',
])

<div {{ $attributes }}>
    <dt class="ui-label">{{ $label }}</dt>
    <dd class="ui-value">{{ $slot }}</dd>
</div>
