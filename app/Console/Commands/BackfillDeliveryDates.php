<?php

namespace App\Console\Commands;

use App\Jobs\UpdateTrackerJob;
use App\Models\Tracker;
use Illuminate\Console\Command;

class BackfillDeliveryDates extends Command
{
    protected $signature = 'tracking:backfill-delivery-dates {--carrier= : Only backfill a specific carrier}';

    protected $description = 'Dispatch update jobs for all trackers missing a delivery_date';

    public function handle(): int
    {
        $count = 0;

        Tracker::whereNull('delivery_date')
            ->when($this->option('carrier'), function ($query) {
                $query->where('carrier', $this->option('carrier'));
            })
            ->chunkById(100, function ($trackers) use (&$count) {
                foreach ($trackers as $tracker) {
                    UpdateTrackerJob::dispatch($tracker);
                    $count++;
                }
            });

        $this->info("Dispatched {$count} backfill jobs.");

        return self::SUCCESS;
    }
}
