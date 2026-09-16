<x-layouts.app
    title="Clients"
    :breadcrumbs="[
        ['label' => 'Dashboard', 'url' => route('admin.dashboard')],
        ['label' => 'Clients'],
    ]"
>
    <x-listing.toolbar
        :action="route('admin.clients.index')"
        search-placeholder="Search name, phone, or email"
    >
        <x-slot:filters>
            <x-form.select name="status" label="Status">
                <option value="">All statuses</option>
                <option value="active" @selected(request('status') === 'active')>Active</option>
                <option value="inactive" @selected(request('status') === 'inactive')>Inactive</option>
            </x-form.select>

            <x-form.select name="preferred_coach_id" label="Preferred coach">
                <option value="">All coaches</option>
                @foreach ($coaches as $coach)
                    <option value="{{ $coach->id }}" @selected((string) request('preferred_coach_id') === (string) $coach->id)>
                        {{ $coach->trainerLabel() }}
                    </option>
                @endforeach
            </x-form.select>

            <x-form.select name="package_id" label="Current package">
                <option value="">All packages</option>
                @foreach ($packages as $package)
                    <option value="{{ $package->id }}" @selected((string) request('package_id') === (string) $package->id)>
                        {{ $package->name }}
                    </option>
                @endforeach
            </x-form.select>

            <x-form.select name="balance" label="Session balance">
                <option value="">All balances</option>
                <option value="healthy" @selected(request('balance') === 'healthy')>Healthy</option>
                <option value="low" @selected(request('balance') === 'low')>Low Sessions</option>
                <option value="recharge" @selected(request('balance') === 'recharge')>Recharge Required</option>
                <option value="attention" @selected(request('balance') === 'attention')>Needs attention</option>
                <option value="none" @selected(request('balance') === 'none')>No Package</option>
            </x-form.select>
        </x-slot:filters>

        <x-slot:actions>
            <a href="{{ route('admin.clients.create') }}" class="btn btn-primary">
                <x-icon name="plus" class="h-4 w-4" />
                Add client
            </a>
        </x-slot:actions>
    </x-listing.toolbar>

    @if ($clients->isEmpty())
        <x-card>
            <x-empty-state
                icon="users"
                :title="request()->hasAny(['search', 'status', 'preferred_coach_id', 'package_id', 'balance']) ? 'No clients match the current search and filters.' : 'No clients have been added yet.'"
                :description="request()->hasAny(['search', 'status', 'preferred_coach_id', 'package_id', 'balance']) ? 'Try clearing filters or searching a different name, phone, or email.' : 'Add your first client to start assigning packages and tracking sessions.'"
            >
                @unless (request()->hasAny(['search', 'status', 'preferred_coach_id', 'package_id', 'balance']))
                    <a href="{{ route('admin.clients.create') }}" class="btn btn-primary">Add client</a>
                @endunless
            </x-empty-state>
        </x-card>
    @else
        <div class="hidden md:block">
            <x-listing.table>
                <thead>
                    <tr>
                        <th><x-listing.sort-link column="full_name" label="Client" /></th>
                        <th>Phone</th>
                        <th>Preferred Coach</th>
                        <th>Current Package</th>
                        <th><x-listing.sort-link column="remaining_sessions" label="Sessions Remaining" /></th>
                        <th>Status</th>
                        <th class="text-right">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($clients as $client)
                        @php $balance = $client->sessionBalanceStatus($threshold); @endphp
                        <tr>
                            <td>
                                <a href="{{ route('admin.clients.show', $client) }}" class="font-semibold text-ink hover:text-brand">
                                    {{ $client->full_name }}
                                </a>
                                @if ($client->email)
                                    <p class="text-xs text-zinc-500">{{ $client->email }}</p>
                                @endif
                            </td>
                            <td>{{ $client->phone }}</td>
                            <td>{{ $client->preferredCoach?->trainerLabel() ?? '—' }}</td>
                            <td>{{ $client->currentPackage?->displayName() ?? '—' }}</td>
                            <td>
                                <div class="flex flex-col gap-1">
                                    <span>{{ $client->remainingSessions() === null ? '—' : $client->remainingSessions() }}</span>
                                    <x-session-balance-badge :status="$balance" />
                                </div>
                            </td>
                            <td><x-status-badge :status="$client->status" /></td>
                            <td class="text-right">
                                @include('admin.clients.partials.actions', ['client' => $client])
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </x-listing.table>
            <x-listing.pagination :paginator="$clients" />
        </div>

        <div class="space-y-3 md:hidden">
            @foreach ($clients as $client)
                @php $balance = $client->sessionBalanceStatus($threshold); @endphp
                <div class="list-card">
                    <div class="flex items-start justify-between gap-3">
                        <div class="min-w-0">
                            <a href="{{ route('admin.clients.show', $client) }}" class="font-semibold text-ink hover:text-brand">
                                {{ $client->full_name }}
                            </a>
                            <p class="mt-1 text-sm text-muted">{{ $client->phone }}</p>
                        </div>
                        <div class="flex shrink-0 items-center gap-2">
                            <x-status-badge :status="$client->status" />
                            @include('admin.clients.partials.actions', ['client' => $client])
                        </div>
                    </div>
                    <dl class="mt-4 grid grid-cols-2 gap-3">
                        <x-meta label="Preferred Coach">{{ $client->preferredCoach?->trainerLabel() ?? '—' }}</x-meta>
                        <x-meta label="Package">{{ $client->currentPackage?->displayName() ?? '—' }}</x-meta>
                        <x-meta label="Remaining">{{ $client->remainingSessions() === null ? '—' : $client->remainingSessions() }}</x-meta>
                        <div>
                            <dt class="ui-label">Balance</dt>
                            <dd class="mt-1.5"><x-session-balance-badge :status="$balance" /></dd>
                        </div>
                    </dl>
                </div>
            @endforeach
            <x-card :padding="false">
                <x-listing.pagination :paginator="$clients" />
            </x-card>
        </div>
    @endif
</x-layouts.app>
