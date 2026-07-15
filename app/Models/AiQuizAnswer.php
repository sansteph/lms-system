<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AiQuizAnswer extends Model
{
    protected $fillable = [
        'ai_quiz_attempt_id',
        'ai_quiz_question_id',
        'answer_text',
        'score',
        'feedback',
    ];

    public function attempt()
    {
        return $this->belongsTo(AiQuizAttempt::class, 'ai_quiz_attempt_id');
    }

    public function question()
    {
        return $this->belongsTo(AiQuizQuestion::class, 'ai_quiz_question_id');
    }
}
