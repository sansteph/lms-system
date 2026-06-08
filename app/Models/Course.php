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

    ];

    public function contents()
    {
        return $this->hasMany(Content::class);
    }

    public function courses()
    {
        $courses = Course::where('is_active', 1)
            ->whereIn('availability_type', ['Independent', 'Both'])
            ->latest()
            ->get();

        return view('independent.courses', compact('courses'));
    }

    public function courseDetails($id)
    {
        $course = Course::where('is_active', 1)
            ->whereIn('availability_type', ['Independent', 'Both'])
            ->findOrFail($id);

        return view('independent.course-details', compact('course'));
    }
}