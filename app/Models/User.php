<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\Permission\Traits\HasRoles;
use Tymon\JWTAuth\Contracts\JWTSubject;

#[Fillable(['name', 'email', 'password', 'status'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable implements JWTSubject
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable, HasUuids, HasRoles;

    public function getJWTIdentifier()
    {
        return $this->getKey();
    }

    public function getJWTCustomClaims()
    {
        return [
            'role' => $this->getRoleNames(),
        ];
    }

    protected $fillable = [
        'name',
        'email',
        'password',
        'phone',
        'student_id',
        'status',
        'user_number',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    /**
     * Generate a role-based user number.
     *
     * @param string $role 'mahasiswa' | 'umum'
     */
    public static function generateUserNumber(string $role): ?string
    {
        $prefixMap = [
            'mahasiswa' => '#MHS-',
            'umum'      => '#USER-',
        ];

        $prefix = $prefixMap[$role] ?? null;

        if (!$prefix) {
            return null;
        }

        $driver = \Illuminate\Support\Facades\DB::connection()->getDriverName();
        $startPos = strlen($prefix) + 1;

        $query = static::where('user_number', 'like', $prefix . '%');

        if ($driver === 'pgsql') {
            $query->orderByRaw("CAST(SUBSTRING(user_number, {$startPos}) AS INTEGER) DESC");
        } elseif ($driver === 'sqlite') {
            $query->orderByRaw("CAST(SUBSTR(user_number, {$startPos}) AS INTEGER) DESC");
        } else {
            $query->orderByRaw("CAST(SUBSTRING(user_number, {$startPos}) AS UNSIGNED) DESC");
        }

        $latest = $query->first();

        $nextId = $latest
            ? ((int) substr($latest->user_number, strlen($prefix))) + 1
            : 1;

        return $prefix . sprintf('%03d', $nextId);
    }

    public function bookings()
    {
        return $this->hasMany(Booking::class);
    }

    public function isSuspended(): bool
    {
        return $this->status === 'suspended';
    }
}

