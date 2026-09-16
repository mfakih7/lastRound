<x-layouts.app
    title="My Profile"
    :breadcrumbs="[
        ['label' => 'My Schedule', 'url' => route('coach.schedule.index')],
        ['label' => 'My Profile'],
    ]"
>
    <div class="mx-auto grid max-w-3xl gap-5">
        <x-card>
            <div class="flex items-start gap-4">
                <x-avatar :name="$coach->name" :src="$coach->profileImageUrl()" size="lg" />
                <div>
                    <h2 class="text-lg font-semibold tracking-tight">{{ $coach->name }}</h2>
                    <p class="mt-1 text-sm text-muted">{{ $coach->accountTypeLabel() }}</p>
                </div>
            </div>

            <dl class="mt-6 grid gap-4 sm:grid-cols-2">
                <x-meta label="Username">{{ $coach->username }}</x-meta>
                <x-meta label="Email">{{ $coach->email ?: '—' }}</x-meta>
                <x-meta label="Phone">{{ $coach->coachProfile?->phone ?: '—' }}</x-meta>
                <div>
                    <dt class="ui-label">Availability</dt>
                    <dd class="mt-1.5">
                        <x-status-badge :status="$coach->coachProfile?->is_available ? 'available' : 'unavailable'" />
                    </dd>
                </div>
            </dl>
        </x-card>

        <x-card>
            <x-form.section title="Change password" description="Enter your current password to set a new one. The current password is never displayed.">
                <form method="POST" action="{{ route('coach.profile.password.update') }}" class="space-y-4">
                    @csrf
                    @method('PUT')
                    <x-form.input name="current_password" type="password" label="Current password" required autocomplete="current-password" />
                    <x-form.input name="password" type="password" label="New password" required autocomplete="new-password" />
                    <x-form.input name="password_confirmation" type="password" label="Confirm new password" required autocomplete="new-password" />
                    <div class="form-actions">
                        <button type="submit" class="btn btn-primary w-full sm:w-auto">Update password</button>
                    </div>
                </form>
            </x-form.section>
        </x-card>
    </div>
</x-layouts.app>
