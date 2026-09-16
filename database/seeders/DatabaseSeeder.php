<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            SettingSeeder::class,
            AdminUserSeeder::class,
            CoachSeeder::class,
            PackageSeeder::class,
            DemoDataSeeder::class,
        ]);
    }
}
