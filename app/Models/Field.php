<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Field extends Model
{
    protected $table = 'fields';

    protected $fillable = [
        'name',
        'image_url',
        'category',
    ];

    protected $with = ['detail'];

    public function detail(): HasOne
    {
        return $this->hasOne(DetailField::class, 'field_id');
    }

    public function schedules(): HasMany
    {
        return $this->hasMany(Schedule::class, 'field_id');
    }

    public function getDescriptionAttribute(): ?string
    {
        return $this->detail?->description;
    }

    public function getSurfaceTypeAttribute(): ?string
    {
        return $this->detail?->surface_type;
    }

    public function getRatingAttribute(): float
    {
        return (float) ($this->detail?->rating ?? 0.0);
    }

    public function getStatusAttribute(): ?string
    {
        return $this->detail?->status;
    }
}
