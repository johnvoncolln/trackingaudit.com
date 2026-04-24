<?php

namespace Tests\Unit\Enums;

use App\Enums\Carrier;
use App\Enums\ServiceLevel;
use Tests\TestCase;

class ServiceLevelTest extends TestCase
{
    public function test_transit_business_days_mapping(): void
    {
        $this->assertSame(1, ServiceLevel::UPS_NEXT_DAY_AIR->transitBusinessDays());
        $this->assertSame(1, ServiceLevel::UPS_NEXT_DAY_AIR_SAVER->transitBusinessDays());
        $this->assertSame(1, ServiceLevel::UPS_NEXT_DAY_AIR_SATURDAY->transitBusinessDays());
        $this->assertSame(2, ServiceLevel::UPS_2ND_DAY_AIR->transitBusinessDays());
        $this->assertSame(3, ServiceLevel::UPS_3_DAY_SELECT->transitBusinessDays());
        $this->assertNull(ServiceLevel::UPS_GROUND->transitBusinessDays());

        $this->assertSame(1, ServiceLevel::FEDEX_FIRST_OVERNIGHT->transitBusinessDays());
        $this->assertSame(1, ServiceLevel::FEDEX_PRIORITY_OVERNIGHT->transitBusinessDays());
        $this->assertSame(1, ServiceLevel::FEDEX_STANDARD_OVERNIGHT->transitBusinessDays());
        $this->assertSame(2, ServiceLevel::FEDEX_2DAY->transitBusinessDays());
        $this->assertSame(3, ServiceLevel::FEDEX_EXPRESS_SAVER->transitBusinessDays());
        $this->assertNull(ServiceLevel::FEDEX_GROUND->transitBusinessDays());

        $this->assertNull(ServiceLevel::UNKNOWN->transitBusinessDays());
    }

    public function test_saturday_delivery_only_true_for_ups_saturday(): void
    {
        $this->assertTrue(ServiceLevel::UPS_NEXT_DAY_AIR_SATURDAY->saturdayDelivery());

        foreach (ServiceLevel::cases() as $case) {
            if ($case === ServiceLevel::UPS_NEXT_DAY_AIR_SATURDAY) {
                continue;
            }
            $this->assertFalse($case->saturdayDelivery(), "{$case->value} should not be Saturday delivery");
        }
    }

    public function test_carrier_mapping(): void
    {
        $this->assertSame(Carrier::UPS, ServiceLevel::UPS_NEXT_DAY_AIR->carrier());
        $this->assertSame(Carrier::UPS, ServiceLevel::UPS_GROUND->carrier());
        $this->assertSame(Carrier::FEDEX, ServiceLevel::FEDEX_2DAY->carrier());
        $this->assertSame(Carrier::FEDEX, ServiceLevel::FEDEX_GROUND->carrier());
        $this->assertNull(ServiceLevel::UNKNOWN->carrier());
    }
}
