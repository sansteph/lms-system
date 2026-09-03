<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class MobilePushToken extends Model
{
    protected $fillable = [
        'pushable_type',
        'pushable_id',
        'fcm_token',
        'role',
        'platform',
        'device_name',
        'last_seen_at',
    ];

    protected $casts = [
        'last_seen_at' => 'datetime',
    ];

    public function pushable(): MorphTo
    {
        return $this->morphTo();
    }
}
