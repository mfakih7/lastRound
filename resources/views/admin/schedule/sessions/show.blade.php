@php
    $package = $session->clientPackage;
    $consumed = $session->isDone();
@endphp

<x-layouts.app
    title="Session details"
    :breadcrumbs="[
        ['label' => 'Dashboard', 'url' => route('admin.dashboard')],
        ['label' => 'Schedule', 'url' => route('admin.schedule.index', ['date' => $session->session_date->toDateString()])],
        ['label' => $session->timeRangeLabel()],
    ]"
>
    <div class="mb-6 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <x-status-badge :status="$session->status" />
        <div class="flex flex-wrap gap-2">
            @if ($session->isPending())
                <form method="POST" action="{{ route('admin.schedule.sessions.done', $session) }}" onsubmit="return confirm('Mark this session as done? This will use one package session.')">
                    @csrf
                    <button type="submit" class="btn btn-primary">Mark Done</button>
                </form>
            @endif
            <a href="{{ route('admin.schedule.sessions.edit', $session) }}" class="btn btn-secondary">Edit</a>
            @if ($session->isPending() || $session->isDone())
                <form method="POST" action="{{ route('admin.schedule.sessions.cancel', $session) }}" onsubmit="return confirm('{{ $session->isDone() ? 'Cancel this done session? One package session will be restored.' : 'Cancel this session?' }}')">
                    @csrf
                    <button type="submit" class="btn btn-secondary">Cancel</button>
                </form>
            @endif
            @if ($session->canBeDeleted())
                <form method="POST" action="{{ route('admin.schedule.sessions.destroy', $session) }}" onsubmit="return confirm('Delete this session? This cannot be undone.')">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="btn btn-danger">Delete</button>
                </form>
            @endif
        </div>
    </div>

    <div class="grid gap-6 xl:grid-cols-3">
        <x-card class="xl:col-span-2">
            <h2 class="ui-section-title">Session</h2>
            <dl class="mt-5 grid gap-4 sm:grid-cols-2">
                <x-meta label="Client">
                    <a href="{{ route('admin.clients.show', $session->client) }}" class="hover:text-brand">{{ $session->client?->full_name }}</a>
                </x-meta>
                <x-meta label="Coach">
                    @if ($session->coach)
                        <a href="{{ route('admin.coaches.show', $session->coach) }}" class="hover:text-brand">{{ $session->coach->trainerLabel() }}</a>
                    @else
                        —
                    @endif
                </x-meta>
                <x-meta label="Date">{{ format_date($session->session_date) }}</x-meta>
                <x-meta label="Time">{{ $session->timeRangeLabel() }}</x-meta>
                <x-meta label="Duration">{{ $session->durationLabel() ?? '—' }}</x-meta>
                <div>
                    <dt class="ui-label">Status</dt>
                    <dd class="mt-1.5"><x-status-badge :status="$session->status" /></dd>
                </div>
                <div class="sm:col-span-2">
                    <dt class="ui-label">Notes</dt>
                    <dd class="ui-value whitespace-pre-wrap font-normal">{{ $session->notes ?: '—' }}</dd>
                </div>
                <x-meta label="Created">{{ $session->created_at ? format_datetime($session->created_at) : '—' }}</x-meta>
                <x-meta label="Updated">{{ $session->updated_at ? format_datetime($session->updated_at) : '—' }}</x-meta>
            </dl>
        </x-card>

        <x-card>
            <h2 class="ui-section-title">Package</h2>
            @if ($package)
                <dl class="mt-5 space-y-3 text-sm">
                    <div class="flex justify-between gap-3">
                        <dt class="text-muted">Linked package</dt>
                        <dd class="font-semibold">{{ $package->displayName() }}</dd>
                    </div>
                    <div class="flex justify-between gap-3">
                        <dt class="text-muted">Purchased</dt>
                        <dd class="font-semibold">{{ $package->purchased_sessions }}</dd>
                    </div>
                    <div class="flex justify-between gap-3">
                        <dt class="text-muted">Used</dt>
                        <dd class="font-semibold">{{ $package->used_sessions }}</dd>
                    </div>
                    <div class="flex justify-between gap-3">
                        <dt class="text-muted">Remaining</dt>
                        <dd class="font-semibold">{{ $package->remaining_sessions }}</dd>
                    </div>
                    <div class="flex items-center justify-between gap-3">
                        <dt class="text-muted">Package status</dt>
                        <dd><x-status-badge :status="$package->status" /></dd>
                    </div>
                </dl>
                @if ($consumed)
                    <p class="mt-4 rounded-[10px] bg-zinc-100 px-3 py-2 text-sm text-zinc-700">
                        This session consumed one package session when it was marked done.
                    </p>
                @elseif ($session->isPending())
                    <p class="mt-4 text-sm text-muted">
                        This pending session reserves one slot from the linked package until it is done or cancelled.
                    </p>
                @endif
            @else
                <p class="mt-4 text-sm text-muted">No package is linked to this session.</p>
            @endif
        </x-card>
    </div>
</x-layouts.app>
