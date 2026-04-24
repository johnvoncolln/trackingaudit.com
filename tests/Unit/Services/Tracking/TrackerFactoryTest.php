<?php

namespace Tests\Unit\Services\Tracking;

use App\Enums\Carrier;
use App\Services\Tracking\EasyPostTracker;
use App\Services\Tracking\FedexTracker;
use App\Services\Tracking\TrackerFactory;
use App\Services\Tracking\UpsTracker;
use App\Services\Tracking\UspsTracker;
use Tests\TestCase;

class TrackerFactoryTest extends TestCase
{
    public function test_direct_driver_returns_per_carrier_tracker(): void
    {
        config(['tracking.driver' => 'direct']);

        $this->assertInstanceOf(UpsTracker::class, TrackerFactory::make(Carrier::UPS));
        $this->assertInstanceOf(UspsTracker::class, TrackerFactory::make(Carrier::USPS));
        $this->assertInstanceOf(FedexTracker::class, TrackerFactory::make(Carrier::FEDEX));
    }

    public function test_easypost_driver_always_returns_easypost_tracker(): void
    {
        config(['tracking.driver' => 'easypost']);

        $this->assertInstanceOf(EasyPostTracker::class, TrackerFactory::make(Carrier::UPS));
        $this->assertInstanceOf(EasyPostTracker::class, TrackerFactory::make(Carrier::USPS));
        $this->assertInstanceOf(EasyPostTracker::class, TrackerFactory::make(Carrier::FEDEX));
    }
}
