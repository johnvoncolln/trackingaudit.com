<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Tracker extends Model
{
    use HasFactory;

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

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
