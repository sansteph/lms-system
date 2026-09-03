<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Laravel\Sanctum\HasApiTokens;

class Student extends Authenticatable
{
    use HasApiTokens;

    public function certificate()
    {
        return $this->hasOne(\App\Models\Certificate::class);
    }
    protected $fillable = [
        'student_id',
        'name',
        'institute',
        'class',
        'section',
        'contact',
        'password',
        'status',
        'email',
        'guardian_name',
        'is_robotics_club_member',
        'profile_completed',
        'linkedin_url',
        'profile_image',
    ];
}
