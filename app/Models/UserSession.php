<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\User;
use App\Models\Student;

class UserSession extends Model
{
    protected $fillable = [
        'user_type',
        'user_id',
        'login_time',
        'logout_time',
        'total_duration_seconds',
        'ip_address',
        'browser',
    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function teacher()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function student()
    {
        return $this->belongsTo(Student::class, 'user_id');
    }
}