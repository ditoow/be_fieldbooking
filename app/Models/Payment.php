<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Payment extends Model
{
    protected $fillable = [
        'booking_id',
        'transaction_id',
        'order_id',
        'payment_type',
        'status',
        'amount',
        'pdf_url',
        'transaction_time',
        'expires_at',
    ];

    protected $casts = [
        'transaction_time' => 'datetime',
        'expires_at' => 'datetime',
        'amount' => 'integer',
    ];

    public function booking()
    {
        return $this->belongsTo(Booking::class);
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