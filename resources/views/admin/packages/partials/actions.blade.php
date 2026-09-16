<x-menu>
    <x-menu.item :href="route('admin.packages.show', $package)">
        <x-icon name="eye" class="h-4 w-4" />
        View
        <span class="sr-only">View</span>
    </x-menu.item>
    <x-menu.item :href="route('admin.packages.edit', $package)">
        <x-icon name="pencil" class="h-4 w-4" />
        Edit
        <span class="sr-only">Edit</span>
    </x-menu.item>
    @if ($package->is_active)
        <form method="POST" action="{{ route('admin.packages.deactivate', $package) }}" onsubmit="return confirm('Deactivate this package? It will no longer be available for new assignments.')">
            @csrf
            <button type="submit" class="menu-item" title="Deactivate">
                <x-icon name="ban" class="h-4 w-4" />
                Deactivate
                <span class="sr-only">Deactivate</span>
            </button>
        </form>
    @else
        <form method="POST" action="{{ route('admin.packages.activate', $package) }}">
            @csrf
            <button type="submit" class="menu-item" title="Activate">
                <x-icon name="check" class="h-4 w-4" />
                Activate
                <span class="sr-only">Activate</span>
            </button>
        </form>
    @endif
    @if ($package->canBeDeleted())
        <form method="POST" action="{{ route('admin.packages.destroy', $package) }}" onsubmit="return confirm('Delete this package? This cannot be undone.')">
            @csrf
            @method('DELETE')
            <button type="submit" class="menu-item menu-item-danger" title="Delete">
                <x-icon name="trash" class="h-4 w-4" />
                Delete
                <span class="sr-only">Delete</span>
            </button>
        </form>
    @else
        <button type="button" class="menu-item menu-item-disabled" title="Cannot delete because this package has purchase history." disabled>
            <x-icon name="trash" class="h-4 w-4" />
            Delete
            <span class="sr-only">Delete unavailable</span>
        </button>
    @endif
</x-menu>
