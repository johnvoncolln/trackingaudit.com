<?php

namespace App\Services\Tracking;

use App\Enums\Carrier;
use App\Services\Api\FedexApiService;
use App\Services\Api\UpsApiService;
use App\Services\Api\UspsApiService;
use InvalidArgumentException;

class TrackerFactory
{
    public static function make(string|Carrier $carrier): CarrierTracker
    {
        if (config('tracking.driver') === 'easypost') {
            return app(EasyPostTracker::class);
        }

        $carrier = $carrier instanceof Carrier
            ? $carrier
            : (Carrier::tryFrom($carrier) ?? throw new InvalidArgumentException("Unknown carrier: {$carrier}"));

        return match ($carrier) {
            Carrier::UPS => new UpsTracker(new UpsApiService),
            Carrier::USPS => new UspsTracker(new UspsApiService),
            Carrier::FEDEX => new FedexTracker(new FedexApiService),
        };
    }
}
