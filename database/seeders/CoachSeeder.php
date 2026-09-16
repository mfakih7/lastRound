<?php

namespace Database\Seeders;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Database\Seeder;

class CoachSeeder extends Seeder
{
    public function run(): void
    {
        $coaches = [
            [
                'username' => 'marcus',
                'name' => 'Marcus Hale',
                'email' => 'marcus@lastround.test',
                'phone' => '+1 555 0101',
                'notes' => 'Striking and pad-work specialist.',
            ],
            [
                'username' => 'sofia',
                'name' => 'Sofia Reed',
                'email' => 'sofia@lastround.test',
                'phone' => '+1 555 0102',
                'notes' => 'Footwork, conditioning, and beginner classes.',
            ],
        ];

        foreach ($coaches as $coach) {
            $user = User::query()->firstOrNew(['username' => $coach['username']]);
            $user->forceFill([
                'name' => $coach['name'],
                'email' => $coach['email'],
                'password' => 'password',
                'role' => UserRole::Coach,
                'is_active' => true,
            ])->save();

            $user->coachProfile()->updateOrCreate(
                ['user_id' => $user->id],
                [
                    'phone' => $coach['phone'],
                    'notes' => $coach['notes'],
                    'is_available' => true,
                ],
            );
        }
    }
}
