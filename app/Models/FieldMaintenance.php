<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FieldMaintenance extends Model
{
    protected $table = 'field_maintenances';

    protected $fillable = [
        'field_id',
        'date',
        'start_time',
        'end_time',
        'reason',
    ];

    public function field(): BelongsTo
    {
        return $this->belongsTo(Field::class, 'field_id');
    }
}
