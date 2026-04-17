<?php

namespace Tests\Feature\Commands;

use App\Enums\TrackerStatus;
use App\Jobs\UpdateTrackerJob;
use App\Models\Tracker;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class UpdateActiveTrackersTest extends TestCase
{
    use RefreshDatabase;

    public function test_dispatches_jobs_for_active_trackers(): void
    {
        Queue::fake();

        $user = User::factory()->create();

        Tracker::factory()->for($user)->active()->count(3)->create();

        $this->artisan('tracking:update-active')
            ->expectsOutputToContain('Dispatched 3 tracker update jobs')
            ->assertSuccessful();

        Queue::assertCount(3);
        Queue::assertPushed(UpdateTrackerJob::class, 3);
    }

    public function test_does_not_dispatch_for_terminal_trackers(): void
    {
        Queue::fake();

        $user = User::factory()->create();

        foreach (TrackerStatus::terminalStatuses() as $status) {
            Tracker::factory()->for($user)->create(['status' => $status->value]);
        }

        $this->artisan('tracking:update-active')
            ->expectsOutputToContain('Dispatched 0 tracker update jobs')
            ->assertSuccessful();

        Queue::assertNothingPushed();
    }

    public function test_dispatches_only_for_active_statuses_when_mixed(): void
    {
        Queue::fake();

        $user = User::factory()->create();

        Tracker::factory()->for($user)->active()->count(2)->create();
        Tracker::factory()->for($user)->delivered()->create();
        Tracker::factory()->for($user)->create(['status' => TrackerStatus::CANCELLED->value]);

        $this->artisan('tracking:update-active')
            ->expectsOutputToContain('Dispatched 2 tracker update jobs')
            ->assertSuccessful();

        Queue::assertPushed(UpdateTrackerJob::class, 2);
    }

    public function test_handles_zero_active_trackers_gracefully(): void
    {
        Queue::fake();

        $this->artisan('tracking:update-active')
            ->expectsOutputToContain('Dispatched 0 tracker update jobs')
            ->assertSuccessful();

        Queue::assertNothingPushed();
    }
}
