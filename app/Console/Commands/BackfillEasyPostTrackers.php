<?php

namespace App\Console\Commands;

use App\Enums\TrackerStatus;
use App\Jobs\UpdateTrackerJob;
use App\Models\Tracker;
use Illuminate\Console\Command;

class BackfillEasyPostTrackers extends Command
{
    protected $signature = 'tracking:backfill-easypost';

    protected $description = 'Dispatch update jobs for active trackers missing an easypost_id so they register with EasyPost';

    public function handle(): int
    {
        if (config('tracking.driver') !== 'easypost') {
            $this->warn('tracking.driver is not "easypost" — aborting.');

            return self::FAILURE;
        }

        $activeStatuses = array_map(
            fn (TrackerStatus $s) => $s->value,
            TrackerStatus::activeStatuses()
        );

        $count = 0;

        Tracker::query()
            ->whereIn('status', $activeStatuses)
            ->whereNull('easypost_id')
            ->chunkById(100, function ($trackers) use (&$count) {
                foreach ($trackers as $tracker) {
                    UpdateTrackerJob::dispatch($tracker);
                    $count++;
                }
            });

        $this->info("Dispatched {$count} EasyPost backfill jobs.");

        return self::SUCCESS;
    }
}
