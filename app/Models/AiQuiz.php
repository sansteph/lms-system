<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AiQuiz extends Model
{
    protected $fillable = [
        'content_id',
        'audience',
        'grade_level',
        'provider',
        'model',
        'title',
        'instructions',
        'total_marks',
        'passing_marks',
        'status',
    ];

    public function content()
    {
        return $this->belongsTo(Content::class);
    }

    public function questions()
    {
        return $this->hasMany(AiQuizQuestion::class);
    }
}
