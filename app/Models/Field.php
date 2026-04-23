<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Field extends Model
{
    protected $table = 'fields';

    protected $fillable = [
       'nama_lapangan',
       'deskripsi',
       'kategori_lapangan',
       'status',
    ];

    public function schedules()
    {
        return $this->hasMany(Schedule::class, 'lapangan_id');
    }
}
