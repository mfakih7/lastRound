<x-layouts.app
    title="Edit session"
    :breadcrumbs="[
        ['label' => 'Dashboard', 'url' => route('admin.dashboard')],
        ['label' => 'Schedule', 'url' => route('admin.schedule.index', ['date' => $session->session_date->toDateString()])],
        ['label' => 'Edit'],
    ]"
>
    <x-card>
        @if ($session->isDone())
            <x-alert type="warning" class="mb-5">
                This session is done and already consumed a package session. Client and schedule fields are locked to protect history. You can update notes or change the status.
            </x-alert>
        @endif
        <form method="POST" action="{{ route('admin.schedule.sessions.update', $session) }}" class="space-y-6">
            @csrf
            @method('PUT')
            @include('admin.schedule.sessions._form', ['session' => $session, 'client' => $session->client])
            <div class="form-actions">
                <a href="{{ route('admin.schedule.sessions.show', $session) }}" class="btn btn-secondary">Cancel</a>
                <button type="submit" class="btn btn-primary">Save changes</button>
            </div>
        </form>
    </x-card>

    @include('admin.schedule.sessions._scripts', [
        'summaryUrl' => route('admin.schedule.sessions.client-summary'),
        'selectedClientId' => old('client_id', $session->client_id),
        'defaultDuration' => $defaultDuration,
        'lockDuration' => $session->isDone(),
    ])
</x-layouts.app>
