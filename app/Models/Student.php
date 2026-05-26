<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Student extends Model
{
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
        'guardian_contact',
        'is_robotics_club_member',
        'profile_completed',
    ];
}