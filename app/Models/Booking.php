<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class Booking extends Model
{
    use HasUuids;
    protected $fillable = [
        'status',
        'file-url',
        'is_hadir',
        'hadir_pada',
        'berakhir_pada'
    ];

    public function schedule(){
        return $this->belongsTo(Schedule::class, 'schedule_id');
    }
    public function user(){
        return $this->belongsTo(User::class, 'user_id');
    }
}
