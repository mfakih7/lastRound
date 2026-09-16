@php
    $summaryUrl = $summaryUrl;
    $selectedClientId = $selectedClientId ?? null;
    $defaultDuration = (int) ($defaultDuration ?? 60);
    $lockDuration = $lockDuration ?? false;
@endphp

@push('scripts')
<script>
    (() => {
        const clientSelect = document.getElementById('client_id');
        const summary = document.getElementById('package-summary-text');
        const start = document.getElementById('start_time');
        const end = document.getElementById('end_time');
        const duration = {{ $defaultDuration }};
        const lockDuration = {{ $lockDuration ? 'true' : 'false' }};
        let autoEnd = true;

        const renderSummary = async (clientId) => {
            if (!summary) {
                return;
            }

            if (!clientId) {
                summary.textContent = 'Select a client to see package remaining sessions.';
                return;
            }

            summary.textContent = 'Loading package…';

            try {
                const response = await fetch('{{ $summaryUrl }}?client_id=' + encodeURIComponent(clientId), {
                    headers: { 'Accept': 'application/json' },
                });
                const data = await response.json();

                if (data.package) {
                    summary.textContent = 'Current package: ' + data.package.name
                        + ' · Remaining: ' + data.package.remaining
                        + ' · Unreserved: ' + data.package.unreserved;
                    return;
                }

                summary.textContent = data.message || 'This client cannot be scheduled.';
            } catch (error) {
                summary.textContent = 'Unable to load package details.';
            }
        };

        clientSelect?.addEventListener('change', (event) => {
            renderSummary(event.target.value);
        });

        if ({{ $selectedClientId ? (int) $selectedClientId : 'null' }}) {
            renderSummary(String({{ (int) $selectedClientId }}));
        }

        const fillEnd = () => {
            if (lockDuration || !start?.value || !end || !autoEnd) {
                return;
            }

            const parts = start.value.split(':').map(Number);
            const date = new Date(2000, 0, 1, parts[0] || 0, parts[1] || 0);
            date.setMinutes(date.getMinutes() + duration);
            const hours = String(date.getHours()).padStart(2, '0');
            const minutes = String(date.getMinutes()).padStart(2, '0');
            end.value = hours + ':' + minutes;
        };

        start?.addEventListener('change', fillEnd);
        end?.addEventListener('input', () => {
            autoEnd = false;
        });
    })();
</script>
@endpush
