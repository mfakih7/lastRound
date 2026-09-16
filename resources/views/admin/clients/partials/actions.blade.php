<x-menu>
    <x-menu.item :href="route('admin.clients.show', $client)">
        <x-icon name="eye" class="h-4 w-4" />
        View
        <span class="sr-only">View</span>
    </x-menu.item>
    <x-menu.item :href="route('admin.clients.edit', $client)">
        <x-icon name="pencil" class="h-4 w-4" />
        Edit
        <span class="sr-only">Edit</span>
    </x-menu.item>
    <x-menu.item :href="route('admin.clients.packages.create', $client)">
        <x-icon name="credit-card" class="h-4 w-4" />
        Assign package
        <span class="sr-only">Assign package</span>
    </x-menu.item>
    @if ($client->isActive())
        <form method="POST" action="{{ route('admin.clients.deactivate', $client) }}" onsubmit="return confirm('Deactivate this client? They will no longer be available for scheduling.')">
            @csrf
            <button type="submit" class="menu-item" title="Deactivate">
                <x-icon name="ban" class="h-4 w-4" />
                Deactivate
                <span class="sr-only">Deactivate</span>
            </button>
        </form>
    @else
        <form method="POST" action="{{ route('admin.clients.activate', $client) }}">
            @csrf
            <button type="submit" class="menu-item" title="Activate">
                <x-icon name="check" class="h-4 w-4" />
                Activate
                <span class="sr-only">Activate</span>
            </button>
        </form>
    @endif
    @if ($client->canBeDeleted())
        <form method="POST" action="{{ route('admin.clients.destroy', $client) }}" onsubmit="return confirm('Delete this client? This cannot be undone.')">
            @csrf
            @method('DELETE')
            <button type="submit" class="menu-item menu-item-danger" title="Delete">
                <x-icon name="trash" class="h-4 w-4" />
                Delete
                <span class="sr-only">Delete</span>
            </button>
        </form>
    @else
        <button type="button" class="menu-item menu-item-disabled" title="Cannot delete because this client has package or session history." disabled>
            <x-icon name="trash" class="h-4 w-4" />
            Delete
            <span class="sr-only">Delete unavailable</span>
        </button>
    @endif
</x-menu>
