<x-layouts.app title="Dashboard">
    <div class="grid gap-3 sm:grid-cols-2 xl:grid-cols-4">
        @foreach ($metrics as $metric)
            <x-stat-card
                :label="$metric['label']"
                :value="number_format($metric['value'])"
                :hint="$metric['hint']"
                :url="$metric['url'] ?? null"
            />
        @endforeach
    </div>

    <div class="mt-6">
        <x-card :padding="false">
            <div class="card-header">
                <div>
                    <h2 class="ui-section-title">Today’s schedule</h2>
                    <p class="ui-section-desc">Next sessions for today.</p>
                </div>
                <a href="{{ route('admin.schedule.index', ['date' => today()->toDateString()]) }}" class="btn btn-secondary btn-sm">
                    View full schedule
                </a>
            </div>
            @if ($todaysSchedule->isEmpty())
                <x-empty-state
                    icon="calendar"
                    title="No sessions scheduled for today."
                    description="Schedule a session to fill today’s board."
                >
                    <a href="{{ route('admin.schedule.sessions.create', ['date' => today()->toDateString()]) }}" class="btn btn-primary">Schedule session</a>
                </x-empty-state>
            @else
                <ul class="divide-y divide-line">
                    @foreach ($todaysSchedule as $session)
                        <li @class(['flex flex-col gap-3 px-5 py-4 sm:flex-row sm:items-center sm:justify-between sm:px-6', 'opacity-60' => $session->isCancelled()])>
                            <div class="min-w-0">
                                <p class="font-display text-base tracking-wide text-ink">{{ $session->timeRangeLabel() }}</p>
                                <p class="mt-0.5 truncate text-sm text-muted">
                                    {{ $session->client?->full_name }} · {{ $session->coach?->trainerLabel() }}
                                </p>
                            </div>
                            <div class="flex items-center justify-between gap-3 sm:justify-end">
                                <x-status-badge :status="$session->status" />
                                <a href="{{ route('admin.schedule.sessions.show', $session) }}" class="text-sm font-semibold text-brand hover:text-brand-dark">View</a>
                            </div>
                        </li>
                    @endforeach
                </ul>
            @endif
        </x-card>
    </div>

    <div class="mt-6">
        <x-card :padding="false">
            <div class="card-header">
                <div>
                    <h2 class="ui-section-title">Upcoming sessions</h2>
                    <p class="ui-section-desc">The next sessions after today.</p>
                </div>
                <a href="{{ route('admin.schedule.index', ['view' => 'week']) }}" class="btn btn-secondary btn-sm">
                    Open week
                </a>
            </div>
            @if ($upcomingSessions->isEmpty())
                <x-empty-state
                    icon="calendar"
                    title="No upcoming sessions."
                    description="Future sessions will appear here after they are scheduled."
                />
            @else
                <ul class="divide-y divide-line">
                    @foreach ($upcomingSessions as $session)
                        <li class="flex flex-col gap-3 px-5 py-4 sm:flex-row sm:items-center sm:justify-between sm:px-6">
                            <div class="min-w-0">
                                <p class="ui-label">{{ format_date($session->session_date) }}</p>
                                <p class="mt-1 font-display text-base tracking-wide text-ink">{{ $session->timeRangeLabel() }}</p>
                                <p class="mt-0.5 truncate text-sm text-muted">
                                    {{ $session->client?->full_name }} · {{ $session->coach?->trainerLabel() }}
                                </p>
                            </div>
                            <div class="flex items-center justify-between gap-3 sm:justify-end">
                                <x-status-badge :status="$session->status" />
                                <a href="{{ route('admin.schedule.sessions.show', $session) }}" class="text-sm font-semibold text-brand hover:text-brand-dark">View</a>
                            </div>
                        </li>
                    @endforeach
                </ul>
            @endif
        </x-card>
    </div>

    <div class="mt-6">
        <x-card :padding="false">
            <div class="card-header">
                <div>
                    <h2 class="ui-section-title">Clients needing attention</h2>
                    <p class="ui-section-desc">Low sessions or recharge required. Threshold: {{ $threshold }} remaining.</p>
                </div>
                <a href="{{ route('admin.clients.index', ['balance' => 'attention']) }}" class="btn btn-secondary btn-sm">
                    View filtered clients
                </a>
            </div>

            @if ($clientsNeedingAttention->isEmpty())
                <x-empty-state
                    icon="check"
                    title="No clients need attention right now."
                    description="Clients with low remaining sessions or 0 remaining will appear here."
                />
            @else
                <div class="hidden md:block">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Client</th>
                                <th>Package</th>
                                <th>Remaining</th>
                                <th>Status</th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($clientsNeedingAttention as $client)
                                @php $balance = $client->sessionBalanceStatus($threshold); @endphp
                                <tr>
                                    <td class="font-medium">
                                        <a href="{{ route('admin.clients.show', $client) }}" class="hover:text-brand">{{ $client->full_name }}</a>
                                    </td>
                                    <td>{{ $client->currentPackage?->displayName() ?? '—' }}</td>
                                    <td class="font-semibold">{{ $client->remainingSessions() ?? 0 }}</td>
                                    <td><x-session-balance-badge :status="$balance" /></td>
                                    <td class="text-right">
                                        <a href="{{ route('admin.clients.packages.create', $client) }}" class="text-sm font-semibold text-brand hover:text-brand-dark">Recharge</a>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                <div class="space-y-3 p-4 md:hidden">
                    @foreach ($clientsNeedingAttention as $client)
                        @php $balance = $client->sessionBalanceStatus($threshold); @endphp
                        <div class="list-card">
                            <a href="{{ route('admin.clients.show', $client) }}" class="font-semibold hover:text-brand">{{ $client->full_name }}</a>
                            <p class="mt-1 text-sm text-muted">
                                {{ $client->currentPackage?->displayName() ?? '—' }} · {{ $client->remainingSessions() ?? '—' }} remaining
                            </p>
                            <div class="mt-3 flex items-center justify-between gap-3">
                                <x-session-balance-badge :status="$balance" />
                                <a href="{{ route('admin.clients.packages.create', $client) }}" class="text-sm font-semibold text-brand">Recharge</a>
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif
        </x-card>
    </div>
</x-layouts.app>
