<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Media extends Model
{
    protected $table = 'media';

    protected $fillable = [
        'uuid',
        'user_id',
        'model_type',
        'model_id',
        'collection_name',
        'original_name',
        'stored_path',
        'mime_type',
        'file_size',
        'bucket',
        'url',
        'custom_properties',
    ];

    protected $casts = [
        'file_size' => 'integer',
        'custom_properties' => 'json',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($media) {
            if (empty($media->uuid)) {
                $media->uuid = (string) Str::uuid();
            }
        });
    }

    public function model()
    {
        return $this->morphTo();
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
