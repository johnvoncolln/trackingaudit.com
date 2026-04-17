<?php

namespace Tests\Feature\Commands;

use App\Enums\Carrier;
use App\Models\Tracker;
use App\Models\User;
use App\Notifications\LateShipmentReportNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class SendLateShipmentReportsTest extends TestCase
{
    use RefreshDatabase;

    public function test_sends_report_to_opted_in_users_with_late_deliveries(): void
    {
        Notification::fake();

        $user = User::factory()->create([
            'late_shipment_report_enabled' => true,
            'late_shipment_report_frequency' => 'daily',
        ]);

        Tracker::factory()->for($user)->deliveredLate()->create([
            'carrier' => Carrier::UPS->value,
        ]);

        $this->artisan('reports:late-shipments daily')
            ->expectsOutputToContain('Sent late shipment reports to 1 users')
            ->assertSuccessful();

        Notification::assertSentTo($user, LateShipmentReportNotification::class);
    }

    public function test_only_includes_ups_and_fedex(): void
    {
        Notification::fake();

        $user = User::factory()->create([
            'late_shipment_report_enabled' => true,
            'late_shipment_report_frequency' => 'daily',
        ]);

        // Only USPS late delivery — should not trigger report
        Tracker::factory()->for($user)->deliveredLate()->create([
            'carrier' => Carrier::USPS->value,
        ]);

        $this->artisan('reports:late-shipments daily')->assertSuccessful();

        Notification::assertNothingSent();
    }

    public function test_includes_fedex_late_deliveries(): void
    {
        Notification::fake();

        $user = User::factory()->create([
            'late_shipment_report_enabled' => true,
            'late_shipment_report_frequency' => 'daily',
        ]);

        Tracker::factory()->for($user)->deliveredLate()->create([
            'carrier' => Carrier::FEDEX->value,
        ]);

        $this->artisan('reports:late-shipments daily')->assertSuccessful();

        Notification::assertSentTo($user, LateShipmentReportNotification::class);
    }

    public function test_only_includes_trackers_delivered_after_delivery_date(): void
    {
        Notification::fake();

        $user = User::factory()->create([
            'late_shipment_report_enabled' => true,
            'late_shipment_report_frequency' => 'daily',
        ]);

        // On-time delivery — delivered_date <= delivery_date
        Tracker::factory()->for($user)->delivered()->create([
            'carrier' => Carrier::UPS->value,
            'delivery_date' => now()->subDay(),
            'delivered_date' => now()->subDays(2),
        ]);

        $this->artisan('reports:late-shipments daily')->assertSuccessful();

        Notification::assertNothingSent();
    }

    public function test_respects_date_range_for_frequency(): void
    {
        Notification::fake();

        $user = User::factory()->create([
            'late_shipment_report_enabled' => true,
            'late_shipment_report_frequency' => 'daily',
        ]);

        // Late delivery from 2 weeks ago — outside daily range
        Tracker::factory()->for($user)->create([
            'carrier' => Carrier::UPS->value,
            'status' => 'delivered',
            'delivery_date' => now()->subDays(20),
            'delivered_date' => now()->subDays(14),
        ]);

        $this->artisan('reports:late-shipments daily')->assertSuccessful();

        Notification::assertNothingSent();
    }

    public function test_does_not_send_to_users_who_opted_out(): void
    {
        Notification::fake();

        $user = User::factory()->create([
            'late_shipment_report_enabled' => false,
        ]);

        Tracker::factory()->for($user)->deliveredLate()->create([
            'carrier' => Carrier::UPS->value,
        ]);

        $this->artisan('reports:late-shipments daily')->assertSuccessful();

        Notification::assertNothingSent();
    }

    public function test_respects_frequency_setting(): void
    {
        Notification::fake();

        $user = User::factory()->create([
            'late_shipment_report_enabled' => true,
            'late_shipment_report_frequency' => 'weekly',
        ]);

        Tracker::factory()->for($user)->deliveredLate()->create([
            'carrier' => Carrier::UPS->value,
        ]);

        $this->artisan('reports:late-shipments daily')->assertSuccessful();

        Notification::assertNothingSent();
    }

    public function test_report_contains_late_delivery_details(): void
    {
        Notification::fake();

        $user = User::factory()->create([
            'late_shipment_report_enabled' => true,
            'late_shipment_report_frequency' => 'daily',
        ]);

        Tracker::factory()->for($user)->deliveredLate()->count(3)->create([
            'carrier' => Carrier::UPS->value,
        ]);

        $this->artisan('reports:late-shipments daily')->assertSuccessful();

        Notification::assertSentTo($user, LateShipmentReportNotification::class, function ($notification) {
            return $notification->lateDeliveries->count() === 3
                && $notification->period === 'Daily';
        });
    }
}
