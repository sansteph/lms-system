<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StudentAchievement extends Model
{
    public function student()
    {
        return $this->belongsTo(Student::class);
    }

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
