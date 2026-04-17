<?php

namespace App\Notifications;

use App\Enums\Carrier;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class LateShipmentReportNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public Collection $lateDeliveries,
        public string $period,
    ) {}

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $count = $this->lateDeliveries->count();

        $message = (new MailMessage)
            ->subject("Late Delivery Report - {$this->period}")
            ->greeting("Hello {$notifiable->name},")
            ->line("The following {$count} ".($count === 1 ? 'shipment was' : 'shipments were')." delivered late during the {$this->period} period:");

        $hasUps = false;

        foreach ($this->lateDeliveries as $tracker) {
            $expected = $tracker->delivery_date->format('M j, Y');
            $actual = $tracker->delivered_date->format('M j, Y');
            $daysLate = $tracker->delivery_date->diffInDays($tracker->delivered_date);

            $message->line("- **{$tracker->tracking_number}** ({$tracker->carrier}) — Expected: {$expected}, Delivered: {$actual} ({$daysLate} ".($daysLate === 1 ? 'day' : 'days').' late)');

            if ($tracker->carrier === Carrier::UPS->value) {
                $hasUps = true;
            }
        }

        if ($hasUps) {
            $message->line('---')
                ->line('**UPS Service Guarantee:** UPS shipments delivered late may be eligible for a refund claim through the UPS Service Guarantee program.');
        }

        $message->action('View Trackers', url('/tracking'));

        return $message;
    }
}
