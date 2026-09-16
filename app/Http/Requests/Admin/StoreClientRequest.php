<?php

namespace App\Http\Requests\Admin;

use App\Enums\ClientGender;
use App\Enums\ClientStatus;
use App\Models\Client;
use App\Models\User;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreClientRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isAdmin() ?? false;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'preferred_coach_id' => $this->filled('preferred_coach_id') ? $this->input('preferred_coach_id') : null,
            'gender' => $this->filled('gender') ? $this->input('gender') : null,
            'email' => $this->filled('email') ? $this->input('email') : null,
        ]);
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'full_name' => ['required', 'string', 'max:191'],
            'phone' => ['required', 'string', 'max:40'],
            'email' => ['nullable', 'email', 'max:191'],
            'date_of_birth' => ['nullable', 'date', 'before:today'],
            'gender' => ['nullable', Rule::enum(ClientGender::class)],
            'emergency_contact_name' => ['nullable', 'string', 'max:191'],
            'emergency_contact_phone' => ['nullable', 'string', 'max:40'],
            'preferred_coach_id' => ['nullable', 'integer', $this->preferredCoachRule()],
            'notes' => ['nullable', 'string', 'max:5000'],
            'status' => ['required', Rule::enum(ClientStatus::class)],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'full_name.required' => 'Enter the client’s full name.',
            'phone.required' => 'Enter a phone number.',
            'email.email' => 'Enter a valid email address.',
            'date_of_birth.before' => 'Date of birth must be in the past.',
            'preferred_coach_id.exists' => 'Select a valid coach.',
            'status.required' => 'Select a client status.',
        ];
    }

    protected function preferredCoachRule(): \Closure
    {
        return function (string $attribute, mixed $value, \Closure $fail): void {
            if ($value === null || $value === '') {
                return;
            }

            $client = $this->route('client');
            $currentId = $client instanceof Client ? $client->preferred_coach_id : null;

            if ($currentId !== null && (int) $value === (int) $currentId) {
                return;
            }

            $exists = User::query()
                ->assignableCoaches()
                ->where('id', $value)
                ->exists();

            if (! $exists) {
                $fail('Select a valid coach.');
            }
        };
    }
}
