<?php

namespace App\Console\Commands;

use App\Enums\Carrier;
use App\Enums\NotificationFrequency;
use App\Models\Tracker;
use App\Models\User;
use App\Notifications\LateShipmentReportNotification;
use Illuminate\Console\Command;

class SendLateShipmentReports extends Command
{
    protected $signature = 'reports:late-shipments {frequency}';

    protected $description = 'Send late delivery report (UPS/FedEx only) to opted-in users';

    public function handle(): int
    {
        $frequency = NotificationFrequency::from($this->argument('frequency'));

        $dateRange = match ($frequency) {
            NotificationFrequency::Daily => [now()->subDay(), now()],
            NotificationFrequency::Weekly => [now()->subWeek(), now()],
            NotificationFrequency::Monthly => [now()->subMonth(), now()],
        };

        $users = User::where('late_shipment_report_enabled', true)
            ->where('late_shipment_report_frequency', $frequency->value)
            ->get();

        $notified = 0;

        foreach ($users as $user) {
            $lateDeliveries = Tracker::where('user_id', $user->id)
                ->whereIn('carrier', [Carrier::UPS->value, Carrier::FEDEX->value])
                ->whereNotNull('expected_delivery_date')
                ->whereNotNull('delivered_date')
                ->whereColumn('delivered_date', '>', 'expected_delivery_date')
                ->whereBetween('delivered_date', $dateRange)
                ->get();

            if ($lateDeliveries->isNotEmpty()) {
                $user->notify(new LateShipmentReportNotification($lateDeliveries, $frequency->label()));
                $notified++;
            }
        }

        $this->info("Sent late shipment reports to {$notified} users.");

        return self::SUCCESS;
    }
}
