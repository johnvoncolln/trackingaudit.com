<?php

namespace App\Services\Tracking;

use App\Enums\Carrier;
use App\Enums\ServiceLevel;

class ServiceLevelMapper
{
    /**
     * @param  array<string, mixed>  $payload  EasyPost tracker payload
     */
    public static function forEasyPost(array $payload): ServiceLevel
    {
        $carrier = strtoupper((string) ($payload['carrier'] ?? ''));
        $service = strtolower((string) ($payload['carrier_detail']['service'] ?? ''));

        if ($service === '') {
            return ServiceLevel::UNKNOWN;
        }

        return match ($carrier) {
            Carrier::UPS->value, 'UPS' => self::mapUpsServiceString($service),
            Carrier::FEDEX->value, 'FEDEX' => self::mapFedexServiceString($service),
            default => ServiceLevel::UNKNOWN,
        };
    }

    /**
     * @param  array<string, mixed>  $upsPayload  UPS raw track API response
     */
    public static function forUps(array $upsPayload): ServiceLevel
    {
        $service = $upsPayload['trackResponse']['shipment'][0]['package'][0]['service']['description']
            ?? $upsPayload['trackResponse']['shipment'][0]['service']['description']
            ?? null;

        if (! is_string($service) || $service === '') {
            return ServiceLevel::UNKNOWN;
        }

        return self::mapUpsServiceString(strtolower($service));
    }

    /**
     * @param  array<string, mixed>  $fedexPayload  FedEx raw track API response
     */
    public static function forFedex(array $fedexPayload): ServiceLevel
    {
        $service = $fedexPayload['output']['completeTrackResults'][0]['trackResults'][0]['serviceDetail']['description']
            ?? null;

        if (! is_string($service) || $service === '') {
            return ServiceLevel::UNKNOWN;
        }

        return self::mapFedexServiceString(strtolower($service));
    }

    protected static function mapUpsServiceString(string $service): ServiceLevel
    {
        return match (true) {
            str_contains($service, 'saturday') && str_contains($service, 'next day') => ServiceLevel::UPS_NEXT_DAY_AIR_SATURDAY,
            str_contains($service, 'next day air saver') => ServiceLevel::UPS_NEXT_DAY_AIR_SAVER,
            str_contains($service, 'next day air') || str_contains($service, 'next day') => ServiceLevel::UPS_NEXT_DAY_AIR,
            str_contains($service, '2nd day') || str_contains($service, 'second day') || str_contains($service, '2 day') => ServiceLevel::UPS_2ND_DAY_AIR,
            str_contains($service, '3 day select') || str_contains($service, '3-day select') || str_contains($service, 'three-day') => ServiceLevel::UPS_3_DAY_SELECT,
            str_contains($service, 'ground') => ServiceLevel::UPS_GROUND,
            default => ServiceLevel::UNKNOWN,
        };
    }

    protected static function mapFedexServiceString(string $service): ServiceLevel
    {
        return match (true) {
            str_contains($service, 'first overnight') => ServiceLevel::FEDEX_FIRST_OVERNIGHT,
            str_contains($service, 'priority overnight') => ServiceLevel::FEDEX_PRIORITY_OVERNIGHT,
            str_contains($service, 'standard overnight') => ServiceLevel::FEDEX_STANDARD_OVERNIGHT,
            str_contains($service, 'express saver') => ServiceLevel::FEDEX_EXPRESS_SAVER,
            str_contains($service, '2day') || str_contains($service, '2 day') => ServiceLevel::FEDEX_2DAY,
            str_contains($service, 'ground') || str_contains($service, 'home delivery') => ServiceLevel::FEDEX_GROUND,
            default => ServiceLevel::UNKNOWN,
        };
    }
}
