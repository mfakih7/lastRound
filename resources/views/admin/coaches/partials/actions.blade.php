<x-menu>
    <x-menu.item :href="route('admin.coaches.show', $coach)">
        <x-icon name="eye" class="h-4 w-4" />
        View
        <span class="sr-only">View</span>
    </x-menu.item>
    <x-menu.item :href="route('admin.coaches.edit', $coach)">
        <x-icon name="pencil" class="h-4 w-4" />
        Edit
        <span class="sr-only">Edit</span>
    </x-menu.item>
    <x-menu.item :href="route('admin.coaches.schedule', $coach)">
        <x-icon name="calendar" class="h-4 w-4" />
        View schedule
        <span class="sr-only">View schedule</span>
    </x-menu.item>
    <x-menu.item :href="route('admin.coaches.password.edit', $coach)">
        <x-icon name="key" class="h-4 w-4" />
        Change password
        <span class="sr-only">Change password</span>
    </x-menu.item>
    @if ($coach->isAdmin())
        <button type="button" class="menu-item menu-item-disabled" title="The Head Coach / Admin account cannot be deactivated." disabled>
            <x-icon name="ban" class="h-4 w-4" />
            Deactivate
            <span class="sr-only">Deactivate unavailable</span>
        </button>
    @elseif ($coach->is_active)
        <form method="POST" action="{{ route('admin.coaches.deactivate', $coach) }}" onsubmit="return confirm('Deactivate this coach? They will not be able to log in.')">
            @csrf
            <button type="submit" class="menu-item" title="Deactivate">
                <x-icon name="ban" class="h-4 w-4" />
                Deactivate
                <span class="sr-only">Deactivate</span>
            </button>
        </form>
    @else
        <form method="POST" action="{{ route('admin.coaches.activate', $coach) }}" onsubmit="return confirm('Activate this coach? They will be able to log in again.')">
            @csrf
            <button type="submit" class="menu-item" title="Activate">
                <x-icon name="check" class="h-4 w-4" />
                Activate
                <span class="sr-only">Activate</span>
            </button>
        </form>
    @endif
    @if ($coach->canBeDeleted())
        <form method="POST" action="{{ route('admin.coaches.destroy', $coach) }}" onsubmit="return confirm('Delete this coach? This cannot be undone.')">
            @csrf
            @method('DELETE')
            <button type="submit" class="menu-item menu-item-danger" title="Delete">
                <x-icon name="trash" class="h-4 w-4" />
                Delete
                <span class="sr-only">Delete</span>
            </button>
        </form>
    @else
        <button type="button" class="menu-item menu-item-disabled" title="{{ $coach->deletionBlockReason() }}" disabled>
            <x-icon name="trash" class="h-4 w-4" />
            Delete
            <span class="sr-only">Delete unavailable</span>
        </button>
    @endif
</x-menu>
