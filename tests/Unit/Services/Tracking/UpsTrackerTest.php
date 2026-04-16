<?php

namespace Tests\Unit\Services\Tracking;

use App\Enums\TrackerStatus;
use App\Models\User;
use App\Services\Api\UpsApiService;
use App\Services\Tracking\UpsTracker;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UpsTrackerTest extends TestCase
{
    use RefreshDatabase;

    public function test_track_parses_ups_response_into_tracker_columns(): void
    {
        $fixture = json_decode(file_get_contents(base_path('tests/Fixtures/ups_tracking_response.json')), true);

        $apiService = $this->createMock(UpsApiService::class);
        $apiService->method('fetchTrackingDetails')->willReturn($fixture);

        $user = User::factory()->create();

        $tracker = (new UpsTracker($apiService))->track($user, [
            'tracking_number' => '1Z12345E0205271688',
            'carrier' => 'UPS',
        ]);

        $this->assertSame('1Z12345E0205271688', $tracker->tracking_number);
        $this->assertSame($user->id, $tracker->user_id);
        $this->assertSame(TrackerStatus::DELIVERED->value, $tracker->status);
        $this->assertSame('New York, NY, US', $tracker->location);
        $this->assertNotNull($tracker->status_time);
        $this->assertDatabaseHas('tracker_data', ['trackers_id' => $tracker->id]);
    }

    public function test_update_refreshes_existing_tracker(): void
    {
        $fixture = json_decode(file_get_contents(base_path('tests/Fixtures/ups_tracking_response.json')), true);

        $apiService = $this->createMock(UpsApiService::class);
        $apiService->method('fetchTrackingDetails')->willReturn($fixture);

        $user = User::factory()->create();
        $subject = new UpsTracker($apiService);

        $tracker = $subject->track($user, [
            'tracking_number' => '1Z12345E0205271688',
            'carrier' => 'UPS',
        ]);

        $refreshed = $subject->update($tracker);

        $this->assertSame($tracker->id, $refreshed->id);
        $this->assertSame(TrackerStatus::DELIVERED->value, $refreshed->status);
    }
}
