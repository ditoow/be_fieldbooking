<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class Booking extends Model
{
    use HasUuids;
    protected $fillable = [
        'status',
        'booking_type',
        'file_url',
        'is_attended',
        'attended_at',
        'expires_at'
    ];

    public function schedule(){
        return $this->belongsTo(Schedule::class, 'schedule_id');
    }
    public function user(){
        return $this->belongsTo(User::class, 'user_id');
    }
}
