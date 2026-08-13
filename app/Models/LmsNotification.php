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
        'notification_type',
        'starts_at',
        'expires_at',
        'status',
        'login_display_limit',
        'created_by',
    ];

    protected $casts = [
        'starts_at' => 'date',
        'expires_at' => 'date',
    ];

    public function loginViews()
    {
        return $this->hasMany(LmsNotificationLoginView::class, 'notification_id');
    }
}
