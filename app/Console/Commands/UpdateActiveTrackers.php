<?php

namespace App\Console\Commands;

use App\Enums\TrackerStatus;
use App\Jobs\UpdateTrackerJob;
use App\Models\Tracker;
use Illuminate\Console\Command;

class UpdateActiveTrackers extends Command
{
    protected $signature = 'tracking:update-active';

    protected $description = 'Dispatch update jobs for all trackers with active (non-terminal) statuses';

    public function handle(): int
    {
        $activeStatuses = array_map(
            fn (TrackerStatus $s) => $s->value,
            TrackerStatus::activeStatuses()
        );

        $count = 0;

        Tracker::whereIn('status', $activeStatuses)
            ->chunkById(100, function ($trackers) use (&$count) {
                foreach ($trackers as $tracker) {
                    UpdateTrackerJob::dispatch($tracker);
                    $count++;
                }
            });

        $this->info("Dispatched {$count} tracker update jobs.");

        return self::SUCCESS;
    }
}
