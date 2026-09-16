<x-layouts.app
    title="Add coach"
    :breadcrumbs="[
        ['label' => 'Dashboard', 'url' => route('admin.dashboard')],
        ['label' => 'Coaches', 'url' => route('admin.coaches.index')],
        ['label' => 'Add'],
    ]"
>
    <x-card>
        <form method="POST" action="{{ route('admin.coaches.store') }}" enctype="multipart/form-data" class="space-y-6">
            @csrf
            @include('admin.coaches._form')
            <div class="form-actions">
                <a href="{{ route('admin.coaches.index') }}" class="btn btn-secondary">Cancel</a>
                <button type="submit" class="btn btn-primary">Save coach</button>
            </div>
        </form>
    </x-card>
</x-layouts.app>
