<x-layouts.app
    title="Coaches"
    :breadcrumbs="[
        ['label' => 'Dashboard', 'url' => route('admin.dashboard')],
        ['label' => 'Coaches'],
    ]"
>
    @if ($adminsWithoutProfile->isNotEmpty())
        <x-card class="mb-6">
            <h2 class="ui-section-title">Enable Head Coach as trainer</h2>
            <p class="ui-section-desc">
                The Admin can also personally train clients. Enable a coach profile so they appear in trainer selectors.
            </p>
            <ul class="mt-4 space-y-3">
                @foreach ($adminsWithoutProfile as $admin)
                    <li class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                        <div>
                            <p class="font-semibold">{{ $admin->name }}</p>
                            <p class="text-sm text-zinc-500">{{ $admin->username }} · Head Coach / Admin</p>
                        </div>
                        <form method="POST" action="{{ route('admin.coaches.enable', $admin) }}" onsubmit="return confirm('Enable a coach profile for this Head Coach?')">
                            @csrf
                            <button type="submit" class="btn btn-primary">Enable as Coach</button>
                        </form>
                    </li>
                @endforeach
            </ul>
        </x-card>
    @endif

    <x-listing.toolbar
        :action="route('admin.coaches.index')"
        search-placeholder="Search name, username, email, or phone"
    >
        <x-slot:filters>
            <x-form.select name="status" label="Account status">
                <option value="">All</option>
                <option value="active" @selected(request('status') === 'active')>Active</option>
                <option value="inactive" @selected(request('status') === 'inactive')>Inactive</option>
            </x-form.select>

            <x-form.select name="availability" label="Availability">
                <option value="">All</option>
                <option value="available" @selected(request('availability') === 'available')>Available</option>
                <option value="unavailable" @selected(request('availability') === 'unavailable')>Unavailable</option>
            </x-form.select>

            <x-form.select name="type" label="User type">
                <option value="">All trainers</option>
                <option value="admin" @selected(request('type') === 'admin')>Head Coach / Admin</option>
                <option value="coach" @selected(request('type') === 'coach')>Coaches</option>
            </x-form.select>
        </x-slot:filters>

        <x-slot:actions>
            <a href="{{ route('admin.coaches.create') }}" class="btn btn-primary">
                <x-icon name="plus" class="h-4 w-4" />
                Add coach
            </a>
        </x-slot:actions>
    </x-listing.toolbar>

    @if ($coaches->isEmpty())
        <x-card>
            <x-empty-state
                icon="whistle"
                :title="request()->hasAny(['search', 'status', 'availability', 'type']) ? 'No coaches match the current search and filters.' : 'No coaches have been added yet.'"
                :description="request()->hasAny(['search', 'status', 'availability', 'type']) ? 'Try clearing filters or searching a different name or username.' : 'Add a coach account so they can log in and appear in trainer selectors.'"
            >
                @unless (request()->hasAny(['search', 'status', 'availability', 'type']))
                    <a href="{{ route('admin.coaches.create') }}" class="btn btn-primary">Add coach</a>
                @endunless
            </x-empty-state>
        </x-card>
    @else
        <div class="hidden md:block">
            <x-listing.table>
                <thead>
                    <tr>
                        <th><x-listing.sort-link column="name" label="Coach" /></th>
                        <th><x-listing.sort-link column="username" label="Username" /></th>
                        <th>Phone</th>
                        <th>Assigned clients</th>
                        <th>Upcoming sessions</th>
                        <th>Status</th>
                        <th>Availability</th>
                        <th class="text-right">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($coaches as $coach)
                        <tr>
                            <td>
                                <div class="flex items-center gap-3">
                                    <x-avatar :name="$coach->name" :src="$coach->profileImageUrl()" size="sm" />
                                    <div>
                                        <a href="{{ route('admin.coaches.show', $coach) }}" class="font-semibold text-ink hover:text-brand">
                                            {{ $coach->name }}
                                        </a>
                                        @if ($coach->isAdmin())
                                            <p class="text-xs text-zinc-500">Head Coach / Admin</p>
                                        @endif
                                    </div>
                                </div>
                            </td>
                            <td>{{ $coach->username }}</td>
                            <td>{{ $coach->coachProfile?->phone ?: '—' }}</td>
                            <td>{{ $coach->preferred_clients_count }}</td>
                            <td>{{ $coach->upcoming_sessions_count }}</td>
                            <td>
                                <x-status-badge :status="$coach->is_active ? 'active' : 'inactive'" />
                            </td>
                            <td>
                                <x-status-badge :status="$coach->coachProfile?->is_available ? 'available' : 'unavailable'" />
                            </td>
                            <td class="text-right">
                                @include('admin.coaches.partials.actions', ['coach' => $coach])
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </x-listing.table>
            <x-listing.pagination :paginator="$coaches" />
        </div>

        <div class="space-y-3 md:hidden">
            @foreach ($coaches as $coach)
                <div class="list-card">
                    <div class="flex items-start justify-between gap-3">
                        <div class="flex min-w-0 items-center gap-3">
                            <x-avatar :name="$coach->name" :src="$coach->profileImageUrl()" />
                            <div class="min-w-0">
                                <a href="{{ route('admin.coaches.show', $coach) }}" class="font-semibold text-ink hover:text-brand">
                                    {{ $coach->name }}
                                </a>
                                <p class="mt-1 text-sm text-muted">{{ $coach->username }}</p>
                                @if ($coach->isAdmin())
                                    <p class="text-xs text-muted">Head Coach / Admin</p>
                                @endif
                            </div>
                        </div>
                        <div class="flex shrink-0 items-center gap-2">
                            <x-status-badge :status="$coach->is_active ? 'active' : 'inactive'" />
                            @include('admin.coaches.partials.actions', ['coach' => $coach])
                        </div>
                    </div>
                    <dl class="mt-4 grid grid-cols-2 gap-3">
                        <div>
                            <dt class="ui-label">Availability</dt>
                            <dd class="mt-1.5">
                                <x-status-badge :status="$coach->coachProfile?->is_available ? 'available' : 'unavailable'" />
                            </dd>
                        </div>
                        <x-meta label="Phone">{{ $coach->coachProfile?->phone ?: '—' }}</x-meta>
                        <x-meta label="Assigned clients">{{ $coach->preferred_clients_count }}</x-meta>
                        <x-meta label="Upcoming">{{ $coach->upcoming_sessions_count }}</x-meta>
                    </dl>
                </div>
            @endforeach
            <x-card :padding="false">
                <x-listing.pagination :paginator="$coaches" />
            </x-card>
        </div>
    @endif
</x-layouts.app>
