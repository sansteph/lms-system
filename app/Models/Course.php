<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Course extends Model
{
    protected $fillable = [

        'course_title',

        'institute',

        'description',

        'target',

        'assigned_class',

        'certificate_enabled',

        'status',

        'price',

        'availability_type',
        
        'is_active',

        'is_template_source',

    ];

    public function contents()
    {
        return $this->hasMany(Content::class);
    }

    public function courseContents()
    {
        return $this->hasMany(CourseContent::class);
    }

}
