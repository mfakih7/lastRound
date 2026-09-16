<x-layouts.app title="My Schedule">
    <div class="mx-auto max-w-2xl">
        <p class="ui-kicker">{{ strtoupper(app_name()) }}</p>
        <h2 class="mt-2 text-lg font-semibold tracking-tight">Welcome, {{ auth()->user()->name }}</h2>
        <p class="mt-1 text-sm text-muted">Your sessions for today.</p>

        <x-card class="mt-5" :padding="false">
            <div class="card-header">
                <h3 class="ui-section-title">Today</h3>
            </div>
            <div class="p-4 sm:p-5">
                @include('partials.session-list', [
                    'sessions' => $sessions,
                    'emptyTitle' => 'You have no sessions scheduled today.',
                    'emptyDescription' => 'Tomorrow’s sessions will appear here when they are assigned to you.',
                ])
            </div>
        </x-card>

        <div class="mt-5">
            <a href="{{ route('coach.schedule.index') }}" class="btn btn-primary w-full sm:w-auto">Open full schedule</a>
        </div>
    </div>
</x-layouts.app>
