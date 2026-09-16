<x-layouts.app
    title="Change password · {{ $coach->name }}"
    :breadcrumbs="[
        ['label' => 'Dashboard', 'url' => route('admin.dashboard')],
        ['label' => 'Coaches', 'url' => route('admin.coaches.index')],
        ['label' => $coach->name, 'url' => route('admin.coaches.show', $coach)],
        ['label' => 'Change password'],
    ]"
>
    <x-card class="max-w-xl">
        <x-form.section title="Change password" description="Set a new password for {{ $coach->name }}. The current password is never displayed.">
            <form method="POST" action="{{ route('admin.coaches.password.update', $coach) }}" class="space-y-4">
                @csrf
                @method('PUT')
                <x-form.input name="password" type="password" label="New password" required autocomplete="new-password" />
                <x-form.input name="password_confirmation" type="password" label="Confirm new password" required autocomplete="new-password" />
                <div class="form-actions">
                    <a href="{{ route('admin.coaches.show', $coach) }}" class="btn btn-secondary">Cancel</a>
                    <button type="submit" class="btn btn-primary">Update password</button>
                </div>
            </form>
        </x-form.section>
    </x-card>
</x-layouts.app>
