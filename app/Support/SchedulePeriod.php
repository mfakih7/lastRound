<?php

namespace App\Support;

class SchedulePeriod
{
    public const TODAY = 'today';

    public const TOMORROW = 'tomorrow';

    public const WEEK = 'week';

    /**
     * @return array<int, string>
     */
    public static function allowed(): array
    {
        return [self::TODAY, self::TOMORROW, self::WEEK];
    }

    public static function fromRequest(?string $period): string
    {
        $period = strtolower(trim((string) $period));

        return in_array($period, self::allowed(), true) ? $period : self::TODAY;
    }

    public static function label(string $period): string
    {
        return match ($period) {
            self::TOMORROW => 'Tomorrow',
            self::WEEK => 'This Week',
            default => 'Today',
        };
    }
}
