<?php

namespace Tests\Feature;

use App\Enums\Carrier;
use App\Enums\TrackerStatus;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class TrackingControllerTest extends TestCase
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

        config()->set('services.usps', [
            'consumer_key' => 'test',
            'consumer_secret' => 'test',
            'token_url' => 'https://usps.test/oauth/token',
            'track_url' => 'https://usps.test/track',
        ]);

        config()->set('services.fedex', [
            'api_key' => 'test',
            'secret_key' => 'test',
            'token_url' => 'https://fedex.test/oauth/token',
            'track_url' => 'https://fedex.test/track',
        ]);
    }

    public function test_store_tracks_ups_package(): void
    {
        $fixture = json_decode(file_get_contents(base_path('tests/Fixtures/ups_tracking_response.json')), true);

        Http::fake([
            'ups.test/oauth/token' => Http::response(['access_token' => 'abc', 'expires_in' => 3600]),
            'ups.test/track/*' => Http::response($fixture),
        ]);

        $user = User::factory()->create();

        $response = $this->actingAs($user)->post(route('tracking.track'), [
            'tracking_number' => '1Z12345E0205271688',
            'carrier' => Carrier::UPS->value,
        ]);

        $response->assertOk();
        $this->assertDatabaseHas('trackers', [
            'user_id' => $user->id,
            'tracking_number' => '1Z12345E0205271688',
            'status' => TrackerStatus::DELIVERED->value,
        ]);
    }

    public function test_store_tracks_usps_package(): void
    {
        $fixture = json_decode(file_get_contents(base_path('tests/Fixtures/usps_tracking_response.json')), true);

        Http::fake([
            'usps.test/oauth/token' => Http::response(['access_token' => 'abc', 'expires_in' => 3600]),
            'usps.test/track/*' => Http::response($fixture),
        ]);

        $user = User::factory()->create();

        $response = $this->actingAs($user)->post(route('tracking.track'), [
            'tracking_number' => '9400111899223377665544',
            'carrier' => Carrier::USPS->value,
        ]);

        $response->assertOk();
        $this->assertDatabaseHas('trackers', [
            'tracking_number' => '9400111899223377665544',
            'status' => TrackerStatus::DELIVERED->value,
        ]);
    }

    public function test_store_tracks_fedex_package(): void
    {
        $fixture = json_decode(file_get_contents(base_path('tests/Fixtures/fedex_tracking_response.json')), true);

        Http::fake([
            'fedex.test/oauth/token' => Http::response(['access_token' => 'abc', 'expires_in' => 3600]),
            'fedex.test/track*' => Http::response($fixture),
        ]);

        $user = User::factory()->create();

        $response = $this->actingAs($user)->post(route('tracking.track'), [
            'tracking_number' => '123456789012',
            'carrier' => Carrier::FEDEX->value,
        ]);

        $response->assertOk();
        $this->assertDatabaseHas('trackers', [
            'tracking_number' => '123456789012',
            'status' => TrackerStatus::DELIVERED->value,
        ]);
    }

    public function test_store_rejects_invalid_tracking_number(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post(route('tracking.track'), [
            'tracking_number' => 'NOT-A-REAL-NUMBER',
            'carrier' => Carrier::UPS->value,
        ]);

        $response->assertSessionHasErrors(['tracking_number']);
    }

    public function test_store_rejects_invalid_fedex_tracking_number(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post(route('tracking.track'), [
            'tracking_number' => 'NOPE',
            'carrier' => Carrier::FEDEX->value,
        ]);

        $response->assertSessionHasErrors(['tracking_number']);
    }
}
