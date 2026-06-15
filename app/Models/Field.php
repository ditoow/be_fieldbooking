<?php

namespace App\Models;

use App\Traits\HasMedia;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Field extends Model
{
    use HasMedia, SoftDeletes;

    protected $table = 'fields';

    protected $fillable = [
        'name',
        'image_url',
        'category',
    ];

    public function detail(): HasOne
    {
        return $this->hasOne(DetailField::class, 'field_id');
    }

    public function schedules(): HasMany
    {
        return $this->hasMany(Schedule::class, 'field_id');
    }

    public function maintenances(): HasMany
    {
        return $this->hasMany(FieldMaintenance::class, 'field_id');
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
