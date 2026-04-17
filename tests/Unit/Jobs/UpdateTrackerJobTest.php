<?php

namespace Tests\Unit\Jobs;

use App\Jobs\UpdateTrackerJob;
use App\Models\Tracker;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UpdateTrackerJobTest extends TestCase
{
    use RefreshDatabase;

    public function test_job_is_constructed_with_tracker(): void
    {
        $user = User::factory()->create();
        $tracker = Tracker::factory()->for($user)->create(['carrier' => 'UPS']);

        $job = new UpdateTrackerJob($tracker);

        $this->assertSame($tracker->id, $job->tracker->id);
        $this->assertSame(3, $job->tries);
        $this->assertSame([30, 60], $job->backoff);
    }
}
