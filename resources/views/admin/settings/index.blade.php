<x-layouts.app
    title="Settings"
    :breadcrumbs="[
        ['label' => 'Dashboard', 'url' => route('admin.dashboard')],
        ['label' => 'Settings'],
    ]"
>
    <div class="grid gap-6 xl:grid-cols-3">
        <x-card class="xl:col-span-2">
            <x-form.section title="Application settings" description="Identity, contact details, and operational defaults used across {{ app_name() }}.">
                <form method="POST" action="{{ route('admin.settings.update') }}" enctype="multipart/form-data" class="space-y-5" @if ($settings['logo_url']) onsubmit="if (this.logo.files.length) { return confirm('Replace the current logo?'); }" @endif>
                    @csrf
                    @method('PUT')

                    <x-form.input name="app_name" label="Application name" :value="old('app_name', $settings['app_name'])" required maxlength="80" />

                    <div>
                        <x-form.label for="logo">Logo</x-form.label>
                        @if ($settings['logo_url'])
                            <div class="mb-3 flex items-center gap-4">
                                <img src="{{ $settings['logo_url'] }}" alt="{{ $settings['app_name'] }}" class="h-12 max-w-[10rem] object-contain">
                                <label class="flex items-center gap-2 text-sm text-zinc-600">
                                    <input type="checkbox" name="remove_logo" value="1" class="form-checkbox">
                                    Remove current logo
                                </label>
                            </div>
                        @endif
                        <input
                            id="logo"
                            type="file"
                            name="logo"
                            accept="image/jpeg,image/png,image/webp"
                            class="form-input"
                        >
                        <p class="form-help">JPEG, PNG, or WebP. Maximum 2 MB. A generated filename is stored; the original name is not used.</p>
                        <x-form.error name="logo" />
                    </div>

                    <div class="grid gap-4 sm:grid-cols-2">
                        <x-form.input name="phone" label="Business phone" :value="old('phone', $settings['phone'])" maxlength="40" />
                        <x-form.input name="email" type="email" label="Business email" :value="old('email', $settings['email'])" maxlength="191" />
                    </div>

                    <x-form.textarea name="address" label="Address" :value="old('address', $settings['address'])" rows="2" />

                    <div class="grid gap-4 sm:grid-cols-3">
                        <x-form.select name="currency" label="Currency" required>
                            <option value="USD" @selected(old('currency', $settings['currency']) === 'USD')>USD ($)</option>
                        </x-form.select>

                        <x-form.input
                            name="default_session_duration"
                            type="number"
                            label="Default session duration (minutes)"
                            :value="old('default_session_duration', $settings['default_session_duration'])"
                            required
                            min="15"
                            max="180"
                        />

                        <x-form.input
                            name="low_session_warning_threshold"
                            type="number"
                            label="Low-session warning threshold"
                            :value="old('low_session_warning_threshold', $settings['low_session_warning_threshold'])"
                            required
                            min="1"
                            max="20"
                        />
                    </div>

                    <p class="text-xs text-muted">
                        Remaining sessions at or below the threshold show Low Sessions. Remaining 0 shows Recharge Required.
                        Default duration only suggests an end time when scheduling; sessions can still be any valid length.
                    </p>

                    <div class="form-actions">
                        <button type="submit" class="btn btn-primary">Save application settings</button>
                    </div>
                </form>
            </x-form.section>
        </x-card>

        <div class="space-y-6">
            <x-card>
                <x-form.section title="Admin account" description="Your Head Coach login. This cannot change your Admin role.">
                    <form method="POST" action="{{ route('admin.settings.account') }}" class="space-y-4">
                        @csrf
                        @method('PUT')

                        <x-form.input name="name" label="Name" :value="old('name', $admin->name)" required maxlength="191" />
                        <x-form.input name="username" label="Username" :value="old('username', $admin->username)" required maxlength="30" autocomplete="username" />
                        <x-form.input name="email" type="email" label="Email" :value="old('email', $admin->email)" maxlength="191" autocomplete="email" />

                        <p class="rounded-[10px] bg-zinc-50 px-3 py-2 text-xs text-muted">Role: Head Coach / Admin. This cannot be changed here.</p>

                        <div class="form-actions">
                            <button type="submit" class="btn btn-primary">Save account</button>
                        </div>
                    </form>
                </x-form.section>
            </x-card>

            <x-card>
                <x-form.section title="Change password" description="Enter your current password, then a new one.">
                    <form method="POST" action="{{ route('admin.settings.password') }}" class="space-y-4">
                        @csrf
                        @method('PUT')

                        <x-form.input name="current_password" type="password" label="Current password" required autocomplete="current-password" />
                        <x-form.input name="password" type="password" label="New password" required autocomplete="new-password" />
                        <x-form.input name="password_confirmation" type="password" label="Confirm new password" required autocomplete="new-password" />

                        <div class="form-actions">
                            <button type="submit" class="btn btn-primary">Change password</button>
                        </div>
                    </form>
                </x-form.section>
            </x-card>
        </div>
    </div>
</x-layouts.app>
