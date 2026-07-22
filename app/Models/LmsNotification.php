<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LmsNotification extends Model
{
    protected $fillable = [
        'title',
        'message',
        'target',
        'institute',
        'starts_at',
        'expires_at',
        'status',
        'created_by',
    ];

    protected $casts = [
        'starts_at' => 'date',
        'expires_at' => 'date',
    ];
}
