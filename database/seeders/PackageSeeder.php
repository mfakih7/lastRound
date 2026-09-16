<?php

namespace Database\Seeders;

use App\Models\Package;
use Illuminate\Database\Seeder;

class PackageSeeder extends Seeder
{
    public function run(): void
    {
        $packages = [
            [
                'name' => '8 Sessions',
                'sessions_count' => 8,
                'price' => 150,
                'description' => 'Standard 8-session training package.',
            ],
            [
                'name' => '12 Sessions',
                'sessions_count' => 12,
                'price' => 250,
                'description' => 'Standard 12-session training package.',
            ],
        ];

        foreach ($packages as $package) {
            Package::query()->updateOrCreate(
                ['name' => $package['name']],
                [
                    'sessions_count' => $package['sessions_count'],
                    'price' => $package['price'],
                    'description' => $package['description'],
                    'is_active' => true,
                ],
            );
        }
    }
}
