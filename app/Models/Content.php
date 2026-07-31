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

    public function getAiQuizContentIdAttribute()
    {
        $sourceContent = $this->courseContent?->sourceTemplateContent;

        if ($sourceContent && ($sourceContent->aiSummary || $sourceContent->hasAiPdfMaterial())) {
            return $sourceContent->id;
        }

        return $this->id;
    }

    public function hasAiPdfMaterial(): bool
    {
        foreach ([
            $this->student_preview_pdf_path,
            $this->preview_pdf_path,
            $this->student_file_path,
            $this->file_path,
        ] as $path) {
            if ($path && strtolower(pathinfo($path, PATHINFO_EXTENSION)) === 'pdf') {
                return true;
            }
        }

        return false;
    }

    protected $fillable = [

        'content_title',

        'description',

        'course_id',

        'lesson_order',

        'content_type',

        'assigned_class',

        'section',

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
