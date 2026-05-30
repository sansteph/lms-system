<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CourseEnrollment extends Model
{
    protected $fillable = [
        'learner_id',
        'course_id',
        'payment_status',
        'enrolled_at',
        'is_completed',
        'completed_at',
    ];

    public function course()
    {
        return $this->belongsTo(Course::class);
    }

    public function learner()
    {
        return $this->belongsTo(IndependentLearner::class, 'learner_id');
    }
}