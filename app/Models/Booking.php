<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Booking extends Model
{


    protected $fillable = [
        'user_id',
        'status',
        'booking_type',
        'total_price',
        'file_url',
        'is_attended',
        'attended_at',
        'expires_at',
    ];

    protected $casts = [
        'is_attended' => 'boolean',
        'attended_at' => 'datetime',
        'expires_at' => 'datetime',
    ];

    public function schedules()
    {
        return $this->belongsToMany(Schedule::class, 'booking_details');
    }

    public function details()
    {
        return $this->hasMany(BookingDetail::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopeExpired($query)
    {
        return $query->where('status', 'pending')
            ->where('expires_at', '<', now());
    }
}
