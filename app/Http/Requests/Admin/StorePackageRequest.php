<?php

namespace App\Http\Requests\Admin;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class StorePackageRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isAdmin() ?? false;
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:191'],
            'sessions_count' => ['required', 'integer', 'min:1'],
            'price' => ['required', 'numeric', 'min:0', 'decimal:0,2'],
            'description' => ['nullable', 'string', 'max:5000'],
            'is_active' => ['sometimes', 'boolean'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'is_active' => $this->boolean('is_active'),
        ]);
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'name.required' => 'Enter a package name.',
            'sessions_count.required' => 'Enter the number of sessions.',
            'sessions_count.min' => 'A package must include at least 1 session.',
            'price.required' => 'Enter a price.',
            'price.min' => 'Price cannot be negative.',
            'price.decimal' => 'Enter a valid amount with up to 2 decimal places.',
        ];
    }
}
