<?php

namespace Database\Factories;

use App\Enums\ClientGender;
use App\Enums\ClientStatus;
use App\Models\Client;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Client>
 */
class ClientFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'full_name' => fake()->name(),
            'phone' => fake()->numerify('+1 ### ### ####'),
            'email' => fake()->optional()->safeEmail(),
            'date_of_birth' => fake()->optional()->date(),
            'gender' => fake()->optional()->randomElement(ClientGender::cases()),
            'emergency_contact_name' => fake()->optional()->name(),
            'emergency_contact_phone' => fake()->optional()->numerify('+1 ### ### ####'),
            'notes' => fake()->optional()->sentence(),
            'status' => ClientStatus::Active,
            'preferred_coach_id' => null,
        ];
    }

    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => ClientStatus::Inactive,
        ]);
    }

    public function forCoach(User $coach): static
    {
        return $this->state(fn (array $attributes) => [
            'preferred_coach_id' => $coach->id,
        ]);
    }
}
