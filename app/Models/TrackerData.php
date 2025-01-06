<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TrackerData extends Model
{
    protected $table = 'tracker_data';

    protected $fillable = [
        'trackers_id',
        'data'
    ];

    protected $casts = [
        'data' => 'array'
    ];

    public function tracker()
    {
        return $this->belongsTo(Tracker::class, 'trackers_id');
    }
}
