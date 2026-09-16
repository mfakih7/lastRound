<x-layouts.guest title="Sign in">
    <div class="w-full">
        <div class="mb-8 text-center">
            <div class="mb-4 flex justify-center">
                <x-brand theme="dark" size="lg" />
            </div>
            <p class="mt-2 text-sm text-white/55">Private coaching operations</p>
        </div>

        <div class="rounded-[18px] border border-white/10 bg-white p-6 text-zinc-900 shadow-[0_24px_60px_rgba(0,0,0,0.35)] sm:p-8">
            <h2 class="text-lg font-semibold tracking-tight">Sign in</h2>
            <p class="mb-6 text-sm text-muted">Use the credentials provided by the Head Coach.</p>

            <x-flash />

            <form method="POST" action="{{ route('login.store') }}" class="space-y-4">
                @csrf

                <x-form.input name="username" label="Username" value="{{ old('username') }}" autocomplete="username" required autofocus />

                <x-form.input name="password" type="password" label="Password" autocomplete="current-password" required />

                <x-form.checkbox name="remember" label="Remember me" />

                <button type="submit" class="btn btn-primary w-full">
                    Sign in
                </button>
            </form>
        </div>
    </div>
</x-layouts.guest>
