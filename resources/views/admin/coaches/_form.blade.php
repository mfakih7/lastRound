@php
    $coach ??= null;
    $isEdit = $coach !== null;
@endphp

<div class="space-y-8">
    <x-form.section title="Account information" description="Login credentials for this coach. Passwords are stored hashed and are never displayed.">
        <div class="grid gap-4 sm:grid-cols-2">
            <x-form.input name="name" label="Name" :value="$coach?->name" required />
            <div>
                <x-form.input name="username" label="Username" :value="$coach?->username" required autocomplete="username" />
                <p class="form-help">Lowercase letters, numbers, dots, and underscores. 3–30 characters.</p>
            </div>
            <x-form.input name="email" type="email" label="Email" :value="$coach?->email" autocomplete="email" />

            @if ($isEdit && $coach->isAdmin())
                <div>
                    <p class="form-label">Account type</p>
                    <p class="rounded-[10px] border border-line bg-zinc-50 px-3.5 py-2.5 text-sm font-medium">Head Coach / Admin</p>
                    <p class="form-help">Role cannot be changed from this form. The admin account stays active.</p>
                </div>
            @else
                <div class="flex items-end pb-1">
                    <x-form.checkbox name="is_active" label="Active account" :checked="old('is_active', $coach?->is_active ?? true)" />
                </div>
            @endif

            <x-form.input
                name="password"
                type="password"
                :label="$isEdit ? 'New password' : 'Password'"
                :required="! $isEdit"
                autocomplete="new-password"
            />
            <x-form.input
                name="password_confirmation"
                type="password"
                :label="$isEdit ? 'Confirm new password' : 'Confirm password'"
                :required="! $isEdit"
                autocomplete="new-password"
            />
            @if ($isEdit)
                <p class="form-help sm:col-span-2">Leave password fields empty to keep the current password.</p>
            @endif
        </div>
    </x-form.section>

    <x-form.section title="Coach information" description="Profile details used when assigning this trainer to clients and sessions.">
        <div class="grid gap-4 sm:grid-cols-2">
            <x-form.input name="phone" label="Phone" :value="$coach?->coachProfile?->phone" />

            <div class="flex items-end pb-1">
                <x-form.checkbox name="is_available" label="Available for new assignments" :checked="old('is_available', $coach?->coachProfile?->is_available ?? true)" />
            </div>

            <div class="sm:col-span-2">
                <x-form.label for="profile_image">Profile image</x-form.label>
                @if ($coach?->profileImageUrl())
                    <div class="mb-3 flex items-center gap-3">
                        <x-avatar :name="$coach->name" :src="$coach->profileImageUrl()" size="lg" />
                        <p class="text-sm text-muted">Upload a new image to replace the current photo.</p>
                    </div>
                @endif
                <input
                    type="file"
                    name="profile_image"
                    id="profile_image"
                    accept="image/jpeg,image/png,image/webp"
                    @class(['form-input', 'form-input-error' => $errors->has('profile_image')])
                >
                <p class="form-help">JPEG, PNG, or WebP. Maximum 2 MB.</p>
                <x-form.error name="profile_image" />
            </div>

            <div class="sm:col-span-2">
                <x-form.textarea name="notes" label="Notes" :value="$coach?->coachProfile?->notes" rows="4" />
            </div>
        </div>
    </x-form.section>
</div>
