<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\AssessmentResult;

class Assessment extends Model
{
    protected $casts = [
        'ai_generated' => 'boolean',
        'ai_source_content_ids' => 'array',
    ];

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
        'start_time',
        'end_time',
        'total_marks',
        'duration',
        'question_paper_type',
        'ai_generated',
        'ai_source_content_ids',
        'ai_generation_payload',
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
