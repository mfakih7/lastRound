<?php

namespace App\Http\Requests\Admin;

use App\Models\Package;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class AssignClientPackageRequest extends FormRequest
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
            'package_id' => ['required', 'integer', Rule::exists('packages', 'id')->where('is_active', true)],
            'purchased_sessions' => ['required', 'integer', 'min:1'],
            'price_paid' => ['required', 'numeric', 'min:0', 'decimal:0,2'],
            'starts_at' => ['required', 'date'],
            'expires_at' => ['nullable', 'date', 'after_or_equal:starts_at'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'package_id.required' => 'Select a package.',
            'package_id.exists' => 'Select an active package from the catalog.',
            'purchased_sessions.required' => 'Enter the number of purchased sessions.',
            'purchased_sessions.min' => 'Purchased sessions must be at least 1.',
            'price_paid.required' => 'Enter the price paid.',
            'price_paid.min' => 'Price paid cannot be negative.',
            'starts_at.required' => 'Enter a start date.',
            'expires_at.after_or_equal' => 'Expiry date cannot be before the start date.',
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            if (! $this->filled('package_id') || $validator->errors()->has('package_id')) {
                return;
            }

            $package = Package::query()->find($this->integer('package_id'));

            if ($package && ! $package->is_active) {
                $validator->errors()->add('package_id', 'Only active packages can be assigned.');
            }
        });
    }
}
