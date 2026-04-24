<?php

namespace App\Enums;

enum TrackerStatus: string
{
    case UNKNOWN = 'unknown';
    case PRE_TRANSIT = 'pre_transit';
    case IN_TRANSIT = 'in_transit';
    case OUT_FOR_DELIVERY = 'out_for_delivery';
    case DELIVERED = 'delivered';
    case AVAILABLE_FOR_PICKUP = 'available_for_pickup';
    case RETURN_TO_SENDER = 'return_to_sender';
    case FAILURE = 'failure';
    case CANCELLED = 'cancelled';
    case ERROR = 'error';

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }

    /**
     * @return array<int, self>
     */
    public static function terminalStatuses(): array
    {
        return [
            self::DELIVERED,
            self::RETURN_TO_SENDER,
            self::FAILURE,
            self::CANCELLED,
            self::ERROR,
        ];
    }

    /**
     * @return array<int, self>
     */
    public static function activeStatuses(): array
    {
        return [
            self::UNKNOWN,
            self::PRE_TRANSIT,
            self::IN_TRANSIT,
            self::OUT_FOR_DELIVERY,
            self::AVAILABLE_FOR_PICKUP,
        ];
    }

    public function label(): string
    {
        return match ($this) {
            self::UNKNOWN => 'Unknown',
            self::PRE_TRANSIT => 'Pre-Transit',
            self::IN_TRANSIT => 'In Transit',
            self::OUT_FOR_DELIVERY => 'Out for Delivery',
            self::DELIVERED => 'Delivered',
            self::AVAILABLE_FOR_PICKUP => 'Available for Pickup',
            self::RETURN_TO_SENDER => 'Return to Sender',
            self::FAILURE => 'Delivery Failure',
            self::CANCELLED => 'Cancelled',
            self::ERROR => 'Error',
        };
    }

    public static function fromUps(?string $raw): self
    {
        $normalized = strtolower(trim((string) $raw));

        return match (true) {
            $normalized === '' => self::UNKNOWN,
            str_contains($normalized, 'delivered') => self::DELIVERED,
            str_contains($normalized, 'out for delivery') => self::OUT_FOR_DELIVERY,
            str_contains($normalized, 'pickup') => self::AVAILABLE_FOR_PICKUP,
            str_contains($normalized, 'return') => self::RETURN_TO_SENDER,
            str_contains($normalized, 'cancel') => self::CANCELLED,
            str_contains($normalized, 'label') || str_contains($normalized, 'shipper created') => self::PRE_TRANSIT,
            str_contains($normalized, 'transit')
                || str_contains($normalized, 'departed')
                || str_contains($normalized, 'arrived')
                || str_contains($normalized, 'origin scan')
                || str_contains($normalized, 'destination scan')
                || str_contains($normalized, 'processing') => self::IN_TRANSIT,
            str_contains($normalized, 'exception')
                || str_contains($normalized, 'failure')
                || str_contains($normalized, 'attempted') => self::FAILURE,
            default => self::UNKNOWN,
        };
    }

    public static function fromUsps(?string $raw): self
    {
        $normalized = strtolower(trim((string) $raw));

        return match (true) {
            $normalized === '' => self::UNKNOWN,
            str_contains($normalized, 'delivered') => self::DELIVERED,
            str_contains($normalized, 'out for delivery') => self::OUT_FOR_DELIVERY,
            str_contains($normalized, 'available for pickup')
                || str_contains($normalized, 'awaiting delivery scan') => self::AVAILABLE_FOR_PICKUP,
            str_contains($normalized, 'return') => self::RETURN_TO_SENDER,
            str_contains($normalized, 'cancel') => self::CANCELLED,
            str_contains($normalized, 'pre-shipment')
                || str_contains($normalized, 'label created')
                || str_contains($normalized, 'acceptance pending') => self::PRE_TRANSIT,
            str_contains($normalized, 'in transit')
                || str_contains($normalized, 'accepted')
                || str_contains($normalized, 'arrived')
                || str_contains($normalized, 'departed')
                || str_contains($normalized, 'processed') => self::IN_TRANSIT,
            str_contains($normalized, 'failure')
                || str_contains($normalized, 'undeliverable')
                || str_contains($normalized, 'alert') => self::FAILURE,
            default => self::UNKNOWN,
        };
    }

    public static function fromEasyPost(?string $raw): self
    {
        return match (strtolower(trim((string) $raw))) {
            'pre_transit' => self::PRE_TRANSIT,
            'in_transit' => self::IN_TRANSIT,
            'out_for_delivery' => self::OUT_FOR_DELIVERY,
            'delivered' => self::DELIVERED,
            'available_for_pickup' => self::AVAILABLE_FOR_PICKUP,
            'return_to_sender' => self::RETURN_TO_SENDER,
            'failure' => self::FAILURE,
            'cancelled' => self::CANCELLED,
            'error' => self::ERROR,
            default => self::UNKNOWN,
        };
    }

    public static function fromFedex(?string $raw): self
    {
        $normalized = strtolower(trim((string) $raw));

        return match (true) {
            $normalized === '' => self::UNKNOWN,
            str_contains($normalized, 'delivered') => self::DELIVERED,
            str_contains($normalized, 'on fedex vehicle for delivery')
                || str_contains($normalized, 'out for delivery') => self::OUT_FOR_DELIVERY,
            str_contains($normalized, 'hold at location')
                || str_contains($normalized, 'ready for pickup') => self::AVAILABLE_FOR_PICKUP,
            str_contains($normalized, 'returned') || str_contains($normalized, 'return to') => self::RETURN_TO_SENDER,
            str_contains($normalized, 'cancel') => self::CANCELLED,
            str_contains($normalized, 'shipment information sent')
                || str_contains($normalized, 'label created')
                || str_contains($normalized, 'initiated') => self::PRE_TRANSIT,
            str_contains($normalized, 'in transit')
                || str_contains($normalized, 'arrived')
                || str_contains($normalized, 'departed')
                || str_contains($normalized, 'at local fedex facility')
                || str_contains($normalized, 'picked up') => self::IN_TRANSIT,
            str_contains($normalized, 'exception')
                || str_contains($normalized, 'delivery exception')
                || str_contains($normalized, 'delay') => self::FAILURE,
            default => self::UNKNOWN,
        };
    }
}
