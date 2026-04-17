<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class LateShipmentNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public Collection $lateTrackers) {}

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $count = $this->lateTrackers->count();

        $message = (new MailMessage)
            ->subject("Late Shipment Alert - {$count} ".($count === 1 ? 'package' : 'packages').' past due')
            ->greeting("Hello {$notifiable->name},")
            ->line("You have {$count} ".($count === 1 ? 'shipment' : 'shipments').' past the expected delivery date:');

        foreach ($this->lateTrackers as $tracker) {
            $expected = $tracker->delivery_date->format('M j, Y');
            $message->line("- **{$tracker->tracking_number}** ({$tracker->carrier}) — Expected: {$expected} — Status: {$tracker->status}");
        }

        $message->action('View Trackers', url('/tracking'))
            ->line('Please review these shipments for any issues.');

        return $message;
    }
}
