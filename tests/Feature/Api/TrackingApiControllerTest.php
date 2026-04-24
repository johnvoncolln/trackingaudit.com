<?php

namespace Tests\Feature\Api;

use App\Jobs\DelegateTrackersJob;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Str;
use Tests\TestCase;

class TrackingApiControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_accepts_valid_tracking_submission(): void
    {
        Queue::fake();

        $user = User::factory()->create(['api_token' => Str::uuid()->toString()]);

        $response = $this->postJson("/api/v1/tracking/{$user->api_token}", [
            'tracking_numbers' => [
                ['tracking_number' => '1Z12345E0205271688'],
                ['tracking_number' => '123456789012'],
            ],
        ]);

        $response->assertOk()
            ->assertJson([
                'message' => 'Accepted 2 tracking numbers for processing.',
                'accepted' => 2,
            ]);

        Queue::assertPushed(DelegateTrackersJob::class);
    }

    public function test_auto_detects_carrier_for_each_tracking_number(): void
    {
        Queue::fake();

        $user = User::factory()->create(['api_token' => Str::uuid()->toString()]);

        $response = $this->postJson("/api/v1/tracking/{$user->api_token}", [
            'tracking_numbers' => [
                ['tracking_number' => '1Z12345E0205271688'],
                ['tracking_number' => '9400111899223377665544'],
                ['tracking_number' => '123456789012'],
            ],
        ]);

        $response->assertOk()->assertJson(['accepted' => 3]);

        Queue::assertPushed(DelegateTrackersJob::class, function ($job) {
            // Verify records were enriched with carrier
            $records = (new \ReflectionProperty($job, 'records'))->getValue($job);

            $carriers = array_column($records, 'carrier');

            return in_array('UPS', $carriers)
                && in_array('USPS', $carriers)
                && in_array('FedEx', $carriers);
        });
    }

    public function test_returns_404_for_invalid_api_token(): void
    {
        $response = $this->postJson('/api/v1/tracking/invalid-token-here', [
            'tracking_numbers' => [
                ['tracking_number' => '1Z12345E0205271688'],
            ],
        ]);

        $response->assertNotFound()
            ->assertJson(['error' => 'Invalid API token.']);
    }

    public function test_validates_tracking_numbers_required(): void
    {
        $user = User::factory()->create(['api_token' => Str::uuid()->toString()]);

        $response = $this->postJson("/api/v1/tracking/{$user->api_token}", []);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['tracking_numbers']);
    }

    public function test_validates_tracking_number_field_required(): void
    {
        $user = User::factory()->create(['api_token' => Str::uuid()->toString()]);

        $response = $this->postJson("/api/v1/tracking/{$user->api_token}", [
            'tracking_numbers' => [
                ['reference_id' => 'REF-001'],
            ],
        ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['tracking_numbers.0.tracking_number']);
    }

    public function test_validates_max_1500_tracking_numbers(): void
    {
        $user = User::factory()->create(['api_token' => Str::uuid()->toString()]);

        $numbers = array_fill(0, 1501, ['tracking_number' => '1Z12345E0205271688']);

        $response = $this->postJson("/api/v1/tracking/{$user->api_token}", [
            'tracking_numbers' => $numbers,
        ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['tracking_numbers']);
    }

    public function test_accepts_optional_metadata(): void
    {
        Queue::fake();

        $user = User::factory()->create(['api_token' => Str::uuid()->toString()]);

        $response = $this->postJson("/api/v1/tracking/{$user->api_token}", [
            'tracking_numbers' => [
                [
                    'tracking_number' => '1Z12345E0205271688',
                    'reference_id' => 'ORDER-123',
                    'reference_name' => 'Customer Order',
                    'recipient_name' => 'John Doe',
                    'recipient_email' => 'john@example.com',
                ],
            ],
        ]);

        $response->assertOk()->assertJson(['accepted' => 1]);

        Queue::assertPushed(DelegateTrackersJob::class);
    }

    public function test_rejects_null_api_token_user(): void
    {
        User::factory()->create(['api_token' => null]);

        $response = $this->postJson('/api/v1/tracking/null', [
            'tracking_numbers' => [
                ['tracking_number' => '1Z12345E0205271688'],
            ],
        ]);

        $response->assertNotFound();
    }
}
