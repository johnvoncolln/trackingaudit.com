<?php

namespace App\Jobs;

use App\Models\Tracker;
use App\Services\Tracking\TrackerFactory;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class UpdateTrackerJob implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    /** @var array<int, int> */
    public array $backoff = [30, 60];

    public function __construct(public Tracker $tracker) {}

    public function handle(): void
    {
        $service = TrackerFactory::make($this->tracker->carrier);
        $service->update($this->tracker);
    }
}
