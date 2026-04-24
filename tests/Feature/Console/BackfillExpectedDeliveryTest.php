<?php

namespace Tests\Feature\Console;

use App\Enums\Carrier;
use App\Enums\ServiceLevel;
use App\Jobs\UpdateTrackerJob;
use App\Models\Tracker;
use App\Models\TrackerData;
use App\Models\User;
use App\Services\Api\UpsTransitTimeService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class BackfillExpectedDeliveryTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $transit = $this->createMock(UpsTransitTimeService::class);
        $transit->method('lookupBusinessDays')->willReturn(3);
        $this->app->instance(UpsTransitTimeService::class, $transit);
    }

    public function test_populates_service_level_columns_for_ups_next_day_air(): void
    {
        $user = User::factory()->create();
        $tracker = Tracker::factory()->create([
            'user_id' => $user->id,
            'carrier' => Carrier::UPS->value,
            'service_code' => null,
            'expected_delivery_date' => null,
        ]);

        TrackerData::create([
            'trackers_id' => $tracker->id,
            'data' => [
                'carrier' => 'UPS',
                'carrier_detail' => [
                    'service' => 'Next Day Air',
                    'origin_location' => 'NEW YORK NY 10001',
                    'destination_location' => 'WASHINGTON DC 20001',
                ],
                'tracking_details' => [
                    ['status' => 'pre_transit', 'datetime' => '2026-04-20T10:00:00Z'],
                    ['status' => 'in_transit', 'datetime' => '2026-04-21T08:00:00Z'],
                ],
            ],
        ]);

        $this->artisan('tracking:backfill-expected-delivery')
            ->assertSuccessful();

        $tracker->refresh();
        $this->assertSame(ServiceLevel::UPS_NEXT_DAY_AIR->value, $tracker->service_code);
        $this->assertSame('Next Day Air', $tracker->service_type);
        $this->assertSame('2026-04-20', $tracker->ship_date->toDateString());
        $this->assertSame('2026-04-21', $tracker->expected_delivery_date->toDateString());
        $this->assertSame('10001', $tracker->origin_zip);
        $this->assertSame('20001', $tracker->destination_zip);
    }

    public function test_skips_trackers_without_payload(): void
    {
        $user = User::factory()->create();
        Tracker::factory()->create([
            'user_id' => $user->id,
            'carrier' => Carrier::UPS->value,
            'service_code' => null,
        ]);

        $this->artisan('tracking:backfill-expected-delivery')
            ->expectsOutputToContain('Skipped (no payload): 1')
            ->assertSuccessful();
    }

    public function test_dry_run_does_not_persist(): void
    {
        $user = User::factory()->create();
        $tracker = Tracker::factory()->create([
            'user_id' => $user->id,
            'carrier' => Carrier::UPS->value,
            'service_code' => null,
            'expected_delivery_date' => null,
        ]);
        TrackerData::create([
            'trackers_id' => $tracker->id,
            'data' => [
                'carrier' => 'UPS',
                'carrier_detail' => ['service' => '2nd Day Air'],
                'tracking_details' => [['status' => 'pre_transit', 'datetime' => '2026-04-20T10:00:00Z']],
            ],
        ]);

        $this->artisan('tracking:backfill-expected-delivery', ['--dry-run' => true])
            ->assertSuccessful();

        $tracker->refresh();
        $this->assertNull($tracker->service_code);
        $this->assertNull($tracker->expected_delivery_date);
    }

    public function test_ignores_usps_trackers(): void
    {
        $user = User::factory()->create();
        Tracker::factory()->create([
            'user_id' => $user->id,
            'carrier' => Carrier::USPS->value,
        ]);

        $this->artisan('tracking:backfill-expected-delivery')
            ->expectsOutputToContain('Processed: 0')
            ->assertSuccessful();
    }

    public function test_refresh_flag_queues_update_job_for_legacy_payload(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $legacy = Tracker::factory()->create([
            'user_id' => $user->id,
            'carrier' => Carrier::UPS->value,
        ]);
        TrackerData::create([
            'trackers_id' => $legacy->id,
            'data' => [
                'trackResponse' => [
                    'shipment' => [[
                        'package' => [[
                            'service' => ['description' => 'UPS Ground'],
                        ]],
                    ]],
                ],
            ],
        ]);

        $this->artisan('tracking:backfill-expected-delivery', ['--refresh' => true])
            ->expectsOutputToContain('Queued refresh: 1')
            ->assertSuccessful();

        Queue::assertPushed(UpdateTrackerJob::class, 1);
    }

    public function test_refresh_flag_still_computes_inline_for_modern_payload(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $modern = Tracker::factory()->create([
            'user_id' => $user->id,
            'carrier' => Carrier::UPS->value,
            'service_code' => null,
            'expected_delivery_date' => null,
        ]);
        TrackerData::create([
            'trackers_id' => $modern->id,
            'data' => [
                'carrier' => 'UPS',
                'carrier_detail' => [
                    'service' => 'Next Day Air',
                    'origin_location' => 'NEW YORK NY 10001',
                    'destination_location' => 'WASHINGTON DC 20001',
                ],
                'tracking_details' => [
                    ['status' => 'pre_transit', 'datetime' => '2026-04-20T10:00:00Z'],
                ],
            ],
        ]);

        $this->artisan('tracking:backfill-expected-delivery', ['--refresh' => true])
            ->assertSuccessful();

        Queue::assertNotPushed(UpdateTrackerJob::class);

        $modern->refresh();
        $this->assertSame(ServiceLevel::UPS_NEXT_DAY_AIR->value, $modern->service_code);
        $this->assertNotNull($modern->expected_delivery_date);
    }
}
