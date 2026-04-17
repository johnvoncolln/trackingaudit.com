<?php

namespace Tests\Feature\Commands;

use App\Models\Tracker;
use App\Models\User;
use App\Notifications\LateShipmentNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class SendLateShipmentNotificationsTest extends TestCase
{
    use RefreshDatabase;

    public function test_sends_notification_to_opted_in_users_with_late_trackers(): void
    {
        Notification::fake();

        $user = User::factory()->create([
            'late_shipment_notifications_enabled' => true,
            'late_shipment_notifications_frequency' => 'daily',
        ]);

        Tracker::factory()->for($user)->late()->create();

        $this->artisan('notifications:late-shipments daily')
            ->expectsOutputToContain('Sent late shipment notifications to 1 users')
            ->assertSuccessful();

        Notification::assertSentTo($user, LateShipmentNotification::class);
    }

    public function test_does_not_send_to_users_who_have_not_opted_in(): void
    {
        Notification::fake();

        $user = User::factory()->create([
            'late_shipment_notifications_enabled' => false,
        ]);

        Tracker::factory()->for($user)->late()->create();

        $this->artisan('notifications:late-shipments daily')->assertSuccessful();

        Notification::assertNothingSent();
    }

    public function test_does_not_send_when_user_has_no_late_trackers(): void
    {
        Notification::fake();

        $user = User::factory()->create([
            'late_shipment_notifications_enabled' => true,
            'late_shipment_notifications_frequency' => 'daily',
        ]);

        Tracker::factory()->for($user)->active()->create([
            'delivery_date' => now()->addDays(3),
        ]);

        $this->artisan('notifications:late-shipments daily')->assertSuccessful();

        Notification::assertNothingSent();
    }

    public function test_does_not_include_delivered_trackers(): void
    {
        Notification::fake();

        $user = User::factory()->create([
            'late_shipment_notifications_enabled' => true,
            'late_shipment_notifications_frequency' => 'daily',
        ]);

        Tracker::factory()->for($user)->delivered()->create();

        $this->artisan('notifications:late-shipments daily')->assertSuccessful();

        Notification::assertNothingSent();
    }

    public function test_respects_frequency_setting(): void
    {
        Notification::fake();

        $weeklyUser = User::factory()->create([
            'late_shipment_notifications_enabled' => true,
            'late_shipment_notifications_frequency' => 'weekly',
        ]);

        Tracker::factory()->for($weeklyUser)->late()->create();

        $this->artisan('notifications:late-shipments daily')->assertSuccessful();

        Notification::assertNothingSent();
    }

    public function test_notification_contains_tracking_details(): void
    {
        Notification::fake();

        $user = User::factory()->create([
            'late_shipment_notifications_enabled' => true,
            'late_shipment_notifications_frequency' => 'daily',
        ]);

        Tracker::factory()->for($user)->late()->count(2)->create();

        $this->artisan('notifications:late-shipments daily')->assertSuccessful();

        Notification::assertSentTo($user, LateShipmentNotification::class, function ($notification) {
            return $notification->lateTrackers->count() === 2;
        });
    }
}
