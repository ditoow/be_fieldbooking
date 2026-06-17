<?php

namespace App\Models;

use App\Traits\HasMedia;
use Illuminate\Database\Eloquent\Model;

/**
 * @mixin \Illuminate\Database\Eloquent\Builder
 */
class Booking extends Model
{
    use HasMedia;



    protected $fillable = [
        'user_id',
        'booking_number',
        'status',
        'booking_type',
        'total_price',
        'file_url',
        'qr_id',
        'qr_string',
        'is_attended',
        'attended_at',
        'expires_at',
    ];

    protected $casts = [
        'expires_at' => 'datetime',
        'total_price' => 'integer',
    ];

    public const TRANSITIONS = [
        'pending' => ['approved', 'rejected', 'cancelled', 'expired'],
        'approved' => ['cancelled'],
        'rejected' => [],
        'cancelled' => [],
        'expired' => [],
    ];

    public function transitionTo(string $newStatus): void
    {
        $allowed = self::TRANSITIONS[$this->status] ?? [];

        if (!in_array($newStatus, $allowed, true)) {
            throw new \InvalidArgumentException(
                "Cannot transition booking from '{$this->status}' to '{$newStatus}'."
            );
        }

        $this->update(['status' => $newStatus]);
    }

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($booking) {
            if (empty($booking->booking_number)) {
                $nextId = (static::max('id') ?? 0) + 1;
                $booking->booking_number = 'UGO-' . now()->format('YmdHis') . '-' . sprintf('%04d', $nextId);
            }
        });
    }

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

    public function rating()
    {
        return $this->hasOne(Rating::class, 'booking_id');
    }

    protected function isAttended(): \Illuminate\Database\Eloquent\Casts\Attribute
    {
        return \Illuminate\Database\Eloquent\Casts\Attribute::make(
            get: fn ($value) => is_null($value) ? null : (bool) $value,
            set: fn ($value) => is_null($value) ? null : ($value ? '1' : '0')
        );
    }

    public function scopePending(\Illuminate\Database\Eloquent\Builder $query)
    {
        return $query->where('status', 'pending');
    }

    public function scopeExpired(\Illuminate\Database\Eloquent\Builder $query)
    {
        return $query->where('status', 'pending')
            ->where('expires_at', '<', now());
    }
}
