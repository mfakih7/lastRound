<x-layouts.guest title="Session expired">
    <div class="w-full text-center">
        <div class="mb-6 flex justify-center">
            <x-brand theme="dark" />
        </div>
        <p class="ui-kicker text-white/40">419</p>
        <h1 class="mt-2 text-2xl font-semibold tracking-tight">Session expired</h1>
        <p class="mt-3 text-sm text-white/65">Your session timed out. Sign in again to continue.</p>
        <div class="mt-8">
            <a href="{{ route('login') }}" class="btn btn-primary">Sign in</a>
        </div>
    </div>
</x-layouts.guest>
