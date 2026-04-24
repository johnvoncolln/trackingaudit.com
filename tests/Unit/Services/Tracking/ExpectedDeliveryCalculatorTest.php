<?php

namespace Tests\Unit\Services\Tracking;

use App\Enums\ServiceLevel;
use App\Services\Api\UpsTransitTimeService;
use App\Services\Tracking\ExpectedDeliveryCalculator;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class ExpectedDeliveryCalculatorTest extends TestCase
{
    protected UpsTransitTimeService $transit;

    protected function setUp(): void
    {
        parent::setUp();

        $this->transit = $this->createMock(UpsTransitTimeService::class);
    }

    protected function calculator(): ExpectedDeliveryCalculator
    {
        return new ExpectedDeliveryCalculator($this->transit);
    }

    public function test_monday_ship_plus_two_day_is_wednesday(): void
    {
        $ship = Carbon::parse('2026-04-20'); // Monday
        $result = $this->calculator()->calculate($ship, ServiceLevel::UPS_2ND_DAY_AIR);

        $this->assertSame('2026-04-22', $result->toDateString());
    }

    public function test_friday_ship_plus_next_day_air_saturday_is_saturday(): void
    {
        $ship = Carbon::parse('2026-04-24'); // Friday
        $result = $this->calculator()->calculate($ship, ServiceLevel::UPS_NEXT_DAY_AIR_SATURDAY);

        $this->assertSame('2026-04-25', $result->toDateString());
    }

    public function test_friday_ship_plus_next_day_air_non_saturday_is_monday(): void
    {
        $ship = Carbon::parse('2026-04-24'); // Friday
        $result = $this->calculator()->calculate($ship, ServiceLevel::UPS_NEXT_DAY_AIR);

        $this->assertSame('2026-04-27', $result->toDateString());
    }

    public function test_ups_ground_uses_transit_time_service(): void
    {
        $this->transit->expects($this->once())
            ->method('lookupBusinessDays')
            ->with('10001', '90210')
            ->willReturn(4);

        $ship = Carbon::parse('2026-04-20'); // Monday
        $result = $this->calculator()->calculate($ship, ServiceLevel::UPS_GROUND, '10001', '90210');

        $this->assertSame('2026-04-24', $result->toDateString()); // Mon + 4 BD = Fri
    }

    public function test_ups_ground_returns_null_when_transit_lookup_fails(): void
    {
        $this->transit->method('lookupBusinessDays')->willReturn(null);

        $result = $this->calculator()->calculate(
            Carbon::parse('2026-04-20'),
            ServiceLevel::UPS_GROUND,
            '10001',
            '90210',
        );

        $this->assertNull($result);
    }

    public function test_ups_ground_without_zips_returns_null(): void
    {
        $this->transit->expects($this->never())->method('lookupBusinessDays');

        $result = $this->calculator()->calculate(
            Carbon::parse('2026-04-20'),
            ServiceLevel::UPS_GROUND,
        );

        $this->assertNull($result);
    }

    public function test_fedex_ground_returns_null_under_mvp(): void
    {
        $result = $this->calculator()->calculate(
            Carbon::parse('2026-04-20'),
            ServiceLevel::FEDEX_GROUND,
            '10001',
            '90210',
        );

        $this->assertNull($result);
    }

    public function test_unknown_service_returns_null(): void
    {
        $result = $this->calculator()->calculate(
            Carbon::parse('2026-04-20'),
            ServiceLevel::UNKNOWN,
        );

        $this->assertNull($result);
    }

    public function test_normalizes_ship_date_to_start_of_day(): void
    {
        $ship = Carbon::parse('2026-04-20 23:59:59'); // Monday evening
        $result = $this->calculator()->calculate($ship, ServiceLevel::UPS_NEXT_DAY_AIR);

        $this->assertSame('2026-04-21', $result->toDateString()); // should be Tue, not Wed
    }
}
