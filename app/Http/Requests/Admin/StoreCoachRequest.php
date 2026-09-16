<?php

namespace App\Http\Requests\Admin;

use App\Models\User;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class StoreCoachRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isAdmin() ?? false;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'username' => Str::lower(trim((string) $this->input('username'))),
            'email' => $this->filled('email') ? Str::lower(trim((string) $this->input('email'))) : null,
            'phone' => $this->filled('phone') ? trim((string) $this->input('phone')) : null,
            'notes' => $this->filled('notes') ? $this->input('notes') : null,
            'is_active' => $this->boolean('is_active'),
            'is_available' => $this->boolean('is_available'),
        ]);
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:191'],
            'username' => [
                'required',
                'string',
                'min:3',
                'max:30',
                'regex:/^[a-z0-9._]+$/',
                Rule::unique('users', 'username')->ignore($this->coachId()),
            ],
            'email' => [
                'nullable',
                'email',
                'max:191',
                Rule::unique('users', 'email')->ignore($this->coachId()),
            ],
            'password' => $this->passwordRules(),
            'is_active' => ['sometimes', 'boolean'],
            'phone' => ['nullable', 'string', 'max:40'],
            'notes' => ['nullable', 'string', 'max:5000'],
            'is_available' => ['sometimes', 'boolean'],
            'profile_image' => ['nullable', 'image', 'mimes:jpeg,jpg,png,webp', 'max:2048'],
        ];
    }

    /**
     * @return array<int, mixed>
     */
    protected function passwordRules(): array
    {
        return ['required', 'confirmed', Password::defaults()];
    }

    protected function coachId(): mixed
    {
        $coach = $this->route('coach');

        return $coach instanceof User ? $coach->id : $coach;
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'name.required' => 'Enter the coach’s name.',
            'username.required' => 'Enter a username.',
            'username.regex' => 'Usernames may only include lowercase letters, numbers, dots, and underscores.',
            'username.unique' => 'That username is already taken.',
            'username.min' => 'Usernames must be at least 3 characters.',
            'username.max' => 'Usernames may not be longer than 30 characters.',
            'email.email' => 'Enter a valid email address.',
            'password.required' => 'Enter a password.',
            'password.confirmed' => 'Password confirmation does not match.',
            'profile_image.image' => 'The profile image must be a JPEG, PNG, or WebP file.',
            'profile_image.mimes' => 'The profile image must be a JPEG, PNG, or WebP file.',
            'profile_image.max' => 'The profile image may not be larger than 2 MB.',
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'is_active' => 'active status',
            'is_available' => 'availability',
        ];
    }
}
