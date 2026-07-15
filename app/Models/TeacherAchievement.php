<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TeacherAchievement extends Model
{
    protected $fillable = [
        'user_id',
        'achievement_type',
        'title',
        'organizer',
        'description',
        'achievement_date',
        'position',
        'certificate_file',
        'verification_status',
    ];

    public function teacher()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
