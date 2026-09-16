<?php

namespace App\Enums;

enum TrainingSessionStatus: string
{
    case Pending = 'pending';
    case Done = 'done';
    case Cancelled = 'cancelled';

    public function label(): string
    {
        return match ($this) {
            self::Pending => 'Pending',
            self::Done => 'Done',
            self::Cancelled => 'Cancelled',
        };
    }
}
