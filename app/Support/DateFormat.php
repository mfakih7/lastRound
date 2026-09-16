<?php

namespace App\Support;

use Carbon\Carbon;
use Carbon\CarbonInterface;

class DateFormat
{
    public static function date(mixed $value): string
    {
        return self::carbon($value)->format('j M Y');
    }

    public static function time(mixed $value): string
    {
        return self::carbon($value)->format('H:i');
    }

    public static function dateTime(mixed $value): string
    {
        return self::carbon($value)->format('j M Y H:i');
    }

    public static function weekdayDate(mixed $value): string
    {
        return self::carbon($value)->format('D, j M');
    }

    public static function longWeekdayDate(mixed $value): string
    {
        return self::carbon($value)->format('l, j M');
    }

    public static function weekRange(mixed $start, mixed $end): string
    {
        return self::carbon($start)->format('j M').' – '.self::carbon($end)->format('j M Y');
    }

    public static function scheduleDayHeading(mixed $value): string
    {
        $date = self::carbon($value);
        $label = $date->isToday() ? 'Today' : ($date->isTomorrow() ? 'Tomorrow' : $date->format('l'));

        return $label.' — '.$date->format('j M Y');
    }

    protected static function carbon(mixed $value): CarbonInterface
    {
        if ($value instanceof CarbonInterface) {
            return $value;
        }

        return Carbon::parse((string) $value);
    }
}
