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
