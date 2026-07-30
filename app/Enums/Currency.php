<?php

namespace App\Enums;

enum Currency: string
{
    case USD = 'USD';
    case JOD = 'JOD';
    case EGP = 'EGP';

    public static function values(): array
    {
        return array_map(fn (self $currency) => $currency->value, self::cases());
    }
}
