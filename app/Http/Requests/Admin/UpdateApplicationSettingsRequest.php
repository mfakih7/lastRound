<?php

namespace App\Http\Requests\Admin;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateApplicationSettingsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isAdmin() ?? false;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'app_name' => trim((string) $this->input('app_name')),
            'phone' => $this->filled('phone') ? trim((string) $this->input('phone')) : null,
            'email' => $this->filled('email') ? strtolower(trim((string) $this->input('email'))) : null,
            'address' => $this->filled('address') ? trim((string) $this->input('address')) : null,
            'currency' => strtoupper(trim((string) $this->input('currency', 'USD'))),
            'remove_logo' => $this->boolean('remove_logo'),
        ]);
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'app_name' => ['required', 'string', 'max:80'],
            'phone' => ['nullable', 'string', 'max:40'],
            'email' => ['nullable', 'email', 'max:191'],
            'address' => ['nullable', 'string', 'max:500'],
            'currency' => ['required', 'string', Rule::in(['USD'])],
            'default_session_duration' => ['required', 'integer', 'min:15', 'max:180'],
            'low_session_warning_threshold' => ['required', 'integer', 'min:1', 'max:20'],
            'logo' => ['nullable', 'image', 'mimes:jpeg,jpg,png,webp', 'max:2048'],
            'remove_logo' => ['sometimes', 'boolean'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'app_name.required' => 'Enter the application name.',
            'currency.in' => 'Version 1 supports USD only.',
            'default_session_duration.min' => 'Default session duration must be at least 15 minutes.',
            'default_session_duration.max' => 'Default session duration may not exceed 180 minutes.',
            'low_session_warning_threshold.min' => 'The low-session warning must be at least 1.',
            'logo.image' => 'The logo must be a JPEG, PNG, or WebP file.',
            'logo.mimes' => 'The logo must be a JPEG, PNG, or WebP file.',
            'logo.max' => 'The logo may not be larger than 2 MB.',
        ];
    }
}
