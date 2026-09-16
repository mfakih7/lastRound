<?php

namespace Database\Factories;

use App\Enums\UserRole;
use App\Models\CoachProfile;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<User>
 */
class UserFactory extends Factory
{
    protected static ?string $password;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->name(),
            'username' => fake()->unique()->userName(),
            'email' => fake()->unique()->safeEmail(),
            'email_verified_at' => now(),
            'password' => static::$password ??= 'password',
            'role' => UserRole::Coach,
            'is_active' => true,
            'remember_token' => Str::random(10),
        ];
    }

    public function admin(): static
    {
        return $this->state(fn (array $attributes) => [
            'role' => UserRole::Admin,
        ]);
    }

    public function coach(): static
    {
        return $this->state(fn (array $attributes) => [
            'role' => UserRole::Coach,
        ]);
    }

    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }

    public function unverified(): static
    {
        return $this->state(fn (array $attributes) => [
            'email_verified_at' => null,
        ]);
    }

    /**
     * @param  array<string, mixed>  $profile
     */
    public function withProfile(array $profile = []): static
    {
        return $this->afterCreating(function (User $user) use ($profile) {
            if ($user->coachProfile()->exists()) {
                return;
            }

            CoachProfile::factory()->create([
                'user_id' => $user->id,
                ...$profile,
            ]);
        });
    }
}
