@php
    $client ??= null;
@endphp

<div class="space-y-8">
    <x-form.section title="Personal information" description="Identity and contact details used across scheduling and packages.">
        <div class="grid gap-4 sm:grid-cols-2">
            <x-form.input name="full_name" label="Full name" :value="$client?->full_name" required />
            <x-form.input name="phone" label="Phone" :value="$client?->phone" required />
            <x-form.input name="email" type="email" label="Email" :value="$client?->email" />
            <x-form.input name="date_of_birth" type="date" label="Date of birth" :value="old('date_of_birth', $client?->date_of_birth?->format('Y-m-d'))" />

            <x-form.select name="gender" label="Gender">
                <option value="">Not specified</option>
                @foreach (\App\Enums\ClientGender::cases() as $gender)
                    <option value="{{ $gender->value }}" @selected(old('gender', $client?->gender?->value) === $gender->value)>
                        {{ $gender->label() }}
                    </option>
                @endforeach
            </x-form.select>

            <x-form.select name="status" label="Status" required>
                @foreach (\App\Enums\ClientStatus::cases() as $status)
                    <option value="{{ $status->value }}" @selected(old('status', $client?->status?->value ?? 'active') === $status->value)>
                        {{ $status->label() }}
                    </option>
                @endforeach
            </x-form.select>

            <x-form.select name="preferred_coach_id" label="Preferred / main coach">
                <option value="">No preferred coach</option>
                @foreach ($coaches as $coach)
                    <option value="{{ $coach->id }}" @selected((string) old('preferred_coach_id', $client?->preferred_coach_id) === (string) $coach->id)>
                        {{ $coach->trainerLabel() }}
                    </option>
                @endforeach
            </x-form.select>
        </div>
    </x-form.section>

    <x-form.section title="Emergency contact">
        <div class="grid gap-4 sm:grid-cols-2">
            <x-form.input name="emergency_contact_name" label="Emergency contact name" :value="$client?->emergency_contact_name" />
            <x-form.input name="emergency_contact_phone" label="Emergency contact phone" :value="$client?->emergency_contact_phone" />
            <div class="sm:col-span-2">
                <x-form.textarea name="notes" label="Notes" :value="$client?->notes" rows="4" />
            </div>
        </div>
    </x-form.section>
</div>
