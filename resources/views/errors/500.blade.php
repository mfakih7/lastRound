<x-layouts.guest title="Something went wrong">
    <div class="w-full text-center">
        <div class="mb-6 flex justify-center">
            <x-brand theme="dark" />
        </div>
        <p class="ui-kicker text-white/40">500</p>
        <h1 class="mt-2 text-2xl font-semibold tracking-tight">Something went wrong</h1>
        <p class="mt-3 text-sm text-white/65">The application could not complete that request. Try again, or return to the dashboard.</p>
        <div class="mt-8">
            @auth
                <a href="{{ \App\Support\Navigation::homeRoute(auth()->user()) }}" class="btn btn-primary">
                    Back to dashboard
                </a>
            @else
                <a href="{{ route('login') }}" class="btn btn-primary">Sign in</a>
            @endauth
        </div>
    </div>
</x-layouts.guest>
