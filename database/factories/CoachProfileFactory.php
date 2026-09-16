<?php

namespace Database\Factories;

use App\Models\CoachProfile;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<CoachProfile>
 */
class CoachProfileFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory()->coach(),
            'phone' => fake()->optional()->numerify('+1 ### ### ####'),
            'profile_image' => null,
            'notes' => fake()->optional()->sentence(),
            'is_available' => true,
        ];
    }
}
