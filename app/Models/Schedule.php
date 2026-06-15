<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * @mixin \Illuminate\Database\Eloquent\Builder
 */
class Schedule extends Model
{
    protected $fillable = [
        'field_id',
        'date',
        'start_time',
        'end_time',
        'price',
    ];

    public function field()
    {
        return $this->belongsTo(Field::class, 'field_id');
    }

    public function bookings()
    {
        return $this->belongsToMany(Booking::class, 'booking_details');
    }
}

