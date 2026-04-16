<?php

namespace App\Jobs;

use App\Models\User;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class DelegateTrackersJob implements ShouldQueue
{
    use Queueable;

    protected User $user;

    protected array $records;

    public function __construct(User $user, array $records)
    {
        $this->user = $user;
        $this->records = $records;
    }

    public function handle(): void
    {
        foreach ($this->records as $record) {
            TrackingImportJob::dispatch($this->user, $record);
        }
    }
}
