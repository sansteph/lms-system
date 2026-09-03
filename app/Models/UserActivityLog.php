<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\User;
use App\Models\Student;

class UserActivityLog extends Model
{
    public function getActivityStatusAttribute(): string
    {
        if ($this->ended_at) return 'Completed';
        return $this->page_url === 'mobile' && $this->updated_at?->lt(now()->subSeconds(90)) ? 'Disconnected' : 'Active';
    }

    public function scopeLearningContent($query)
    {
        return $query->where(function ($scope) {
            $scope->whereIn('route_name', ['teacher.content', 'student.content', 'content.preview'])
                ->orWhere(function ($legacy) {
                    $legacy->where('route_name', 'mobile')
                        ->whereIn('section_name', ['Learning Content', 'Learning Content Preview']);
                });
        });
    }

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
