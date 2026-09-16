<x-layouts.app
    title="Schedule session"
    :breadcrumbs="[
        ['label' => 'Dashboard', 'url' => route('admin.dashboard')],
        ['label' => 'Schedule', 'url' => route('admin.schedule.index')],
        ['label' => 'New session'],
    ]"
>
    <x-card>
        <form method="POST" action="{{ route('admin.schedule.sessions.store') }}" class="space-y-6">
            @csrf
            @include('admin.schedule.sessions._form')
            <div class="form-actions">
                <a href="{{ route('admin.schedule.index', ['date' => $defaultDate]) }}" class="btn btn-secondary">Cancel</a>
                <button type="submit" class="btn btn-primary">Save session</button>
            </div>
        </form>
    </x-card>

    @include('admin.schedule.sessions._scripts', [
        'summaryUrl' => route('admin.schedule.sessions.client-summary'),
        'selectedClientId' => old('client_id', $client?->id),
        'defaultDuration' => $defaultDuration,
    ])
</x-layouts.app>
