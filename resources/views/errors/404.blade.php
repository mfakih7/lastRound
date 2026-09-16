<x-layouts.guest title="Page not found">
    <div class="w-full text-center">
        <div class="mb-6 flex justify-center">
            <x-brand theme="dark" />
        </div>
        <p class="ui-kicker text-white/40">404</p>
        <h1 class="mt-2 text-2xl font-semibold tracking-tight">Page not found</h1>
        <p class="mt-3 text-sm text-white/65">This page does not exist or is no longer available.</p>
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
