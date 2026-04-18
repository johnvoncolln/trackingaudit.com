<?php

namespace Tests\Feature;

use App\Enums\Carrier;
use App\Enums\TrackerStatus;
use App\Livewire\Dashboard;
use App\Models\Tracker;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class DashboardTest extends TestCase
{
    use RefreshDatabase;

    public function test_dashboard_is_accessible_by_authenticated_user(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get('/dashboard')
            ->assertStatus(200)
            ->assertSeeLivewire(Dashboard::class);
    }

    public function test_dashboard_redirects_unauthenticated_user(): void
    {
        $this->get('/dashboard')
            ->assertRedirect('/login');
    }

    public function test_dashboard_shows_total_shipment_count_for_period(): void
    {
        $user = User::factory()->create();

        Tracker::factory()->count(3)->create(['user_id' => $user->id]);

        Tracker::factory()->create([
            'user_id' => $user->id,
            'created_at' => now()->subDays(45),
        ]);

        Livewire::actingAs($user)
            ->test(Dashboard::class)
            ->assertViewHas('totalShipments', 3);
    }

    public function test_dashboard_shows_delivered_count(): void
    {
        $user = User::factory()->create();

        Tracker::factory()->count(2)->delivered()->create(['user_id' => $user->id]);
        Tracker::factory()->active()->create(['user_id' => $user->id]);

        Livewire::actingAs($user)
            ->test(Dashboard::class)
            ->assertViewHas('deliveredCount', 2);
    }

    public function test_dashboard_shows_en_route_count(): void
    {
        $user = User::factory()->create();

        Tracker::factory()->count(3)->active()->create(['user_id' => $user->id]);
        Tracker::factory()->delivered()->create(['user_id' => $user->id]);

        Livewire::actingAs($user)
            ->test(Dashboard::class)
            ->assertViewHas('enRouteCount', 3);
    }

    public function test_dashboard_shows_late_count(): void
    {
        $user = User::factory()->create();

        Tracker::factory()->count(2)->late()->create(['user_id' => $user->id]);
        Tracker::factory()->active()->create(['user_id' => $user->id]);
        Tracker::factory()->delivered()->create(['user_id' => $user->id]);

        Livewire::actingAs($user)
            ->test(Dashboard::class)
            ->assertViewHas('lateCount', 2);
    }

    public function test_dashboard_shows_needs_attention_count(): void
    {
        $user = User::factory()->create();

        Tracker::factory()->create([
            'user_id' => $user->id,
            'status' => TrackerStatus::FAILURE->value,
        ]);
        Tracker::factory()->create([
            'user_id' => $user->id,
            'status' => TrackerStatus::RETURN_TO_SENDER->value,
        ]);
        Tracker::factory()->create([
            'user_id' => $user->id,
            'status' => TrackerStatus::ERROR->value,
        ]);
        Tracker::factory()->active()->create(['user_id' => $user->id]);

        Livewire::actingAs($user)
            ->test(Dashboard::class)
            ->assertViewHas('needsAttentionCount', 3);
    }

    public function test_dashboard_shows_on_time_delivery_rate(): void
    {
        $user = User::factory()->create();

        // 2 on-time deliveries (delivered_date <= delivery_date)
        Tracker::factory()->count(2)->create([
            'user_id' => $user->id,
            'status' => TrackerStatus::DELIVERED->value,
            'delivery_date' => now()->subDay(),
            'delivered_date' => now()->subDays(2),
        ]);

        // 1 late delivery (delivered_date > delivery_date)
        Tracker::factory()->deliveredLate()->create(['user_id' => $user->id]);

        Livewire::actingAs($user)
            ->test(Dashboard::class)
            ->assertViewHas('onTimeRate', 66.7);
    }

    public function test_on_time_rate_compares_dates_not_datetimes(): void
    {
        $user = User::factory()->create();

        // Delivered same day but later in the day (14:30 vs 00:00)
        Tracker::factory()->create([
            'user_id' => $user->id,
            'status' => TrackerStatus::DELIVERED->value,
            'delivery_date' => '2026-04-10 00:00:00',
            'delivered_date' => '2026-04-10 14:30:00',
        ]);

        Livewire::actingAs($user)
            ->test(Dashboard::class)
            ->assertViewHas('onTimeRate', 100.0);
    }

    public function test_dashboard_on_time_rate_is_null_when_no_eligible_shipments(): void
    {
        $user = User::factory()->create();

        Tracker::factory()->active()->create(['user_id' => $user->id]);

        Livewire::actingAs($user)
            ->test(Dashboard::class)
            ->assertViewHas('onTimeRate', null)
            ->assertSee('N/A');
    }

    public function test_dashboard_shows_average_delivery_days(): void
    {
        $user = User::factory()->create();

        // Tracker delivered in 2 days
        Tracker::factory()->create([
            'user_id' => $user->id,
            'status' => TrackerStatus::DELIVERED->value,
            'created_at' => now()->subDays(4),
            'delivered_date' => now()->subDays(2),
            'delivery_date' => now()->subDays(1),
        ]);

        // Tracker delivered in 4 days
        Tracker::factory()->create([
            'user_id' => $user->id,
            'status' => TrackerStatus::DELIVERED->value,
            'created_at' => now()->subDays(6),
            'delivered_date' => now()->subDays(2),
            'delivery_date' => now()->subDays(1),
        ]);

        // Average should be 3.0 days
        Livewire::actingAs($user)
            ->test(Dashboard::class)
            ->assertViewHas('avgDeliveryDays', 3.0);
    }

    public function test_avg_delivery_days_excludes_retroactive_imports(): void
    {
        $user = User::factory()->create();

        // Normal flow: created 4 days ago, delivered 2 days ago = 2 days
        Tracker::factory()->create([
            'user_id' => $user->id,
            'status' => TrackerStatus::DELIVERED->value,
            'created_at' => now()->subDays(4),
            'delivered_date' => now()->subDays(2),
        ]);

        // Retroactive import: created today, but delivered 3 days ago
        Tracker::factory()->create([
            'user_id' => $user->id,
            'status' => TrackerStatus::DELIVERED->value,
            'created_at' => now(),
            'delivered_date' => now()->subDays(3),
        ]);

        Livewire::actingAs($user)
            ->test(Dashboard::class)
            ->assertViewHas('avgDeliveryDays', 2.0);
    }

    public function test_dashboard_avg_delivery_days_is_null_when_no_deliveries(): void
    {
        $user = User::factory()->create();

        Tracker::factory()->active()->create(['user_id' => $user->id]);

        Livewire::actingAs($user)
            ->test(Dashboard::class)
            ->assertViewHas('avgDeliveryDays', null);
    }

    public function test_dashboard_shows_carrier_breakdown(): void
    {
        $user = User::factory()->create();

        Tracker::factory()->count(3)->create([
            'user_id' => $user->id,
            'carrier' => Carrier::UPS->value,
        ]);
        Tracker::factory()->count(2)->create([
            'user_id' => $user->id,
            'carrier' => Carrier::FEDEX->value,
        ]);
        Tracker::factory()->create([
            'user_id' => $user->id,
            'carrier' => Carrier::USPS->value,
        ]);

        Livewire::actingAs($user)
            ->test(Dashboard::class)
            ->assertSee('UPS')
            ->assertSee('FedEx')
            ->assertSee('USPS');
    }

    public function test_dashboard_shows_recent_shipments_limited_to_five(): void
    {
        $user = User::factory()->create();

        $trackers = Tracker::factory()->count(7)->create(['user_id' => $user->id]);

        Livewire::actingAs($user)
            ->test(Dashboard::class)
            ->assertViewHas('recentShipments', function ($shipments) {
                return $shipments->count() === 5;
            });
    }

    public function test_dashboard_scopes_data_to_authenticated_user(): void
    {
        $userA = User::factory()->create();
        $userB = User::factory()->create();

        Tracker::factory()->count(3)->create(['user_id' => $userA->id]);
        Tracker::factory()->count(5)->create(['user_id' => $userB->id]);

        Livewire::actingAs($userA)
            ->test(Dashboard::class)
            ->assertViewHas('totalShipments', 3);
    }

    public function test_dashboard_excludes_shipments_outside_date_range(): void
    {
        $user = User::factory()->create();

        // Inside range
        Tracker::factory()->count(2)->create(['user_id' => $user->id]);

        // Outside range
        Tracker::factory()->count(3)->create([
            'user_id' => $user->id,
            'created_at' => now()->subDays(45),
        ]);

        Livewire::actingAs($user)
            ->test(Dashboard::class)
            ->assertViewHas('totalShipments', 2);
    }

    public function test_recent_shipments_links_to_show_page(): void
    {
        $user = User::factory()->create();

        $tracker = Tracker::factory()->create(['user_id' => $user->id]);

        Livewire::actingAs($user)
            ->test(Dashboard::class)
            ->assertSee($tracker->tracking_number)
            ->assertSeeHtml(route('tracking.show', $tracker));
    }

    public function test_dashboard_handles_empty_state(): void
    {
        $user = User::factory()->create();

        Livewire::actingAs($user)
            ->test(Dashboard::class)
            ->assertViewHas('totalShipments', 0)
            ->assertViewHas('deliveredCount', 0)
            ->assertViewHas('enRouteCount', 0)
            ->assertViewHas('lateCount', 0)
            ->assertViewHas('needsAttentionCount', 0)
            ->assertViewHas('onTimeRate', null)
            ->assertViewHas('avgDeliveryDays', null)
            ->assertSee('No shipments yet')
            ->assertSee('Track Package');
    }
}
