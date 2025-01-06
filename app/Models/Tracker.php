<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Tracker extends Model
{

    protected $casts = [
        'reference_data' => 'array',
        'status_time' => 'datetime',
        'delivery_date' => 'datetime',
        'delivered_date' => 'datetime',
    ];

    public function trackerData()
    {
        return $this->hasOne(TrackerData::class, 'trackers_id');
    }
}
