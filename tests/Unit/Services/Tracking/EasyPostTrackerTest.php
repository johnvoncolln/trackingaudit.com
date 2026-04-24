<?php

namespace Tests\Unit\Services\Tracking;

use App\Enums\TrackerStatus;
use App\Models\User;
use App\Services\Api\EasyPostClient;
use App\Services\Tracking\EasyPostTracker;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EasyPostTrackerTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @return array<string, mixed>
     */
    protected function fixture(): array
    {
        $event = json_decode(
            file_get_contents(base_path('tests/Fixtures/easypost/tracker_updated.json')),
            true
        );

        return $event['result'];
    }

    public function test_track_uses_existing_tracker_when_found_by_tracking_code(): void
    {
        $fixture = $this->fixture();

        $client = $this->createMock(EasyPostClient::class);
        $client->expects($this->once())
            ->method('findByTrackingCode')
            ->with('EZ1000000001')
            ->willReturn($fixture);
        $client->expects($this->never())->method('createTracker');

        $user = User::factory()->create();

        $tracker = (new EasyPostTracker($client))->track($user, [
            'tracking_number' => 'EZ1000000001',
            'carrier' => 'USPS',
        ]);

        $this->assertSame('EZ1000000001', $tracker->tracking_number);
        $this->assertSame('trk_9f1c5e', $tracker->easypost_id);
        $this->assertSame($user->id, $tracker->user_id);
        $this->assertSame(TrackerStatus::DELIVERED->value, $tracker->status);
        $this->assertSame('Washington, DC, US', $tracker->location);
        $this->assertNotNull($tracker->status_time);
        $this->assertNotNull($tracker->delivery_date);
        $this->assertNotNull($tracker->delivered_date);
        $this->assertDatabaseHas('tracker_data', ['trackers_id' => $tracker->id]);
    }

    public function test_track_creates_tracker_when_not_yet_registered(): void
    {
        $fixture = $this->fixture();

        $client = $this->createMock(EasyPostClient::class);
        $client->expects($this->once())
            ->method('findByTrackingCode')
            ->willReturn(null);
        $client->expects($this->once())
            ->method('createTracker')
            ->with('EZ1000000001', 'USPS')
            ->willReturn($fixture);

        $user = User::factory()->create();

        $tracker = (new EasyPostTracker($client))->track($user, [
            'tracking_number' => 'EZ1000000001',
            'carrier' => 'USPS',
        ]);

        $this->assertSame('trk_9f1c5e', $tracker->easypost_id);
        $this->assertSame(TrackerStatus::DELIVERED->value, $tracker->status);
    }

    public function test_track_does_not_set_delivered_date_when_not_delivered(): void
    {
        $fixture = $this->fixture();
        $fixture['status'] = 'in_transit';

        $client = $this->createMock(EasyPostClient::class);
        $client->method('findByTrackingCode')->willReturn($fixture);

        $user = User::factory()->create();

        $tracker = (new EasyPostTracker($client))->track($user, [
            'tracking_number' => 'EZ1000000001',
            'carrier' => 'USPS',
        ]);

        $this->assertSame(TrackerStatus::IN_TRANSIT->value, $tracker->status);
        $this->assertNotNull($tracker->delivery_date);
        $this->assertNull($tracker->delivered_date);
    }

    public function test_update_retrieves_by_easypost_id_when_present(): void
    {
        $fixture = $this->fixture();

        $client = $this->createMock(EasyPostClient::class);
        $client->method('findByTrackingCode')->willReturn($fixture);
        $client->expects($this->once())
            ->method('retrieveTracker')
            ->with('trk_9f1c5e')
            ->willReturn($fixture);

        $user = User::factory()->create();
        $subject = new EasyPostTracker($client);

        $tracker = $subject->track($user, [
            'tracking_number' => 'EZ1000000001',
            'carrier' => 'USPS',
        ]);

        $refreshed = $subject->update($tracker);

        $this->assertSame($tracker->id, $refreshed->id);
        $this->assertSame('trk_9f1c5e', $refreshed->easypost_id);
        $this->assertSame(TrackerStatus::DELIVERED->value, $refreshed->status);
    }

    public function test_update_falls_back_to_lookup_when_easypost_id_missing(): void
    {
        $fixture = $this->fixture();

        $client = $this->createMock(EasyPostClient::class);
        $client->expects($this->once())
            ->method('findByTrackingCode')
            ->with('EZ1000000001')
            ->willReturn($fixture);
        $client->expects($this->never())->method('retrieveTracker');

        $user = User::factory()->create();
        $tracker = \App\Models\Tracker::factory()->for($user)->create([
            'tracking_number' => 'EZ1000000001',
            'easypost_id' => null,
            'status' => TrackerStatus::PRE_TRANSIT->value,
        ]);

        $refreshed = (new EasyPostTracker($client))->update($tracker);

        $this->assertSame('trk_9f1c5e', $refreshed->easypost_id);
        $this->assertSame(TrackerStatus::DELIVERED->value, $refreshed->status);
    }
}
