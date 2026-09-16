<?php

namespace App\Http\Requests\Admin;

use App\Models\User;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class UpdateAdminAccountRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isAdmin() ?? false;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'name' => trim((string) $this->input('name')),
            'username' => Str::lower(trim((string) $this->input('username'))),
            'email' => $this->filled('email') ? Str::lower(trim((string) $this->input('email'))) : null,
        ]);
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $adminId = $this->user()?->id;

        return [
            'name' => ['required', 'string', 'max:191'],
            'username' => [
                'required',
                'string',
                'min:3',
                'max:30',
                'regex:/^[a-z0-9._]+$/',
                Rule::unique(User::class, 'username')->ignore($adminId),
            ],
            'email' => [
                'nullable',
                'email',
                'max:191',
                Rule::unique(User::class, 'email')->ignore($adminId),
            ],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'name.required' => 'Enter your name.',
            'username.required' => 'Enter a username.',
            'username.regex' => 'Usernames may only include lowercase letters, numbers, dots, and underscores.',
            'username.unique' => 'That username is already taken.',
        ];
    }
}
