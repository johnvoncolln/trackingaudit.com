<?php

namespace Tests\Feature\Commands;

use App\Jobs\UpdateTrackerJob;
use App\Models\Tracker;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class BackfillEasyPostTrackersTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_aborts_when_driver_is_not_easypost(): void
    {
        config(['tracking.driver' => 'direct']);

        Queue::fake();

        $this->artisan('tracking:backfill-easypost')
            ->assertFailed();

        Queue::assertNothingPushed();
    }

    public function test_it_dispatches_update_jobs_for_active_trackers_missing_easypost_id(): void
    {
        config(['tracking.driver' => 'easypost']);

        Queue::fake();

        $user = User::factory()->create();

        $active = Tracker::factory()->for($user)->active()->count(3)->create(['easypost_id' => null]);
        Tracker::factory()->for($user)->active()->create(['easypost_id' => 'trk_alreadyset']);
        Tracker::factory()->for($user)->delivered()->create(['easypost_id' => null]);

        $this->artisan('tracking:backfill-easypost')
            ->expectsOutputToContain('Dispatched 3 EasyPost backfill jobs.')
            ->assertSuccessful();

        Queue::assertPushed(UpdateTrackerJob::class, 3);
    }
}
