@props([
    'sessions',
    'emptyTitle' => 'No sessions in this period',
    'emptyDescription' => 'Scheduled sessions will appear here.',
    'showDate' => false,
    'showCoach' => false,
    'linkToAdmin' => false,
])

@if ($sessions->isEmpty())
    <x-empty-state icon="calendar" :title="$emptyTitle" :description="$emptyDescription" />
@else
    <ul class="space-y-3">
        @foreach ($sessions as $session)
            <li @class([
                'session-card',
                'session-card-pending' => $session->isPending(),
                'session-card-done' => $session->isDone(),
                'session-card-cancelled' => $session->isCancelled(),
            ])>
                <div class="flex items-start justify-between gap-3">
                    <div class="min-w-0">
                        @if ($showDate)
                            <p class="ui-label">
                                {{ \App\Support\DateFormat::weekdayDate($session->session_date) }}
                            </p>
                        @endif
                        <p @class(['font-display text-lg tracking-wide text-ink', 'mt-1' => $showDate])>
                            @if ($linkToAdmin)
                                <a href="{{ route('admin.schedule.sessions.show', $session) }}" class="hover:text-brand">
                                    {{ $session->timeRangeLabel() }}
                                </a>
                            @else
                                {{ $session->timeRangeLabel() }}
                            @endif
                        </p>
                        <p class="mt-1 truncate font-semibold">{{ $session->client?->full_name ?? 'Unknown client' }}</p>
                        @if ($showCoach && $session->coach)
                            <p class="mt-0.5 text-sm text-muted">{{ $session->coach->trainerLabel() }}</p>
                        @endif
                        @if ($session->notes)
                            <p class="mt-1 line-clamp-2 text-sm text-zinc-600">{{ $session->notes }}</p>
                        @endif
                    </div>
                    <x-status-badge :status="$session->status" />
                </div>
            </li>
        @endforeach
    </ul>
@endif
