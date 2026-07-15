<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Content extends Model
{
    protected $table = 'contents';
    public function course()
    {
        return $this->belongsTo(Course::class);
    }

    public function courseContent()
    {
        return $this->hasOne(CourseContent::class, 'content_id');
    }

    public function aiSummary()
    {
        return $this->hasOne(AiContentSummary::class);
    }

    public function getEffectiveAiSummaryAttribute()
    {
        if ($this->aiSummary) {
            return $this->aiSummary;
        }

        return $this->courseContent?->sourceTemplateContent?->aiSummary;
    }

    protected $fillable = [

        'content_title',

        'description',

        'course_id',

        'lesson_order',

        'content_type',

        'assigned_class',

        'institute',

        'file_path',

        'preview_pdf_path',

        'student_file_path',

        'student_preview_pdf_path',

        'original_file_name',

        'uploaded_by',

        'is_released',

        'status',

    ];
}
