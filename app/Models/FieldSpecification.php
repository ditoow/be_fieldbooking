<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FieldSpecification extends Model
{
    protected $table = 'field_specifications';

    protected $fillable = [
        'field_id',
        'label',
        'value',
        'sort_order',
    ];

    public function field(): BelongsTo
    {
        return $this->belongsTo(Field::class, 'field_id');
    }
}
