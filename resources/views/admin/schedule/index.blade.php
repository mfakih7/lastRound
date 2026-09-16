@php
    $view = $view;
    $date = $date;
    $preserved = request()->except(['date', 'view', 'page']);
    $dayQuery = array_merge($preserved, ['view' => 'day']);
    $weekQuery = array_merge($preserved, ['view' => 'week']);
    $hasScheduleFilters = request()->filled('coach_id') || request()->filled('client_id') || request()->filled('status');
@endphp

<x-layouts.app
    title="Schedule"
    :breadcrumbs="[
        ['label' => 'Dashboard', 'url' => route('admin.dashboard')],
        ['label' => 'Schedule'],
    ]"
>
    <div class="mb-4 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <div class="segmented grid-cols-2 w-full max-w-[17rem]">
            <a href="{{ route('admin.schedule.index', array_merge($dayQuery, ['date' => $date->toDateString()])) }}" @class(['segmented-item', 'segmented-item-active' => $view === 'day'])>Daily</a>
            <a href="{{ route('admin.schedule.index', array_merge($weekQuery, ['date' => $date->toDateString()])) }}" @class(['segmented-item', 'segmented-item-active' => $view === 'week'])>Weekly</a>
        </div>
        <a href="{{ route('admin.schedule.sessions.create', ['date' => $date->toDateString()]) }}" class="btn btn-primary w-full sm:w-auto">
            <x-icon name="plus" class="h-4 w-4" />
            Schedule session
        </a>
    </div>

    <x-card class="mb-6">
        <form method="GET" action="{{ route('admin.schedule.index') }}">
            <input type="hidden" name="view" value="{{ $view }}">

            <div class="grid gap-3 sm:grid-cols-2 xl:grid-cols-4">
                <x-form.input name="date" type="date" label="Date" :value="$date->toDateString()" required />
            </div>

            <details class="filter-details mt-4" @open($hasScheduleFilters)>
                <summary class="py-1">Filters</summary>
                <div class="mt-3 grid gap-3 sm:grid-cols-2 md:mt-0 xl:grid-cols-4">
                    <x-form.select name="coach_id" label="Coach">
                        <option value="">All coaches</option>
                        @foreach ($coaches as $coach)
                            <option value="{{ $coach->id }}" @selected((string) request('coach_id') === (string) $coach->id)>
                                {{ $coach->trainerLabel() }}
                            </option>
                        @endforeach
                    </x-form.select>

                    <x-form.select name="client_id" label="Client">
                        <option value="">All clients</option>
                        @foreach ($clients as $client)
                            <option value="{{ $client->id }}" @selected((string) request('client_id') === (string) $client->id)>
                                {{ $client->full_name }}
                            </option>
                        @endforeach
                    </x-form.select>

                    <x-form.select name="status" label="Status">
                        <option value="">All statuses</option>
                        @foreach (\App\Enums\TrainingSessionStatus::cases() as $status)
                            <option value="{{ $status->value }}" @selected(request('status') === $status->value)>{{ $status->label() }}</option>
                        @endforeach
                    </x-form.select>
                </div>
            </details>

            <div class="mt-4 flex flex-col gap-2 sm:flex-row">
                <button type="submit" class="btn btn-primary">Apply filters</button>
                <a href="{{ route('admin.schedule.index', ['view' => $view, 'date' => $date->toDateString()]) }}" class="btn btn-secondary">Reset</a>
            </div>
        </form>
    </x-card>

    <div class="mb-6 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <h2 class="ui-schedule-title">
                @if ($view === 'week')
                    Week of {{ \App\Support\DateFormat::weekRange($weekStart, $weekEnd) }}
                @else
                    {{ \App\Support\DateFormat::scheduleDayHeading($date) }}
                @endif
            </h2>
        </div>
        <div class="schedule-nav">
            @if ($view === 'week')
                <a href="{{ route('admin.schedule.index', array_merge($preserved, ['view' => 'week', 'date' => $date->copy()->subWeek()->toDateString()])) }}" class="btn btn-secondary">Previous week</a>
                <a href="{{ route('admin.schedule.index', array_merge($preserved, ['view' => 'week', 'date' => today()->toDateString()])) }}" class="btn btn-secondary">Current week</a>
                <a href="{{ route('admin.schedule.index', array_merge($preserved, ['view' => 'week', 'date' => $date->copy()->addWeek()->toDateString()])) }}" class="btn btn-secondary">Next week</a>
            @else
                <a href="{{ route('admin.schedule.index', array_merge($preserved, ['view' => 'day', 'date' => $date->copy()->subDay()->toDateString()])) }}" class="btn btn-secondary">Previous day</a>
                <a href="{{ route('admin.schedule.index', array_merge($preserved, ['view' => 'day', 'date' => today()->toDateString()])) }}" class="btn btn-secondary">Today</a>
                <a href="{{ route('admin.schedule.index', array_merge($preserved, ['view' => 'day', 'date' => $date->copy()->addDay()->toDateString()])) }}" class="btn btn-secondary">Next day</a>
            @endif
        </div>
    </div>

    @if ($view === 'week')
        <div class="space-y-5">
            @foreach ($days as $dayKey => $daySessions)
                @php $dayDate = \Carbon\Carbon::parse($dayKey); @endphp
                <x-card>
                    <div class="mb-3 flex items-center justify-between gap-3">
                        <h3 class="ui-schedule-title">
                            {{ \App\Support\DateFormat::longWeekdayDate($dayDate) }}
                        </h3>
                        <a href="{{ route('admin.schedule.sessions.create', ['date' => $dayKey]) }}" class="text-sm font-semibold text-brand hover:text-brand-dark">Add</a>
                    </div>
                    @if ($daySessions->isEmpty())
                        <p class="text-sm text-muted">No sessions scheduled for this date.</p>
                    @else
                        <div class="space-y-4">
                            @foreach ($daySessions->groupBy('coach_user_id') as $coachSessions)
                                <div>
                                    <p class="ui-label mb-2">
                                        {{ $coachSessions->first()?->coach?->trainerLabel() ?? 'Coach' }}
                                    </p>
                                    <ul class="space-y-3">
                                        @foreach ($coachSessions as $session)
                                            @include('admin.schedule.partials.session-card', ['session' => $session])
                                        @endforeach
                                    </ul>
                                </div>
                            @endforeach
                        </div>
                    @endif
                </x-card>
            @endforeach
        </div>
    @elseif ($sessions->isEmpty())
        <x-card>
            <x-empty-state
                icon="calendar"
                title="No sessions scheduled for this date."
                description="Create a session to assign a client, coach, and package slot."
            >
                <a href="{{ route('admin.schedule.sessions.create', ['date' => $date->toDateString()]) }}" class="btn btn-primary">Schedule session</a>
            </x-empty-state>
        </x-card>
    @else
        <ul class="space-y-3">
            @foreach ($sessions as $session)
                @include('admin.schedule.partials.session-card', ['session' => $session])
            @endforeach
        </ul>
    @endif
</x-layouts.app>
