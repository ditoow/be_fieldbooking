<?php

namespace App\Traits;

use App\Models\Media;
use Illuminate\Database\Eloquent\Relations\MorphMany;

trait HasMedia
{
    public function media(): MorphMany
    {
        return $this->morphMany(Media::class, 'model');
    }

    public function getMediaByCollection(string $collectionName)
    {
        return $this->media()->where('collection_name', $collectionName)->get();
    }

    public function getFirstMediaUrl(string $collectionName): ?string
    {
        $media = $this->media()->where('collection_name', $collectionName)->first();

        return $media?->url;
    }
}
