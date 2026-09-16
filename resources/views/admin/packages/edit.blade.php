<x-layouts.app
    title="Edit {{ $package->name }}"
    :breadcrumbs="[
        ['label' => 'Dashboard', 'url' => route('admin.dashboard')],
        ['label' => 'Packages', 'url' => route('admin.packages.index')],
        ['label' => $package->name, 'url' => route('admin.packages.show', $package)],
        ['label' => 'Edit'],
    ]"
>
    <x-card>
        <form method="POST" action="{{ route('admin.packages.update', $package) }}" class="space-y-6">
            @csrf
            @method('PUT')
            @include('admin.packages._form', ['package' => $package])
            <div class="form-actions">
                <a href="{{ route('admin.packages.show', $package) }}" class="btn btn-secondary">Cancel</a>
                <button type="submit" class="btn btn-primary">Save changes</button>
            </div>
        </form>
    </x-card>
</x-layouts.app>
