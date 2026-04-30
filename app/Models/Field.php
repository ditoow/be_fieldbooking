<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Field extends Model
{
    protected $table = 'fields';

    protected $fillable = [
       'name',
       'description',
       'category',
       'status',
    ];

    public function schedules()
    {
        return $this->hasMany(Schedule::class, 'field_id');
    }
}
