<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CourseContent extends Model
{
    protected $fillable = [
        'course_id',
        'content_id',
        'sort_order',
        'status',
        'created_by',
        'source_template_course_content_id',
        'source_template_content_id',
    ];

    public function course()
    {
        return $this->belongsTo(Course::class);
    }

    public function content()
    {
        return $this->belongsTo(Content::class);
    }

    public function sourceTemplateContent()
    {
        return $this->belongsTo(Content::class, 'source_template_content_id');
    }
}
