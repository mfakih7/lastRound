<?php

namespace Database\Factories;

use App\Enums\ClientPackageStatus;
use App\Models\Client;
use App\Models\ClientPackage;
use App\Models\Package;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ClientPackage>
 */
class ClientPackageFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'client_id' => Client::factory(),
            'package_id' => Package::factory(),
            'package_name' => '8 Sessions',
            'purchased_sessions' => 8,
            'used_sessions' => 0,
            'price_paid' => '150.00',
            'starts_at' => now()->toDateString(),
            'expires_at' => null,
            'status' => ClientPackageStatus::Active,
        ];
    }

    public function forPackage(Package $package): static
    {
        return $this->state(fn (array $attributes) => [
            'package_id' => $package->id,
            'package_name' => $package->name,
            'purchased_sessions' => $package->sessions_count,
            'price_paid' => $package->price,
        ]);
    }

    public function completed(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => ClientPackageStatus::Completed,
            'used_sessions' => $attributes['purchased_sessions'] ?? 8,
        ]);
    }

    public function cancelled(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => ClientPackageStatus::Cancelled,
        ]);
    }

    public function withUsed(int $used): static
    {
        return $this->state(fn (array $attributes) => [
            'used_sessions' => $used,
        ]);
    }
}
