<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Field extends Model
{
    protected $table = 'fields';

    protected $fillable = [
        'name',
        'description',
        'surface_type',
        'rating',
        'image_url',
        'category',
        'status',
    ];

    public function schedules()
    {
        return $this->hasMany(Schedule::class, 'field_id');
    }
}
