<?php

namespace App\Support;

use App\Models\User;

class Navigation
{
    /**
     * @return array<int, array{label: string, route: string, icon: string, match: string|array<int, string>}>
     */
    public static function items(?User $user): array
    {
        if ($user === null) {
            return [];
        }

        if ($user->isAdmin()) {
            return [
                ['label' => 'Dashboard', 'route' => 'admin.dashboard', 'icon' => 'home', 'match' => 'admin.dashboard'],
                ['label' => 'Clients', 'route' => 'admin.clients.index', 'icon' => 'users', 'match' => 'admin.clients.*'],
                ['label' => 'Coaches', 'route' => 'admin.coaches.index', 'icon' => 'whistle', 'match' => 'admin.coaches.*'],
                ['label' => 'Packages', 'route' => 'admin.packages.index', 'icon' => 'package', 'match' => 'admin.packages.*'],
                ['label' => 'Schedule', 'route' => 'admin.schedule.index', 'icon' => 'calendar', 'match' => 'admin.schedule.*'],
                ['label' => 'Settings', 'route' => 'admin.settings.index', 'icon' => 'settings', 'match' => 'admin.settings.*'],
            ];
        }

        return [
            [
                'label' => 'My Schedule',
                'route' => 'coach.schedule.index',
                'icon' => 'calendar',
                'match' => ['coach.dashboard', 'coach.schedule.*'],
            ],
            [
                'label' => 'My Profile',
                'route' => 'coach.profile.show',
                'icon' => 'user',
                'match' => 'coach.profile.*',
            ],
        ];
    }

    public static function homeRoute(?User $user): string
    {
        if ($user?->isAdmin()) {
            return route('admin.dashboard');
        }

        return route('coach.dashboard');
    }
}
