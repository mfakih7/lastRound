<?php

namespace App\Http\Requests\Admin;

use Illuminate\Validation\Rules\Password;

class UpdateCoachRequest extends StoreCoachRequest
{
    /**
     * @return array<int, mixed>
     */
    protected function passwordRules(): array
    {
        return ['nullable', 'confirmed', Password::defaults()];
    }
}
