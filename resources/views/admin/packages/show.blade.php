<x-layouts.app
    title="{{ $package->name }}"
    :breadcrumbs="[
        ['label' => 'Dashboard', 'url' => route('admin.dashboard')],
        ['label' => 'Packages', 'url' => route('admin.packages.index')],
        ['label' => $package->name],
    ]"
>
    <div class="mb-6 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-end">
        <div class="flex flex-wrap gap-2">
            <a href="{{ route('admin.packages.edit', $package) }}" class="btn btn-secondary">Edit</a>
            @if ($package->is_active)
                <form method="POST" action="{{ route('admin.packages.deactivate', $package) }}" onsubmit="return confirm('Deactivate this package? It will no longer be available for new assignments.')">
                    @csrf
                    <button type="submit" class="btn btn-secondary">Deactivate</button>
                </form>
            @else
                <form method="POST" action="{{ route('admin.packages.activate', $package) }}">
                    @csrf
                    <button type="submit" class="btn btn-primary">Activate</button>
                </form>
            @endif
        </div>
    </div>

    <x-card>
        <div class="flex flex-wrap items-center gap-2">
            <h2 class="ui-section-title">Package details</h2>
            <x-status-badge :status="$package->is_active ? 'active' : 'inactive'" />
        </div>
        <dl class="mt-5 grid gap-4 sm:grid-cols-2">
            <x-meta label="Name">{{ $package->name }}</x-meta>
            <x-meta label="Sessions">{{ $package->sessions_count }}</x-meta>
            <x-meta label="Price">{{ $package->formattedPrice() }}</x-meta>
            <x-meta label="Purchases">{{ $package->client_packages_count }}</x-meta>
            <div class="sm:col-span-2">
                <dt class="ui-label">Description</dt>
                <dd class="ui-value font-normal">{{ $package->description ?: '—' }}</dd>
            </div>
        </dl>
        @if (! $package->canBeDeleted())
            <x-alert type="info" class="mt-6">
                This package has purchase history, so it cannot be deleted. Deactivate it if it should no longer be assigned.
            </x-alert>
        @endif
    </x-card>
</x-layouts.app>
