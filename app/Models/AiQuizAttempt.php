<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AiQuizAttempt extends Model
{
    protected $fillable = [
        'ai_quiz_id',
        'content_id',
        'attempt_type',
        'grade_level',
        'student_id',
        'teacher_id',
        'score',
        'percentage',
        'status',
        'feedback',
        'started_at',
        'submitted_at',
        'evaluated_at',
    ];

    protected $casts = [
        'started_at' => 'datetime',
        'submitted_at' => 'datetime',
        'evaluated_at' => 'datetime',
    ];

    public function quiz()
    {
        return $this->belongsTo(AiQuiz::class, 'ai_quiz_id');
    }

    public function content()
    {
        return $this->belongsTo(Content::class);
    }
}
