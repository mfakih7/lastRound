<?php

use App\Services\SettingsService;
use App\Support\DateFormat;
use App\Support\Money;

if (! function_exists('setting')) {
    function setting(string $key, mixed $default = null): mixed
    {
        return app(SettingsService::class)->get($key, $default);
    }
}

if (! function_exists('app_name')) {
    function app_name(): string
    {
        return app(SettingsService::class)->appName();
    }
}

if (! function_exists('app_logo_url')) {
    function app_logo_url(): ?string
    {
        return app(SettingsService::class)->logoUrl();
    }
}

if (! function_exists('money')) {
    function money(mixed $amount, ?string $currency = null): string
    {
        return Money::format($amount, $currency);
    }
}

if (! function_exists('format_date')) {
    function format_date(mixed $value): string
    {
        return DateFormat::date($value);
    }
}

if (! function_exists('format_time')) {
    function format_time(mixed $value): string
    {
        return DateFormat::time($value);
    }
}

if (! function_exists('format_datetime')) {
    function format_datetime(mixed $value): string
    {
        return DateFormat::dateTime($value);
    }
}
