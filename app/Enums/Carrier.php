<?php

namespace App\Enums;

enum Carrier: string
{
    case UPS = 'UPS';
    case FEDEX = 'FedEx';
    case USPS = 'USPS';

    /**
     * Get a list of all enum values.
     *
     * @return array
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
