<x-layouts.app
    title="Add client"
    :breadcrumbs="[
        ['label' => 'Dashboard', 'url' => route('admin.dashboard')],
        ['label' => 'Clients', 'url' => route('admin.clients.index')],
        ['label' => 'Add'],
    ]"
>
    <x-card>
        <form method="POST" action="{{ route('admin.clients.store') }}" class="space-y-6">
            @csrf
            @include('admin.clients._form', ['coaches' => $coaches])
            <div class="form-actions">
                <a href="{{ route('admin.clients.index') }}" class="btn btn-secondary">Cancel</a>
                <button type="submit" class="btn btn-primary">Save client</button>
            </div>
        </form>
    </x-card>
</x-layouts.app>
