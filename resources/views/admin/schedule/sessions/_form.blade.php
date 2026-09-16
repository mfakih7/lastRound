@php
    $session ??= null;
    $locked = $session?->isDone() ?? false;
    $defaultCoachId ??= null;
    $defaultDate ??= today()->toDateString();
    $selectedClientId = old('client_id', $session?->client_id ?? $client?->id);
    $selectedCoachId = old('coach_user_id', $session?->coach_user_id ?? $defaultCoachId);
@endphp

<div class="grid gap-4 sm:grid-cols-2">
    <x-form.select name="client_id" label="Client" required :disabled="$locked">
        <option value="">Select client</option>
        @foreach ($clients as $option)
            <option value="{{ $option->id }}" @selected((string) $selectedClientId === (string) $option->id)>
                {{ $option->full_name }}
            </option>
        @endforeach
    </x-form.select>
    @if ($locked)
        <input type="hidden" name="client_id" value="{{ $session->client_id }}">
    @endif

    <x-form.select name="coach_user_id" label="Coach" required :disabled="$locked">
        <option value="">Select coach</option>
        @foreach ($coaches as $coach)
            <option value="{{ $coach->id }}" @selected((string) $selectedCoachId === (string) $coach->id)>
                {{ $coach->trainerLabel() }}
            </option>
        @endforeach
    </x-form.select>
    @if ($locked)
        <input type="hidden" name="coach_user_id" value="{{ $session->coach_user_id }}">
    @endif

    <x-form.input
        name="session_date"
        type="date"
        label="Date"
        :value="old('session_date', $session?->session_date?->format('Y-m-d') ?? $defaultDate ?? today()->toDateString())"
        required
        :disabled="$locked"
    />
    @if ($locked)
        <input type="hidden" name="session_date" value="{{ $session->session_date->format('Y-m-d') }}">
    @endif

    <div class="sm:col-span-2 rounded-[12px] border border-line bg-zinc-50 p-4 text-sm" id="package-summary">
        <p class="font-semibold text-ink">Current package</p>
        <p class="mt-1 text-muted" id="package-summary-text">Select a client to see package remaining sessions.</p>
    </div>

    <x-form.input
        name="start_time"
        type="time"
        label="Start time"
        :value="old('start_time', $session ? $session->formattedStartTime() : '')"
        required
        :disabled="$locked"
        id="start_time"
    />
    @if ($locked)
        <input type="hidden" name="start_time" value="{{ $session->formattedStartTime() }}">
    @endif

    <x-form.input
        name="end_time"
        type="time"
        label="End time"
        :value="old('end_time', $session ? $session->formattedEndTime() : '')"
        required
        :disabled="$locked"
        id="end_time"
        data-default-duration="{{ $defaultDuration ?? 60 }}"
    />
    @if ($locked)
        <input type="hidden" name="end_time" value="{{ $session->formattedEndTime() }}">
    @endif

    @if ($session)
        <x-form.select name="status" label="Status" required>
            @foreach (\App\Enums\TrainingSessionStatus::cases() as $status)
                <option value="{{ $status->value }}" @selected(old('status', $session->status->value) === $status->value)>
                    {{ $status->label() }}
                </option>
            @endforeach
        </x-form.select>
    @endif

    <div class="sm:col-span-2">
        <x-form.textarea name="notes" label="Notes" :value="old('notes', $session?->notes)" rows="3" />
    </div>
</div>
