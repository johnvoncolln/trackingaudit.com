<?php

namespace Tests\Feature;

use App\Enums\Carrier;
use App\Enums\TrackerStatus;
use App\Jobs\TrackingImportJob;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class TrackingImportJobTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config()->set('services.ups', [
            'client_id' => 'test',
            'client_secret' => 'test',
            'token_url' => 'https://ups.test/oauth/token',
            'track_url' => 'https://ups.test/track',
        ]);
    }

    public function test_job_dispatches_ups_tracker_and_persists_tracker(): void
    {
        $fixture = json_decode(file_get_contents(base_path('tests/Fixtures/ups_tracking_response.json')), true);

        Http::fake([
            'ups.test/oauth/token' => Http::response(['access_token' => 'abc', 'expires_in' => 3600]),
            'ups.test/track/*' => Http::response($fixture),
        ]);

        $user = User::factory()->create();

        (new TrackingImportJob($user, [
            'tracking_number' => '1Z12345E0205271688',
            'carrier' => Carrier::UPS->value,
        ]))->handle();

        $this->assertDatabaseHas('trackers', [
            'user_id' => $user->id,
            'tracking_number' => '1Z12345E0205271688',
            'status' => TrackerStatus::DELIVERED->value,
        ]);
    }
}
