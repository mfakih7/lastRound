<?php

namespace App\Support;

use Carbon\Carbon;
use Illuminate\Http\Request;

class AdminSchedule
{
    public const VIEW_DAY = 'day';

    public const VIEW_WEEK = 'week';

    public static function view(Request $request): string
    {
        return $request->string('view')->toString() === self::VIEW_WEEK
            ? self::VIEW_WEEK
            : self::VIEW_DAY;
    }

    public static function date(Request $request): Carbon
    {
        $value = $request->string('date')->toString();

        if ($value === '') {
            return today();
        }

        try {
            $date = Carbon::createFromFormat('Y-m-d', $value);
        } catch (\Throwable) {
            return today();
        }

        if ($date === false || $date->format('Y-m-d') !== $value) {
            return today();
        }

        return $date->startOfDay();
    }

    /**
     * @return array{start: Carbon, end: Carbon}
     */
    public static function weekBounds(Carbon $date): array
    {
        return [
            'start' => $date->copy()->startOfWeek(Carbon::MONDAY)->startOfDay(),
            'end' => $date->copy()->endOfWeek(Carbon::SUNDAY)->startOfDay(),
        ];
    }
}
