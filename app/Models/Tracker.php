<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Tracker extends Model
{
    protected $fillable = [
        'carrier',
        'tracking_number',
        'reference_id',
        'reference_name',
        'reference_data',
        'origin',
        'destination',
        'location',
        'status',
        'status_time',
        'delivery_date',
        'delivered_date'
    ];

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
