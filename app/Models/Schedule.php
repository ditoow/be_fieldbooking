<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Schedule extends Model
{
    protected $fillable = [
        'lapangan_id',
        'hari',
        'jam_mulai',
        'jam_selesai',
        'harga',
        'status',
    ];

    public function field()
    {
        return $this->belongsTo(Field::class, 'lapangan_id');
    }
}
