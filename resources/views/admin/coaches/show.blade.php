<x-layouts.app
    title="{{ $coach->name }}"
    :breadcrumbs="[
        ['label' => 'Dashboard', 'url' => route('admin.dashboard')],
        ['label' => 'Coaches', 'url' => route('admin.coaches.index')],
        ['label' => $coach->name],
    ]"
>
    <div class="mb-6 flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
        <div class="flex flex-wrap items-center gap-2">
            <x-status-badge :status="$coach->is_active ? 'active' : 'inactive'" />
            <x-status-badge :status="$coach->coachProfile?->is_available ? 'available' : 'unavailable'" />
            @if ($coach->isAdmin())
                <x-badge tone="info">Head Coach / Admin</x-badge>
            @endif
        </div>
        <div class="flex flex-wrap gap-2">
            <a href="{{ route('admin.schedule.sessions.create', ['coach_user_id' => $coach->id]) }}" class="btn btn-secondary">
                Schedule session
            </a>
            <a href="{{ route('admin.schedule.index', ['view' => 'week', 'coach_id' => $coach->id]) }}" class="btn btn-primary">
                <x-icon name="calendar" class="h-4 w-4" />
                View schedule
            </a>
            <x-menu>
                <x-menu.item :href="route('admin.coaches.edit', $coach)">
                    <x-icon name="pencil" class="h-4 w-4" />
                    Edit
                </x-menu.item>
                <x-menu.item :href="route('admin.coaches.password.edit', $coach)">
                    <x-icon name="key" class="h-4 w-4" />
                    Change password
                </x-menu.item>
                @if (! $coach->isAdmin())
                    @if ($coach->is_active)
                        <form method="POST" action="{{ route('admin.coaches.deactivate', $coach) }}" onsubmit="return confirm('Deactivate this coach? They will not be able to log in.')">
                            @csrf
                            <button type="submit" class="menu-item">
                                <x-icon name="ban" class="h-4 w-4" />
                                Deactivate
                            </button>
                        </form>
                    @else
                        <form method="POST" action="{{ route('admin.coaches.activate', $coach) }}" onsubmit="return confirm('Activate this coach? They will be able to log in again.')">
                            @csrf
                            <button type="submit" class="menu-item">
                                <x-icon name="check" class="h-4 w-4" />
                                Activate
                            </button>
                        </form>
                    @endif
                @endif
            </x-menu>
        </div>
    </div>

    <div class="grid gap-6 xl:grid-cols-3">
        <x-card class="xl:col-span-2">
            <div class="flex items-start gap-4">
                <x-avatar :name="$coach->name" :src="$coach->profileImageUrl()" size="lg" />
                <div>
                    <h2 class="ui-section-title">Coach information</h2>
                    <p class="ui-section-desc">{{ $coach->accountTypeLabel() }}</p>
                </div>
            </div>
            <dl class="mt-6 grid gap-4 sm:grid-cols-2">
                <x-meta label="Name">{{ $coach->name }}</x-meta>
                <x-meta label="Username">{{ $coach->username }}</x-meta>
                <x-meta label="Email">{{ $coach->email ?: '—' }}</x-meta>
                <x-meta label="Phone">{{ $coach->coachProfile?->phone ?: '—' }}</x-meta>
                <div>
                    <dt class="ui-label">Account status</dt>
                    <dd class="mt-1.5"><x-status-badge :status="$coach->is_active ? 'active' : 'inactive'" /></dd>
                </div>
                <div>
                    <dt class="ui-label">Availability</dt>
                    <dd class="mt-1.5"><x-status-badge :status="$coach->coachProfile?->is_available ? 'available' : 'unavailable'" /></dd>
                </div>
                <x-meta label="Account type">{{ $coach->accountTypeLabel() }}</x-meta>
                <div class="sm:col-span-2">
                    <dt class="ui-label">Notes</dt>
                    <dd class="ui-value whitespace-pre-wrap font-normal">{{ $coach->coachProfile?->notes ?: '—' }}</dd>
                </div>
            </dl>
        </x-card>

        <x-card>
            <h2 class="ui-section-title">Summary</h2>
            <dl class="mt-5 space-y-3 text-sm">
                <div class="flex items-center justify-between gap-3">
                    <dt class="text-muted">Preferred clients</dt>
                    <dd class="font-semibold">{{ $preferredClients->count() }}</dd>
                </div>
                <div class="flex items-center justify-between gap-3">
                    <dt class="text-muted">Upcoming sessions</dt>
                    <dd class="font-semibold">{{ $upcomingSessions->count() }}{{ $upcomingSessions->count() === 10 ? '+' : '' }}</dd>
                </div>
            </dl>
            <p class="mt-4 text-xs text-muted">
                Preferred coach is the client’s main trainer preference. It does not mean this coach runs every session.
            </p>
        </x-card>
    </div>

    <x-card class="mt-6">
        <h2 class="ui-section-title">Preferred clients</h2>
        <p class="ui-section-desc">Clients who currently have this coach set as their preferred / main coach.</p>

        @if ($preferredClients->isEmpty())
            <div class="mt-4">
                <x-empty-state
                    icon="users"
                    title="No preferred clients"
                    description="Clients can be linked to this coach from the client profile."
                />
            </div>
        @else
            <div class="mt-4 hidden md:block">
                <x-listing.table>
                    <thead>
                        <tr>
                            <th>Client</th>
                            <th>Phone</th>
                            <th>Current package</th>
                            <th>Remaining sessions</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($preferredClients as $client)
                            <tr>
                                <td>
                                    <a href="{{ route('admin.clients.show', $client) }}" class="font-semibold text-ink hover:text-brand">
                                        {{ $client->full_name }}
                                    </a>
                                </td>
                                <td>{{ $client->phone }}</td>
                                <td>{{ $client->currentPackage?->displayName() ?? '—' }}</td>
                                <td>{{ $client->remainingSessions() === null ? '—' : $client->remainingSessions() }}</td>
                                <td><x-status-badge :status="$client->status" /></td>
                            </tr>
                        @endforeach
                    </tbody>
                </x-listing.table>
            </div>

            <div class="mt-4 space-y-3 md:hidden">
                @foreach ($preferredClients as $client)
                    <div class="list-card">
                        <a href="{{ route('admin.clients.show', $client) }}" class="font-semibold text-ink hover:text-brand">
                            {{ $client->full_name }}
                        </a>
                        <p class="mt-1 text-sm text-muted">{{ $client->phone }}</p>
                        <div class="mt-3 flex flex-wrap items-center gap-2">
                            <x-status-badge :status="$client->status" />
                            <span class="text-sm text-zinc-600">
                                {{ $client->currentPackage?->displayName() ?? 'No package' }}
                                @if ($client->remainingSessions() !== null)
                                    · {{ $client->remainingSessions() }} remaining
                                @endif
                            </span>
                        </div>
                    </div>
                @endforeach
            </div>
        @endif
    </x-card>

    <x-card class="mt-6">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h2 class="ui-section-title">Upcoming sessions</h2>
                <p class="ui-section-desc">Pending and done sessions from today onward.</p>
            </div>
            <a href="{{ route('admin.schedule.index', ['view' => 'week', 'coach_id' => $coach->id]) }}" class="btn btn-secondary">Open schedule</a>
        </div>
        <div class="mt-4">
            @include('partials.session-list', [
                'sessions' => $upcomingSessions,
                'showDate' => true,
                'showCoach' => false,
                'linkToAdmin' => true,
                'emptyTitle' => 'No upcoming sessions',
                'emptyDescription' => 'When sessions are scheduled for this coach, they will appear here.',
            ])
        </div>
    </x-card>

    <x-card class="mt-6">
        <div>
            <h2 class="ui-section-title">Recent sessions</h2>
            <p class="ui-section-desc">Latest activity for this coach, newest first.</p>
        </div>
        <div class="mt-4">
            @include('partials.session-list', [
                'sessions' => $recentSessions,
                'showDate' => true,
                'showCoach' => false,
                'linkToAdmin' => true,
                'emptyTitle' => 'No session history yet',
                'emptyDescription' => 'Completed and cancelled sessions will appear here.',
            ])
        </div>
    </x-card>
</x-layouts.app>
