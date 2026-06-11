<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AssessmentQuestion extends Model
{
    protected $fillable = [
        'assessment_id',
        'question',
        'option_a',
        'option_b',
        'option_c',
        'option_d',
        'correct_answer',
        'marks',
        'topic',
        'question_type',
        'short_answer',
        'long_answer',
        'explanation',
    ];
    public function assessment()
    {
        return $this->belongsTo(Assessment::class, 'assessment_id');
    }
}