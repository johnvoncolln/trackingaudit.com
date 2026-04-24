<?php

namespace Tests\Feature\Webhooks;

use App\Enums\TrackerStatus;
use App\Models\Tracker;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Log;
use Tests\TestCase;

class EasyPostWebhookTest extends TestCase
{
    use RefreshDatabase;

    protected string $secret = 'whsec_testsecret';

    protected function setUp(): void
    {
        parent::setUp();

        config(['tracking.easypost.webhook_secret' => $this->secret]);
    }

    protected function fixturePayload(): string
    {
        return file_get_contents(base_path('tests/Fixtures/easypost/tracker_updated.json'));
    }

    protected function signedHeaders(string $body): array
    {
        return [
            'X-Hmac-Signature' => hash_hmac('sha256', $body, $this->secret),
            'Content-Type' => 'application/json',
        ];
    }

    public function test_valid_signature_updates_known_tracker(): void
    {
        $user = User::factory()->create();
        $tracker = Tracker::factory()->for($user)->create([
            'tracking_number' => 'EZ1000000001',
            'easypost_id' => null,
            'status' => TrackerStatus::PRE_TRANSIT->value,
        ]);

        $body = $this->fixturePayload();

        $response = $this->call(
            'POST',
            '/api/webhooks/easypost',
            [],
            [],
            [],
            $this->transformHeadersToServerVars($this->signedHeaders($body)),
            $body,
        );

        $response->assertOk()->assertJson(['received' => true]);

        $tracker->refresh();
        $this->assertSame('trk_9f1c5e', $tracker->easypost_id);
        $this->assertSame(TrackerStatus::DELIVERED->value, $tracker->status);
        $this->assertNotNull($tracker->delivered_date);
        $this->assertSame('Washington, DC, US', $tracker->location);
        $this->assertDatabaseHas('tracker_data', ['trackers_id' => $tracker->id]);
    }

    public function test_invalid_signature_returns_401(): void
    {
        $body = $this->fixturePayload();

        $response = $this->call(
            'POST',
            '/api/webhooks/easypost',
            [],
            [],
            [],
            $this->transformHeadersToServerVars([
                'X-Hmac-Signature' => 'bogus',
                'Content-Type' => 'application/json',
            ]),
            $body,
        );

        $response->assertStatus(401);
    }

    public function test_missing_signature_returns_401(): void
    {
        $body = $this->fixturePayload();

        $response = $this->call(
            'POST',
            '/api/webhooks/easypost',
            [],
            [],
            [],
            $this->transformHeadersToServerVars(['Content-Type' => 'application/json']),
            $body,
        );

        $response->assertStatus(401);
    }

    public function test_unknown_tracking_code_logs_and_returns_200(): void
    {
        Log::shouldReceive('info')
            ->once()
            ->with('easypost.webhook.unknown_tracking_code', \Mockery::on(function ($context) {
                return ($context['tracking_code'] ?? null) === 'EZ1000000001'
                    && ($context['trk_id'] ?? null) === 'trk_9f1c5e';
            }));

        $body = $this->fixturePayload();

        $response = $this->call(
            'POST',
            '/api/webhooks/easypost',
            [],
            [],
            [],
            $this->transformHeadersToServerVars($this->signedHeaders($body)),
            $body,
        );

        $response->assertOk()->assertJson(['received' => true]);
        $this->assertDatabaseCount('trackers', 0);
        $this->assertDatabaseCount('tracker_data', 0);
    }

    public function test_non_tracker_event_is_acknowledged_without_lookup(): void
    {
        $body = json_encode([
            'description' => 'batch.created',
            'result' => ['id' => 'batch_1'],
        ]);

        $response = $this->call(
            'POST',
            '/api/webhooks/easypost',
            [],
            [],
            [],
            $this->transformHeadersToServerVars($this->signedHeaders($body)),
            $body,
        );

        $response->assertOk()->assertJson(['received' => true]);
    }
}
