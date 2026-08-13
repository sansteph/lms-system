<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LmsNotificationLoginView extends Model
{
    protected $fillable = [
        'notification_id',
        'user_id',
        'viewer_role',
        'institute',
        'display_count',
        'last_displayed_at',
    ];

    protected $casts = [
        'last_displayed_at' => 'datetime',
    ];
}
