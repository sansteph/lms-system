<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MfaChallenge extends Model
{
    protected $fillable = [
        'challenge_token_hash', 'account_type', 'account_id', 'channel', 'code_hash',
        'attempts', 'resend_count', 'resend_window_started_at', 'last_sent_at',
        'expires_at', 'locked_until', 'verified_at', 'ip_address', 'user_agent',
    ];

    protected $casts = [
        'resend_window_started_at' => 'datetime',
        'last_sent_at' => 'datetime',
        'expires_at' => 'datetime',
        'locked_until' => 'datetime',
        'verified_at' => 'datetime',
    ];
}
