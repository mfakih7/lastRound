<?php

namespace Database\Seeders;

use App\Models\Setting;
use Illuminate\Database\Seeder;

class SettingSeeder extends Seeder
{
    public function run(): void
    {
        $settings = [
            ['key' => 'app_name', 'value' => 'LastRound', 'type' => 'string'],
            ['key' => 'logo', 'value' => null, 'type' => 'string'],
            ['key' => 'phone', 'value' => null, 'type' => 'string'],
            ['key' => 'email', 'value' => 'admin@lastround.test', 'type' => 'string'],
            ['key' => 'address', 'value' => null, 'type' => 'string'],
            ['key' => 'currency', 'value' => 'USD', 'type' => 'string'],
            ['key' => 'default_session_duration', 'value' => '60', 'type' => 'integer'],
            ['key' => 'low_session_warning_threshold', 'value' => '2', 'type' => 'integer'],
        ];

        foreach ($settings as $setting) {
            Setting::query()->updateOrCreate(
                ['key' => $setting['key']],
                [
                    'value' => $setting['value'],
                    'type' => $setting['type'],
                ],
            );
        }
    }
}
