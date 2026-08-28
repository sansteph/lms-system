<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PendingPasswordChange extends Model
{
    protected $fillable = [
        'user_id',
        'email',
        'token_hash',
        'new_password',
        'purpose',
        'expires_at',
        'confirmed_at',
    ];

    protected $casts = [
        'expires_at' => 'datetime',
        'confirmed_at' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
