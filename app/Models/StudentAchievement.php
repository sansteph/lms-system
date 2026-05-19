<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StudentAchievement extends Model
{
    protected $fillable = [

        'student_id',

        'achievement_type',

        'title',

        'organizer',

        'description',

        'achievement_date',

        'position',

        'certificate_file',

        'verification_status',


    ];
}