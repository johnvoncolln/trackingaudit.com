<?php

namespace Tests\Unit\Services\Tracking;

use App\Enums\TrackerStatus;
use App\Models\User;
use App\Services\Api\UspsApiService;
use App\Services\Tracking\UspsTracker;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UspsTrackerTest extends TestCase
{
    use RefreshDatabase;

    public function test_track_parses_usps_response_into_tracker_columns(): void
    {
        $fixture = json_decode(file_get_contents(base_path('tests/Fixtures/usps_tracking_response.json')), true);

        $apiService = $this->createMock(UspsApiService::class);
        $apiService->method('fetchTrackingDetails')->willReturn($fixture);

        $user = User::factory()->create();

        $tracker = (new UspsTracker($apiService))->track($user, [
            'tracking_number' => '9400111899223377665544',
            'carrier' => 'USPS',
        ]);

        $this->assertSame('9400111899223377665544', $tracker->tracking_number);
        $this->assertSame(TrackerStatus::DELIVERED->value, $tracker->status);
        $this->assertSame('NEW YORK, NY, US', $tracker->location);
        $this->assertNotNull($tracker->status_time);
        $this->assertNotNull($tracker->delivery_date);
        $this->assertNotNull($tracker->delivered_date);
        $this->assertDatabaseHas('tracker_data', ['trackers_id' => $tracker->id]);
    }

    public function test_track_does_not_set_delivered_date_when_not_delivered(): void
    {
        $fixture = json_decode(file_get_contents(base_path('tests/Fixtures/usps_tracking_response.json')), true);
        $fixture['trackingEvents'][0]['eventType'] = 'In Transit';

        $apiService = $this->createMock(UspsApiService::class);
        $apiService->method('fetchTrackingDetails')->willReturn($fixture);

        $user = User::factory()->create();

        $tracker = (new UspsTracker($apiService))->track($user, [
            'tracking_number' => '9400111899223377665544',
            'carrier' => 'USPS',
        ]);

        $this->assertSame(TrackerStatus::IN_TRANSIT->value, $tracker->status);
        $this->assertNotNull($tracker->delivery_date);
        $this->assertNull($tracker->delivered_date);
    }
}
