<?php

namespace App\Enums;

enum ServiceLevel: string
{
    case UPS_NEXT_DAY_AIR = 'ups_next_day_air';
    case UPS_NEXT_DAY_AIR_SAVER = 'ups_next_day_air_saver';
    case UPS_NEXT_DAY_AIR_SATURDAY = 'ups_next_day_air_saturday';
    case UPS_2ND_DAY_AIR = 'ups_2nd_day_air';
    case UPS_3_DAY_SELECT = 'ups_3_day_select';
    case UPS_GROUND = 'ups_ground';

    case FEDEX_FIRST_OVERNIGHT = 'fedex_first_overnight';
    case FEDEX_PRIORITY_OVERNIGHT = 'fedex_priority_overnight';
    case FEDEX_STANDARD_OVERNIGHT = 'fedex_standard_overnight';
    case FEDEX_2DAY = 'fedex_2day';
    case FEDEX_EXPRESS_SAVER = 'fedex_express_saver';
    case FEDEX_GROUND = 'fedex_ground';

    case UNKNOWN = 'unknown';

    public function transitBusinessDays(): ?int
    {
        return match ($this) {
            self::UPS_NEXT_DAY_AIR,
            self::UPS_NEXT_DAY_AIR_SAVER,
            self::UPS_NEXT_DAY_AIR_SATURDAY,
            self::FEDEX_FIRST_OVERNIGHT,
            self::FEDEX_PRIORITY_OVERNIGHT,
            self::FEDEX_STANDARD_OVERNIGHT => 1,
            self::UPS_2ND_DAY_AIR,
            self::FEDEX_2DAY => 2,
            self::UPS_3_DAY_SELECT,
            self::FEDEX_EXPRESS_SAVER => 3,
            self::UPS_GROUND,
            self::FEDEX_GROUND,
            self::UNKNOWN => null,
        };
    }

    public function saturdayDelivery(): bool
    {
        return $this === self::UPS_NEXT_DAY_AIR_SATURDAY;
    }

    public function carrier(): ?Carrier
    {
        return match ($this) {
            self::UPS_NEXT_DAY_AIR,
            self::UPS_NEXT_DAY_AIR_SAVER,
            self::UPS_NEXT_DAY_AIR_SATURDAY,
            self::UPS_2ND_DAY_AIR,
            self::UPS_3_DAY_SELECT,
            self::UPS_GROUND => Carrier::UPS,
            self::FEDEX_FIRST_OVERNIGHT,
            self::FEDEX_PRIORITY_OVERNIGHT,
            self::FEDEX_STANDARD_OVERNIGHT,
            self::FEDEX_2DAY,
            self::FEDEX_EXPRESS_SAVER,
            self::FEDEX_GROUND => Carrier::FEDEX,
            self::UNKNOWN => null,
        };
    }
}
