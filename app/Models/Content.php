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
    protected $fillable = [

        'content_title',

        'course_id',

        'course_category',

        'lesson_order',

        'content_type',

        'assigned_class',

        'institute',

        'file_path',

        'preview_pdf_path',

        'student_file_path',

        'student_preview_pdf_path',

        'is_released',

        'status',

    ];
}
