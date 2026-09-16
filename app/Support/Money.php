<?php

namespace App\Support;

use App\Services\SettingsService;

class Money
{
    public static function format(mixed $amount, ?string $currency = null): string
    {
        $value = number_format((float) $amount, 2);
        $currency = strtoupper($currency ?: (string) app(SettingsService::class)->get('currency', 'USD') ?: 'USD');

        return match ($currency) {
            'USD' => '$'.$value,
            default => $currency.' '.$value,
        };
    }
}
