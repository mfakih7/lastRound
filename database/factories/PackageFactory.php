<?php

namespace Database\Factories;

use App\Models\Package;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Package>
 */
class PackageFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->randomElement(['8 Sessions', '12 Sessions', '4 Sessions']),
            'sessions_count' => fake()->randomElement([4, 8, 12]),
            'price' => fake()->randomElement([80, 150, 250]),
            'description' => fake()->optional()->sentence(),
            'is_active' => true,
        ];
    }
}
