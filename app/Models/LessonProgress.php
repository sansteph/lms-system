<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LessonProgress extends Model
{
    protected $table = 'lesson_progress';

    protected $fillable = [

        'student_id',
        
        'independent_learner_id',

        'content_id',

        'is_completed',

        'completed_at',


    ];
}