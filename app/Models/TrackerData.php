<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TrackerData extends Model
{
    protected $table = 'tracker_data';

    public function tracker()
    {
        return $this->belongsTo(Tracker::class);
    }
}
