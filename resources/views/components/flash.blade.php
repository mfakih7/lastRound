@if (session('success') || session('error') || session('warning') || session('status') || session('info'))
    <div class="mb-6 space-y-3">
        @if (session('success'))
            <x-alert type="success">{{ session('success') }}</x-alert>
        @endif
        @if (session('error'))
            <x-alert type="error">{{ session('error') }}</x-alert>
        @endif
        @if (session('warning'))
            <x-alert type="warning">{{ session('warning') }}</x-alert>
        @endif
        @if (session('status') || session('info'))
            <x-alert type="info">{{ session('status') ?? session('info') }}</x-alert>
        @endif
    </div>
@endif
