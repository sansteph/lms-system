<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AiQuizQuestion extends Model
{
    protected $fillable = [
        'ai_quiz_id',
        'question_order',
        'question_type',
        'question_text',
        'options',
        'expected_answer',
        'rubric',
        'marks',
    ];

    protected $casts = [
        'options' => 'array',
        'rubric' => 'array',
    ];

    public function quiz()
    {
        return $this->belongsTo(AiQuiz::class, 'ai_quiz_id');
    }
}
