<?php

namespace Tests\Feature;

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

    public function test_store_tracks_ups_package_via_auto_detection(): void
    {
        $fixture = json_decode(file_get_contents(base_path('tests/Fixtures/ups_tracking_response.json')), true);

        Http::fake([
            'ups.test/oauth/token' => Http::response(['access_token' => 'abc', 'expires_in' => 3600]),
            'ups.test/track/*' => Http::response($fixture),
        ]);

        $user = User::factory()->create();

        $response = $this->actingAs($user)->post(route('tracking.track'), [
            'tracking_number' => '1Z12345E0205271688',
        ]);

        $response->assertOk();
        $this->assertDatabaseHas('trackers', [
            'user_id' => $user->id,
            'tracking_number' => '1Z12345E0205271688',
            'carrier' => 'UPS',
            'status' => TrackerStatus::DELIVERED->value,
        ]);
    }

    public function test_store_tracks_usps_package_via_auto_detection(): void
    {
        $fixture = json_decode(file_get_contents(base_path('tests/Fixtures/usps_tracking_response.json')), true);

        Http::fake([
            'usps.test/oauth/token' => Http::response(['access_token' => 'abc', 'expires_in' => 3600]),
            'usps.test/track/*' => Http::response($fixture),
        ]);

        $user = User::factory()->create();

        $response = $this->actingAs($user)->post(route('tracking.track'), [
            'tracking_number' => '9400111899223377665544',
        ]);

        $response->assertOk();
        $this->assertDatabaseHas('trackers', [
            'tracking_number' => '9400111899223377665544',
            'carrier' => 'USPS',
            'status' => TrackerStatus::DELIVERED->value,
        ]);
    }

    public function test_store_tracks_fedex_package_via_auto_detection(): void
    {
        $fixture = json_decode(file_get_contents(base_path('tests/Fixtures/fedex_tracking_response.json')), true);

        Http::fake([
            'fedex.test/oauth/token' => Http::response(['access_token' => 'abc', 'expires_in' => 3600]),
            'fedex.test/track*' => Http::response($fixture),
        ]);

        $user = User::factory()->create();

        $response = $this->actingAs($user)->post(route('tracking.track'), [
            'tracking_number' => '123456789012',
        ]);

        $response->assertOk();
        $this->assertDatabaseHas('trackers', [
            'tracking_number' => '123456789012',
            'carrier' => 'FedEx',
            'status' => TrackerStatus::DELIVERED->value,
        ]);
    }

    public function test_store_requires_tracking_number(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post(route('tracking.track'), []);

        $response->assertSessionHasErrors(['tracking_number']);
    }
}
