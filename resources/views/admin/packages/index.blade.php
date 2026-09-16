<x-layouts.app
    title="Packages"
    :breadcrumbs="[
        ['label' => 'Dashboard', 'url' => route('admin.dashboard')],
        ['label' => 'Packages'],
    ]"
>
    <x-listing.toolbar
        :action="route('admin.packages.index')"
        search-placeholder="Search package name or description"
    >
        <x-slot:filters>
            <x-form.select name="status" label="Status">
                <option value="">All statuses</option>
                <option value="active" @selected(request('status') === 'active')>Active</option>
                <option value="inactive" @selected(request('status') === 'inactive')>Inactive</option>
            </x-form.select>
        </x-slot:filters>

        <x-slot:actions>
            <a href="{{ route('admin.packages.create') }}" class="btn btn-primary">
                <x-icon name="plus" class="h-4 w-4" />
                Add package
            </a>
        </x-slot:actions>
    </x-listing.toolbar>

    @if ($packages->isEmpty())
        <x-card>
            <x-empty-state
                icon="package"
                :title="request()->hasAny(['search', 'status']) ? 'No packages match the current search and filters.' : 'No packages have been added yet.'"
                description="Create session packages such as 8 Sessions / {{ money(150) }} to assign them to clients."
            >
                @unless (request()->hasAny(['search', 'status']))
                    <a href="{{ route('admin.packages.create') }}" class="btn btn-primary">Add package</a>
                @endunless
            </x-empty-state>
        </x-card>
    @else
        <div class="hidden md:block">
            <x-listing.table>
                <thead>
                    <tr>
                        <th><x-listing.sort-link column="name" label="Name" /></th>
                        <th><x-listing.sort-link column="sessions_count" label="Sessions" /></th>
                        <th><x-listing.sort-link column="price" label="Price" /></th>
                        <th>Status</th>
                        <th><x-listing.sort-link column="purchases_count" label="Purchases" /></th>
                        <th class="text-right">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($packages as $package)
                        <tr>
                            <td>
                                <a href="{{ route('admin.packages.show', $package) }}" class="font-semibold text-ink hover:text-brand">
                                    {{ $package->name }}
                                </a>
                            </td>
                            <td>{{ $package->sessions_count }}</td>
                            <td>{{ $package->formattedPrice() }}</td>
                            <td>
                                <x-status-badge :status="$package->is_active ? 'active' : 'inactive'" />
                            </td>
                            <td>{{ $package->purchases_count }}</td>
                            <td class="text-right">
                                @include('admin.packages.partials.actions', ['package' => $package])
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </x-listing.table>
            <x-listing.pagination :paginator="$packages" />
        </div>

        <div class="space-y-3 md:hidden">
            @foreach ($packages as $package)
                <div class="list-card">
                    <div class="flex items-start justify-between gap-3">
                        <div class="min-w-0">
                            <a href="{{ route('admin.packages.show', $package) }}" class="font-semibold text-ink hover:text-brand">
                                {{ $package->name }}
                            </a>
                            <p class="mt-1 text-sm text-muted">
                                {{ $package->sessions_count }} sessions · {{ $package->formattedPrice() }}
                            </p>
                        </div>
                        <div class="flex shrink-0 items-center gap-2">
                            <x-status-badge :status="$package->is_active ? 'active' : 'inactive'" />
                            @include('admin.packages.partials.actions', ['package' => $package])
                        </div>
                    </div>
                    <p class="mt-3 text-sm text-muted">{{ $package->purchases_count }} purchases</p>
                </div>
            @endforeach
            <x-card :padding="false">
                <x-listing.pagination :paginator="$packages" />
            </x-card>
        </div>
    @endif
</x-layouts.app>
