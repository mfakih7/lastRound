<?php

namespace App\Enums;

enum SessionBalanceStatus: string
{
    case Healthy = 'healthy';
    case LowSessions = 'low';
    case RechargeRequired = 'recharge';
    case NoPackage = 'none';

    public function label(): string
    {
        return match ($this) {
            self::Healthy => 'Healthy',
            self::LowSessions => 'Low Sessions',
            self::RechargeRequired => 'Recharge Required',
            self::NoPackage => 'No Package',
        };
    }

    public function tone(): string
    {
        return match ($this) {
            self::Healthy => 'success',
            self::LowSessions => 'warning',
            self::RechargeRequired => 'danger',
            self::NoPackage => 'neutral',
        };
    }
}
