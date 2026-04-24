<?php

namespace Tests\Unit\Services\Api;

use App\Services\Api\UpsApiService;
use App\Services\Api\UpsTransitTimeService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class UpsTransitTimeServiceTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config([
            'services.ups.transit_url' => 'https://onlinetools.ups.example/api/shipments/v1/transittimes',
        ]);

        Cache::flush();
    }

    protected function service(): UpsTransitTimeService
    {
        $ups = $this->createMock(UpsApiService::class);
        $ups->method('getAccessToken')->willReturn('fake-token');

        return new UpsTransitTimeService($ups);
    }

    public function test_returns_business_transit_days_for_ground_service(): void
    {
        Http::fake([
            '*transittimes*' => Http::response([
                'emsResponse' => [
                    'services' => [
                        ['serviceLevel' => '2DA', 'businessTransitDays' => '2'],
                        ['serviceLevel' => 'GND', 'businessTransitDays' => '4'],
                    ],
                ],
            ], 200),
        ]);

        $days = $this->service()->lookupBusinessDays('10001', '90210');

        $this->assertSame(4, $days);
        Http::assertSentCount(1);
    }

    public function test_caches_result_and_does_not_re_request(): void
    {
        Http::fake([
            '*transittimes*' => Http::response([
                'emsResponse' => [
                    'services' => [
                        ['serviceLevel' => 'GND', 'businessTransitDays' => '3'],
                    ],
                ],
            ], 200),
        ]);

        $service = $this->service();

        $this->assertSame(3, $service->lookupBusinessDays('10001', '90210'));
        $this->assertSame(3, $service->lookupBusinessDays('10001', '90210'));

        Http::assertSentCount(1);
    }

    public function test_returns_null_on_http_failure(): void
    {
        Http::fake([
            '*transittimes*' => Http::response([], 500),
        ]);

        $this->assertNull($this->service()->lookupBusinessDays('10001', '90210'));
    }

    public function test_returns_null_when_no_ground_service_in_response(): void
    {
        Http::fake([
            '*transittimes*' => Http::response([
                'emsResponse' => [
                    'services' => [
                        ['serviceLevel' => '2DA', 'businessTransitDays' => '2'],
                    ],
                ],
            ], 200),
        ]);

        $this->assertNull($this->service()->lookupBusinessDays('10001', '90210'));
    }

    public function test_returns_null_when_transit_url_not_configured(): void
    {
        config(['services.ups.transit_url' => null]);

        Http::fake(); // would fail the test if called

        $this->assertNull($this->service()->lookupBusinessDays('10001', '90210'));

        Http::assertNothingSent();
    }
}
