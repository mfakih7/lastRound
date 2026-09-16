@php
    $package ??= null;
@endphp

<x-form.section title="Package details">
    <div class="grid gap-4 sm:grid-cols-2">
        <x-form.input name="name" label="Name" :value="$package?->name" required />
        <x-form.input name="sessions_count" type="number" min="1" label="Number of sessions" :value="$package?->sessions_count" required />
        <x-form.input name="price" type="number" step="0.01" min="0" label="Price" :value="$package?->price" required />
        <div class="flex items-end pb-1">
            <x-form.checkbox name="is_active" label="Active" :checked="old('is_active', $package?->is_active ?? true)" />
        </div>
        <div class="sm:col-span-2">
            <x-form.textarea name="description" label="Description" :value="$package?->description" rows="4" />
        </div>
    </div>
</x-form.section>
