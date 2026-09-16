@php
    $current = $client->currentPackage;
    $packageOptions = $packages->map(fn ($package) => [
        'id' => $package->id,
        'sessions_count' => $package->sessions_count,
        'price' => $package->price,
    ])->values();
@endphp

<x-layouts.app
    title="Assign package"
    :breadcrumbs="[
        ['label' => 'Dashboard', 'url' => route('admin.dashboard')],
        ['label' => 'Clients', 'url' => route('admin.clients.index')],
        ['label' => $client->full_name, 'url' => route('admin.clients.show', $client)],
        ['label' => 'Assign package'],
    ]"
>
    @if ($current && $current->remaining_sessions > 0)
        <x-alert type="warning" class="mb-6">
            This client still has {{ $current->remaining_sessions }} sessions remaining in the current package
            ({{ $current->displayName() }}). A new package cannot be assigned until the current package is completed or has 0 remaining sessions.
        </x-alert>
    @endif

    <x-card>
        <p class="mb-6 text-sm text-muted">
            Assigning a package creates a new purchase record. Previous packages stay in history.
            Sessions purchased and price paid are stored on this purchase, even if the catalog package changes later.
        </p>

        <form method="POST" action="{{ route('admin.clients.packages.store', $client) }}" class="space-y-6" id="assign-package-form">
            @csrf

            <x-form.section title="Purchase details">
                <div class="grid gap-4 sm:grid-cols-2">
                    <x-form.select name="package_id" label="Package" required data-packages='@json($packageOptions)'>
                        <option value="">Select a package</option>
                        @foreach ($packages as $package)
                            <option value="{{ $package->id }}" @selected((string) old('package_id') === (string) $package->id)>
                                {{ $package->name }} · {{ $package->sessions_count }} sessions · {{ $package->formattedPrice() }}
                            </option>
                        @endforeach
                    </x-form.select>

                    <x-form.input name="purchased_sessions" type="number" label="Purchased sessions" :value="old('purchased_sessions')" min="1" required />
                    <x-form.input name="price_paid" type="number" step="0.01" min="0" label="Price paid" :value="old('price_paid')" required />
                    <x-form.input name="starts_at" type="date" label="Start date" :value="old('starts_at', now()->toDateString())" required />
                    <x-form.input name="expires_at" type="date" label="Expiry date" :value="old('expires_at')" />
                </div>
            </x-form.section>

            <div class="form-actions">
                <a href="{{ route('admin.clients.show', $client) }}" class="btn btn-secondary">Cancel</a>
                <button type="submit" class="btn btn-primary" @disabled($current && $current->remaining_sessions > 0)>
                    Assign package
                </button>
            </div>
        </form>
    </x-card>
</x-layouts.app>

<script>
    document.addEventListener('DOMContentLoaded', () => {
        const select = document.getElementById('package_id');
        if (!select) return;

        const packages = JSON.parse(select.getAttribute('data-packages') || '[]');
        const sessions = document.getElementById('purchased_sessions');
        const price = document.getElementById('price_paid');

        const fillDefaults = () => {
            const selected = packages.find((item) => String(item.id) === select.value);
            if (!selected) return;
            if (sessions) sessions.value = selected.sessions_count;
            if (price) price.value = selected.price;
        };

        select.addEventListener('change', fillDefaults);

        if (select.value && !sessions?.value) {
            fillDefaults();
        }
    });
</script>
