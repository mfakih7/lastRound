<li @class([
    'session-card',
    'session-card-pending' => $session->isPending(),
    'session-card-done' => $session->isDone(),
    'session-card-cancelled' => $session->isCancelled(),
])>
    <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
        <div class="min-w-0">
            <p class="font-display text-lg tracking-wide text-ink">{{ $session->timeRangeLabel() }}</p>
            <p class="mt-1 truncate font-semibold">
                <a href="{{ route('admin.clients.show', $session->client) }}" class="hover:text-brand">
                    {{ $session->client?->full_name ?? 'Unknown client' }}
                </a>
            </p>
            <p class="mt-0.5 truncate text-sm text-muted">
                Coach:
                <a href="{{ route('admin.coaches.show', $session->coach) }}" class="hover:text-brand">
                    {{ $session->coach?->trainerLabel() ?? '—' }}
                </a>
            </p>
            @if ($session->notes)
                <p class="mt-2 line-clamp-2 text-sm text-zinc-600">{{ $session->notes }}</p>
            @endif
        </div>
        <div class="flex items-center justify-between gap-2 sm:flex-col sm:items-end">
            <x-status-badge :status="$session->status" />
            <div class="flex items-center gap-2">
                @if ($session->isPending())
                    <form method="POST" action="{{ route('admin.schedule.sessions.done', $session) }}" onsubmit="return confirm('Mark this session as done? This will use one package session.')">
                        @csrf
                        <button type="submit" class="btn btn-primary btn-sm min-h-11 sm:min-h-9">Mark Done</button>
                    </form>
                @endif
                <x-menu>
                    <x-menu.item :href="route('admin.schedule.sessions.show', $session)">
                        <x-icon name="eye" class="h-4 w-4" />
                        View
                    </x-menu.item>
                    <x-menu.item :href="route('admin.schedule.sessions.edit', $session)">
                        <x-icon name="pencil" class="h-4 w-4" />
                        Edit
                    </x-menu.item>
                    @if ($session->isPending() || $session->isDone())
                        <form method="POST" action="{{ route('admin.schedule.sessions.cancel', $session) }}" onsubmit="return confirm('{{ $session->isDone() ? 'Cancel this done session? One package session will be restored.' : 'Cancel this session?' }}')">
                            @csrf
                            <button type="submit" class="menu-item">
                                <x-icon name="ban" class="h-4 w-4" />
                                Cancel
                            </button>
                        </form>
                    @endif
                    @if ($session->canBeDeleted())
                        <form method="POST" action="{{ route('admin.schedule.sessions.destroy', $session) }}" onsubmit="return confirm('Delete this session? This cannot be undone.')">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="menu-item menu-item-danger">
                                <x-icon name="trash" class="h-4 w-4" />
                                Delete
                            </button>
                        </form>
                    @endif
                </x-menu>
            </div>
        </div>
    </div>
</li>
