<?php

namespace Database\Seeders;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Database\Seeder;

class AdminUserSeeder extends Seeder
{
    public function run(): void
    {
        $admin = User::query()->firstOrNew(['username' => 'admin']);
        $admin->forceFill([
            'name' => 'Head Coach',
            'email' => 'admin@lastround.test',
            'password' => 'password',
            'role' => UserRole::Admin,
            'is_active' => true,
        ])->save();

        $admin->coachProfile()->updateOrCreate(
            ['user_id' => $admin->id],
            [
                'phone' => null,
                'notes' => 'Head coach / administrator. Can also take training sessions.',
                'is_available' => true,
            ],
        );
    }
}
