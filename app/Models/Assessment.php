<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\AssessmentQuestion;
use App\Models\AssessmentResult;

class Assessment extends Model
{
    public function questions()
    {
        return $this->hasMany(AssessmentQuestion::class, 'assessment_id');
    }
    public function getCalculatedMarksAttribute()
    {
        return $this->questions->sum('marks');
    }

    public function results()
    {
        return $this->hasMany(
            AssessmentResult::class,'assessment_id');
    }

    public function content()
    {
        return $this->belongsTo(Content::class, 'content_id');
    }   
    protected $fillable = [
        'assessment_title',
        'assessment_type',
        'assigned_class',
        'total_marks',
        'duration',
        'question_paper_type',
        'file_path',
        'institute',
        'status',
        'content_id',
    ];
}