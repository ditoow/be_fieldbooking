<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DetailField extends Model
{
    protected $table = 'detail_fields';

    protected $fillable = [
        'field_id',
        'description',
        'surface_type',
        'rating',
        'status',
    ];

    public function field(): BelongsTo
    {
        return $this->belongsTo(Field::class, 'field_id');
    }
}
