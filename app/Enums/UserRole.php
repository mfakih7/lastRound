<?php

namespace App\Enums;

enum UserRole: string
{
    case Admin = 'admin';
    case Coach = 'coach';

    public function label(): string
    {
        return match ($this) {
            self::Admin => 'Admin',
            self::Coach => 'Coach',
        };
    }
}
