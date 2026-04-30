<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Schedule extends Model
{
    protected $fillable = [
        'field_id',
        'date',
        'start_time',
        'end_time',
        'price',
        'status',
    ];

    public function field()
    {
        return $this->belongsTo(Field::class, 'field_id');
    }
}
