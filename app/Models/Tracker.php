<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Tracker extends Model
{
    public function trackerData()
    {
        return $this->hasOne(TrackerData::class);
    }
}
