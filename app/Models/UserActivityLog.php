<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\User;
use App\Models\Student;

class UserActivityLog extends Model
{
    protected $fillable = [
        'user_session_id',
        'user_type',
        'user_id',
        'section_name',
        'route_name',
        'page_url',
        'started_at',
        'ended_at',
        'duration_seconds',
    ];

    public function teacher()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function student()
    {
        return $this->belongsTo(Student::class, 'user_id');
    }
}