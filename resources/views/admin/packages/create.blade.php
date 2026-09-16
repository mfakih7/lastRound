<x-layouts.app
    title="Add package"
    :breadcrumbs="[
        ['label' => 'Dashboard', 'url' => route('admin.dashboard')],
        ['label' => 'Packages', 'url' => route('admin.packages.index')],
        ['label' => 'Add'],
    ]"
>
    <x-card>
        <form method="POST" action="{{ route('admin.packages.store') }}" class="space-y-6">
            @csrf
            @include('admin.packages._form')
            <div class="form-actions">
                <a href="{{ route('admin.packages.index') }}" class="btn btn-secondary">Cancel</a>
                <button type="submit" class="btn btn-primary">Save package</button>
            </div>
        </form>
    </x-card>
</x-layouts.app>
