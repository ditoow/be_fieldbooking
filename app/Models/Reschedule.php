<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Reschedule extends Model
{
    protected $fillable = [
        'booking_id',
        'old_schedule_id',
        'new_schedule_id',
    ];

    public function booking(): BelongsTo
    {
        return $this->belongsTo(Booking::class);
    }

    public function oldSchedule(): BelongsTo
    {
        return $this->belongsTo(Schedule::class, 'old_schedule_id');
    }

    public function newSchedule(): BelongsTo
    {
        return $this->belongsTo(Schedule::class, 'new_schedule_id');
    }
}
