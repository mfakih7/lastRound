<x-layouts.app title="My Schedule">
    <div class="mx-auto max-w-2xl">
        <p class="ui-kicker">{{ strtoupper(app_name()) }}</p>
        <h2 class="mt-2 text-lg font-semibold tracking-tight">Welcome, {{ auth()->user()->name }}</h2>
        <p class="mt-1 text-sm text-muted">Who you are training, and at what time.</p>

        <div class="mt-5">
            <x-schedule.period-tabs :period="$period" route-name="coach.schedule.index" />
        </div>

        <x-card class="mt-5" :padding="false">
            <div class="card-header">
                <h3 class="ui-section-title">{{ \App\Support\SchedulePeriod::label($period) }}</h3>
            </div>
            <div class="p-4 sm:p-5">
                @include('partials.session-list', [
                    'sessions' => $sessions,
                    'showDate' => $period === 'week',
                    'emptyTitle' => match ($period) {
                        'today' => 'You have no sessions scheduled today.',
                        'tomorrow' => 'You have no sessions scheduled tomorrow.',
                        default => 'You have no sessions scheduled this week.',
                    },
                    'emptyDescription' => 'When you are assigned sessions, they will appear here.',
                ])
            </div>
        </x-card>
    </div>
</x-layouts.app>
