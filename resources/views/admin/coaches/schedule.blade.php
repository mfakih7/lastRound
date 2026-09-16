<x-layouts.app
    title="Schedule · {{ $coach->name }}"
    :breadcrumbs="[
        ['label' => 'Dashboard', 'url' => route('admin.dashboard')],
        ['label' => 'Coaches', 'url' => route('admin.coaches.index')],
        ['label' => $coach->name, 'url' => route('admin.coaches.show', $coach)],
        ['label' => 'Schedule'],
    ]"
>
    <div class="mb-5 flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <p class="text-sm text-muted">
            Read-only schedule for {{ $coach->trainerLabel() }}. Create or edit sessions from the Admin schedule.
        </p>
        <div class="w-full sm:max-w-md">
            <x-schedule.period-tabs :period="$period" route-name="admin.coaches.schedule" :route-params="['coach' => $coach]" />
        </div>
    </div>

    <x-card :padding="false">
        <div class="card-header">
            <h2 class="ui-section-title">{{ \App\Support\SchedulePeriod::label($period) }}</h2>
        </div>
        <div class="p-4 sm:p-5">
            @include('partials.session-list', [
                'sessions' => $sessions,
                'showDate' => $period === 'week',
                'emptyTitle' => 'No sessions for '.strtolower(\App\Support\SchedulePeriod::label($period)),
                'emptyDescription' => 'There are no training sessions for this coach in the selected period.',
            ])
        </div>
    </x-card>
</x-layouts.app>
