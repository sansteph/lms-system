<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\AssessmentResult;

class Assessment extends Model
{
    public function results()
    {
        return $this->hasMany(
            AssessmentResult::class,
            'assessment_id'
        );
    }

    public function content()
    {
        return $this->belongsTo(Content::class, 'content_id');
    }

    public function teacher()
    {
        return $this->belongsTo(User::class, 'teacher_id');
    }

    public function questionPaperReviewer()
    {
        return $this->belongsTo(User::class, 'question_paper_reviewed_by');
    }

    protected $fillable = [
        'assessment_title',
        'assessment_type',
        'assigned_class',
        'assessment_category',
        'assessment_date',
        'total_marks',
        'duration',
        'question_paper_type',
        'file_path',
        'question_paper_status',
        'question_paper_reviewed_by',
        'question_paper_reviewed_at',
        'question_paper_feedback',
        'question_paper_preview_path',
        'institute',
        'status',
        'content_id',
        'teacher_id',
    ];
}
