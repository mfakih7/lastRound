<?php

namespace Database\Factories;

use App\Enums\TrainingSessionStatus;
use App\Models\Client;
use App\Models\TrainingSession;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<TrainingSession>
 */
class TrainingSessionFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'client_id' => Client::factory(),
            'coach_user_id' => User::factory()->coach(),
            'client_package_id' => null,
            'session_date' => today()->toDateString(),
            'start_time' => '09:00:00',
            'end_time' => '10:00:00',
            'status' => TrainingSessionStatus::Pending,
            'notes' => null,
        ];
    }

    public function done(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => TrainingSessionStatus::Done,
        ]);
    }

    public function cancelled(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => TrainingSessionStatus::Cancelled,
        ]);
    }
}
