<x-layouts.app
    title="Edit {{ $coach->name }}"
    :breadcrumbs="[
        ['label' => 'Dashboard', 'url' => route('admin.dashboard')],
        ['label' => 'Coaches', 'url' => route('admin.coaches.index')],
        ['label' => $coach->name, 'url' => route('admin.coaches.show', $coach)],
        ['label' => 'Edit'],
    ]"
>
    <x-card>
        <form method="POST" action="{{ route('admin.coaches.update', $coach) }}" enctype="multipart/form-data" class="space-y-6">
            @csrf
            @method('PUT')
            @include('admin.coaches._form', ['coach' => $coach])
            <div class="form-actions">
                <a href="{{ route('admin.coaches.show', $coach) }}" class="btn btn-secondary">Cancel</a>
                <button type="submit" class="btn btn-primary">Save changes</button>
            </div>
        </form>
    </x-card>
</x-layouts.app>
