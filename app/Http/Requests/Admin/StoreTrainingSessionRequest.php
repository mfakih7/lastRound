<?php

namespace App\Http\Requests\Admin;

use App\Models\Client;
use App\Models\User;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreTrainingSessionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isAdmin() ?? false;
    }

    protected function prepareForValidation(): void
    {
        foreach (['start_time', 'end_time'] as $field) {
            $value = (string) $this->input($field);

            if (preg_match('/^\d{2}:\d{2}:\d{2}$/', $value) === 1) {
                $this->merge([$field => substr($value, 0, 5)]);
            }
        }
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'client_id' => ['required', 'integer', Rule::exists(Client::class, 'id')],
            'coach_user_id' => ['required', 'integer', Rule::exists(User::class, 'id')],
            'session_date' => ['required', 'date'],
            'start_time' => ['required', 'date_format:H:i'],
            'end_time' => ['required', 'date_format:H:i', 'after:start_time'],
            'notes' => ['nullable', 'string', 'max:5000'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'client_id.required' => 'Select a client.',
            'coach_user_id.required' => 'Select a coach.',
            'session_date.required' => 'Select a session date.',
            'start_time.required' => 'Enter a start time.',
            'end_time.required' => 'Enter an end time.',
            'end_time.after' => 'End time must be after start time.',
        ];
    }
}
