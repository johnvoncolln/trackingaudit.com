<?php

namespace App\Jobs;

use App\Models\User;
use App\Services\Tracking\TrackerFactory;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class TrackingImportJob implements ShouldQueue
{
    use Queueable;

    public int $tries = 5;

    /** @var array<int, int> */
    public array $backoff = [10, 30, 90, 300, 900];

    protected User $user;

    protected array $record;

    public function __construct(User $user, array $record)
    {
        $this->user = $user;
        $this->record = $record;
    }

    public function handle(): void
    {
        $tracker = TrackerFactory::make($this->record['carrier']);
        $tracker->track($this->user, $this->record);
    }
}
