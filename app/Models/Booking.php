<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Booking extends Model
{
    protected $fillable = [
        'status',
        'file-url',
        'is_hadir',
        'hadir_pada',
    ];

    public function schedule(){
        return $this->hasMany(Schedule::class, 'schedule_id');
    }
    public function user(){
        return $this->hasMany(User::class, 'user_id');
    }
}
