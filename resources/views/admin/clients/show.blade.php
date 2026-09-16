@php
    $current = $client->currentPackage;
    $balance = $client->sessionBalanceStatus();
    $remaining = $current?->remaining_sessions;
@endphp

<x-layouts.app
    title="{{ $client->full_name }}"
    :breadcrumbs="[
        ['label' => 'Dashboard', 'url' => route('admin.dashboard')],
        ['label' => 'Clients', 'url' => route('admin.clients.index')],
        ['label' => $client->full_name],
    ]"
>
    <div class="card mb-6 overflow-hidden">
        <div class="grid lg:grid-cols-[minmax(0,1fr)_15.5rem]">
            <div class="order-2 p-5 sm:p-6 lg:order-1">
                <div class="flex flex-wrap items-center gap-2">
                    <x-status-badge :status="$client->status" />
                    <x-session-balance-badge :status="$balance" />
                </div>

                <dl class="identity-grid">
                    <x-meta label="Preferred coach">
                        {{ $client->preferredCoach?->trainerLabel() ?? '—' }}
                    </x-meta>
                    <x-meta label="Current package">
                        {{ $current?->displayName() ?? 'No Package' }}
                    </x-meta>
                    <x-meta label="Purchased">
                        {{ $current?->purchased_sessions ?? '—' }}
                    </x-meta>
                    <x-meta label="Used">
                        {{ $current?->used_sessions ?? '—' }}
                    </x-meta>
                </dl>

                <div class="mt-5 flex flex-wrap gap-2">
                    <a href="{{ route('admin.clients.packages.create', $client) }}" class="btn btn-primary">
                        <x-icon name="credit-card" class="h-4 w-4" />
                        {{ $current ? 'Recharge / assign package' : 'Assign package' }}
                    </a>
                    <a href="{{ route('admin.schedule.sessions.create', ['client_id' => $client->id]) }}" class="btn btn-secondary">
                        Schedule session
                    </a>
                    <x-menu>
                        <x-menu.item :href="route('admin.clients.edit', $client)">
                            <x-icon name="pencil" class="h-4 w-4" />
                            Edit
                        </x-menu.item>
                        @if ($client->isActive())
                            <form method="POST" action="{{ route('admin.clients.deactivate', $client) }}" onsubmit="return confirm('Deactivate this client? They will no longer be available for scheduling.')">
                                @csrf
                                <button type="submit" class="menu-item">
                                    <x-icon name="ban" class="h-4 w-4" />
                                    Deactivate
                                </button>
                            </form>
                        @else
                            <form method="POST" action="{{ route('admin.clients.activate', $client) }}">
                                @csrf
                                <button type="submit" class="menu-item">
                                    <x-icon name="check" class="h-4 w-4" />
                                    Activate
                                </button>
                            </form>
                        @endif
                    </x-menu>
                </div>
            </div>

            <div class="remaining-panel order-1 lg:order-2">
                <p class="ui-kicker text-white/45">Remaining</p>
                <p class="remaining-value">{{ $remaining === null ? '—' : $remaining }}</p>
                <div class="mt-3">
                    <x-session-balance-badge :status="$balance" />
                </div>
            </div>
        </div>
    </div>

    <div class="grid gap-6 xl:grid-cols-3">
        <x-card class="xl:col-span-2">
            <h2 class="ui-section-title">Client information</h2>
            <dl class="mt-5 grid gap-4 sm:grid-cols-2">
                <x-meta label="Full name">{{ $client->full_name }}</x-meta>
                <x-meta label="Phone">{{ $client->phone }}</x-meta>
                <x-meta label="Email">{{ $client->email ?: '—' }}</x-meta>
                <x-meta label="Date of birth">{{ $client->date_of_birth ? format_date($client->date_of_birth) : '—' }}</x-meta>
                <x-meta label="Gender">{{ $client->gender?->label() ?? '—' }}</x-meta>
                <x-meta label="Preferred coach">{{ $client->preferredCoach?->trainerLabel() ?? '—' }}</x-meta>
                <x-meta label="Emergency contact">
                    {{ $client->emergency_contact_name ?: '—' }}
                    @if ($client->emergency_contact_phone)
                        <span class="mt-0.5 block text-sm font-normal text-muted">{{ $client->emergency_contact_phone }}</span>
                    @endif
                </x-meta>
                <div>
                    <dt class="ui-label">Status</dt>
                    <dd class="mt-1.5"><x-status-badge :status="$client->status" /></dd>
                </div>
                <div class="sm:col-span-2">
                    <dt class="ui-label">Notes</dt>
                    <dd class="ui-value font-normal">{{ $client->notes ?: '—' }}</dd>
                </div>
            </dl>
        </x-card>

        <x-card>
            <h2 class="ui-section-title">Current package</h2>
            @if ($current)
                <dl class="mt-5 space-y-3 text-sm">
                    <div class="flex justify-between gap-3">
                        <dt class="text-muted">Package</dt>
                        <dd class="font-semibold">{{ $current->displayName() }}</dd>
                    </div>
                    <div class="flex justify-between gap-3">
                        <dt class="text-muted">Purchased</dt>
                        <dd class="font-semibold">{{ $current->purchased_sessions }}</dd>
                    </div>
                    <div class="flex justify-between gap-3">
                        <dt class="text-muted">Used</dt>
                        <dd class="font-semibold">{{ $current->used_sessions }}</dd>
                    </div>
                    <div class="flex justify-between gap-3">
                        <dt class="text-muted">Remaining</dt>
                        <dd class="font-semibold">{{ $current->remaining_sessions }}</dd>
                    </div>
                    <div class="flex justify-between gap-3">
                        <dt class="text-muted">Price paid</dt>
                        <dd class="font-semibold">{{ $current->formattedPricePaid() }}</dd>
                    </div>
                    <div class="flex justify-between gap-3">
                        <dt class="text-muted">Start date</dt>
                        <dd class="font-semibold">{{ $current->starts_at ? format_date($current->starts_at) : '—' }}</dd>
                    </div>
                    <div class="flex justify-between gap-3">
                        <dt class="text-muted">Expiry</dt>
                        <dd class="font-semibold">{{ $current->expires_at ? format_date($current->expires_at) : '—' }}</dd>
                    </div>
                    <div class="flex items-center justify-between gap-3">
                        <dt class="text-muted">Status</dt>
                        <dd><x-status-badge :status="$current->status" /></dd>
                    </div>
                </dl>
                <div class="mt-4">
                    <x-session-balance-badge :status="$balance" />
                </div>
            @else
                <x-empty-state
                    icon="credit-card"
                    title="This client does not currently have an active package."
                    description="Assign a package to start tracking purchased and remaining sessions."
                >
                    <a href="{{ route('admin.clients.packages.create', $client) }}" class="btn btn-primary">Assign package</a>
                </x-empty-state>
            @endif
        </x-card>
    </div>

    <div class="mt-6">
        <x-card :padding="false">
            <div class="card-header">
                <h2 class="ui-section-title">Package history</h2>
            </div>
            @if ($client->clientPackages->isEmpty())
                <x-empty-state icon="inbox" title="No package history available." description="Recharges and previous packages will appear here." />
            @else
                <div class="hidden md:block">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Package</th>
                                <th>Start</th>
                                <th>Purchased</th>
                                <th>Used</th>
                                <th>Remaining</th>
                                <th>Price paid</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($client->clientPackages as $history)
                                <tr>
                                    <td class="font-medium">{{ $history->displayName() }}</td>
                                    <td>{{ $history->starts_at ? format_date($history->starts_at) : format_date($history->created_at) }}</td>
                                    <td>{{ $history->purchased_sessions }}</td>
                                    <td>{{ $history->used_sessions }}</td>
                                    <td>{{ $history->remaining_sessions }}</td>
                                    <td>{{ $history->formattedPricePaid() }}</td>
                                    <td><x-status-badge :status="$history->status" /></td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                <div class="space-y-3 p-4 md:hidden">
                    @foreach ($client->clientPackages as $history)
                        <div class="list-card">
                            <div class="flex items-start justify-between gap-3">
                                <p class="font-semibold">{{ $history->displayName() }}</p>
                                <x-status-badge :status="$history->status" />
                            </div>
                            <p class="mt-2 text-sm text-muted">
                                {{ $history->remaining_sessions }} remaining of {{ $history->purchased_sessions }}
                                · {{ $history->formattedPricePaid() }}
                            </p>
                        </div>
                    @endforeach
                </div>
            @endif
        </x-card>
    </div>

    <div class="mt-6">
        <x-card :padding="false">
            <div class="card-header">
                <h2 class="ui-section-title">Session history</h2>
                <div class="flex flex-wrap items-center gap-2">
                    @if ($sessions->total() > 0)
                        <form method="GET" action="{{ route('admin.clients.show', $client) }}" class="flex items-center gap-2">
                            <label for="per_page" class="ui-label mb-0">Per page</label>
                            <select name="per_page" id="per_page" class="form-select min-h-10 w-24" onchange="this.form.submit()">
                                @foreach (\App\Support\AdminListing::PER_PAGE_OPTIONS as $option)
                                    <option value="{{ $option }}" @selected((int) request('per_page', 20) === $option)>{{ $option }}</option>
                                @endforeach
                            </select>
                        </form>
                    @endif
                    <a href="{{ route('admin.schedule.sessions.create', ['client_id' => $client->id]) }}" class="btn btn-secondary btn-sm">Schedule session</a>
                </div>
            </div>
            @if ($sessions->isEmpty())
                <x-empty-state
                    icon="calendar"
                    title="No training sessions yet."
                    description="Schedule a session to assign this client to a coach and package slot."
                >
                    <a href="{{ route('admin.schedule.sessions.create', ['client_id' => $client->id]) }}" class="btn btn-primary">Schedule session</a>
                </x-empty-state>
            @else
                <div class="hidden md:block">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Date</th>
                                <th>Time</th>
                                <th>Coach</th>
                                <th>Status</th>
                                <th>Package</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($sessions as $session)
                                <tr @class(['opacity-60' => $session->isCancelled()])>
                                    <td>
                                        <a href="{{ route('admin.schedule.sessions.show', $session) }}" class="font-medium hover:text-brand">
                                            {{ format_date($session->session_date) }}
                                        </a>
                                    </td>
                                    <td>{{ $session->timeRangeLabel() }}</td>
                                    <td>{{ $session->coach?->trainerLabel() ?? '—' }}</td>
                                    <td><x-status-badge :status="$session->status" /></td>
                                    <td>{{ $session->clientPackage?->displayName() ?? '—' }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                    <x-listing.pagination :paginator="$sessions" />
                </div>
                <div class="space-y-3 p-4 md:hidden">
                    @foreach ($sessions as $session)
                        <div @class(['list-card', 'opacity-60' => $session->isCancelled()])>
                            <a href="{{ route('admin.schedule.sessions.show', $session) }}" class="font-semibold hover:text-brand">
                                {{ format_date($session->session_date) }} · {{ $session->timeRangeLabel() }}
                            </a>
                            <p class="mt-1 text-sm text-muted">{{ $session->coach?->trainerLabel() ?? '—' }}</p>
                            <div class="mt-3 flex flex-wrap items-center gap-2">
                                <x-status-badge :status="$session->status" />
                                <span class="text-sm text-zinc-600">{{ $session->clientPackage?->displayName() ?? '—' }}</span>
                            </div>
                        </div>
                    @endforeach
                    <x-listing.pagination :paginator="$sessions" />
                </div>
            @endif
        </x-card>
    </div>
</x-layouts.app>
