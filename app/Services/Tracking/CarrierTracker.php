<?php

namespace App\Services\Tracking;

use App\Models\Tracker;
use App\Models\User;

interface CarrierTracker
{
    public function track(User $user, array $data): Tracker;

    public function update(Tracker $tracker): Tracker;
}
