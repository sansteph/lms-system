<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MfaAuditLog extends Model
{
    protected $fillable = [
        'account_type', 'account_id', 'event', 'channel', 'successful',
        'ip_address', 'user_agent', 'metadata',
    ];

    protected $casts = ['successful' => 'boolean', 'metadata' => 'array'];
}
