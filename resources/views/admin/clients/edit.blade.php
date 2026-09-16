<x-layouts.app
    title="Edit {{ $client->full_name }}"
    :breadcrumbs="[
        ['label' => 'Dashboard', 'url' => route('admin.dashboard')],
        ['label' => 'Clients', 'url' => route('admin.clients.index')],
        ['label' => $client->full_name, 'url' => route('admin.clients.show', $client)],
        ['label' => 'Edit'],
    ]"
>
    <x-card>
        <form method="POST" action="{{ route('admin.clients.update', $client) }}" class="space-y-6">
            @csrf
            @method('PUT')
            @include('admin.clients._form', ['client' => $client, 'coaches' => $coaches])
            <div class="form-actions">
                <a href="{{ route('admin.clients.show', $client) }}" class="btn btn-secondary">Cancel</a>
                <button type="submit" class="btn btn-primary">Save changes</button>
            </div>
        </form>
    </x-card>
</x-layouts.app>
