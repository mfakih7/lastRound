<?php

namespace App\Http\Requests\Admin;

use App\Enums\TrainingSessionStatus;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Validation\Rule;

class UpdateTrainingSessionRequest extends StoreTrainingSessionRequest
{
    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            ...parent::rules(),
            'status' => ['required', Rule::enum(TrainingSessionStatus::class)],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            ...parent::messages(),
            'status.required' => 'Select a session status.',
        ];
    }
}
