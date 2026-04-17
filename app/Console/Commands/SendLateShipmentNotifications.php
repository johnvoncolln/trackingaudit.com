<?php

namespace App\Console\Commands;

use App\Enums\TrackerStatus;
use App\Models\Tracker;
use App\Models\User;
use App\Notifications\LateShipmentNotification;
use Illuminate\Console\Command;

class SendLateShipmentNotifications extends Command
{
    protected $signature = 'notifications:late-shipments {frequency}';

    protected $description = 'Send email digest of currently-late shipments to opted-in users';

    public function handle(): int
    {
        $frequency = $this->argument('frequency');

        $users = User::where('late_shipment_notifications_enabled', true)
            ->where('late_shipment_notifications_frequency', $frequency)
            ->get();

        $notified = 0;

        foreach ($users as $user) {
            $lateTrackers = Tracker::where('user_id', $user->id)
                ->whereNotNull('delivery_date')
                ->where('delivery_date', '<', now())
                ->whereNull('delivered_date')
                ->whereIn('status', array_map(fn (TrackerStatus $s) => $s->value, TrackerStatus::activeStatuses()))
                ->get();

            if ($lateTrackers->isNotEmpty()) {
                $user->notify(new LateShipmentNotification($lateTrackers));
                $notified++;
            }
        }

        $this->info("Sent late shipment notifications to {$notified} users.");

        return self::SUCCESS;
    }
}
