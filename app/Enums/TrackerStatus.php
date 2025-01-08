<?php

namespace App\Enums;

enum TrackerStatus: string
{
    /**
     * Package is awaiting pickup or processing
     */
    case PENDING = 'PENDING';

    /**
     * Package is in transit to destination
     */
    case IN_TRANSIT = 'IN_TRANSIT';

    /**
     * Package has been delivered
     */
    case DELIVERED = 'DELIVERED';

    /**
     * Package has encountered a delivery exception
     */
    case EXCEPTION = 'EXCEPTION';

    /**
     * Get a list of all enum values
     *
     * @return array<string>
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }

    /**
     * Get a human-readable label for the status
     *
     * @return string
     */
    public function label(): string
    {
        return match($this) {
            self::PENDING => 'Pending',
            self::IN_TRANSIT => 'In Transit',
            self::DELIVERED => 'Delivered',
            self::EXCEPTION => 'Exception',
        };
    }
}
