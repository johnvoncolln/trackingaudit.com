<?php

namespace Tests\Unit\Services\Tracking;

use App\Enums\TrackerStatus;
use App\Models\User;
use App\Services\Api\FedexApiService;
use App\Services\Tracking\FedexTracker;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FedexTrackerTest extends TestCase
{
    use RefreshDatabase;

    public function test_track_parses_fedex_response_into_tracker_columns(): void
    {
        $fixture = json_decode(file_get_contents(base_path('tests/Fixtures/fedex_tracking_response.json')), true);

        $apiService = $this->createMock(FedexApiService::class);
        $apiService->method('fetchTrackingDetails')->willReturn($fixture);

        $user = User::factory()->create();

        $tracker = (new FedexTracker($apiService))->track($user, [
            'tracking_number' => '123456789012',
            'carrier' => 'FedEx',
        ]);

        $this->assertSame('123456789012', $tracker->tracking_number);
        $this->assertSame(TrackerStatus::DELIVERED->value, $tracker->status);
        $this->assertSame('NEW YORK, NY, US', $tracker->location);
        $this->assertNotNull($tracker->status_time);
        $this->assertDatabaseHas('tracker_data', ['trackers_id' => $tracker->id]);
    }
}
