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

    public function student()
    {
        return $this->belongsTo(Student::class, 'student_id');
    }

    public function content()
    {
        return $this->belongsTo(Content::class, 'content_id');
    }
}