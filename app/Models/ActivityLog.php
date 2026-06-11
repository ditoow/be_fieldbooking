<?php
 
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ActivityLog extends Model
{
    protected $fillable = [
        'type',
        'title',
        'description',
        'user_name',
    ];
}
