<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AssessmentAnswer extends Model
{
    protected $fillable = [
        'assessment_result_id',
        'assessment_id',
        'student_id',
        'question_id',
        'question_type',
        'submitted_answer',
        'is_correct',
        'marks_awarded',
        'review_status',
    ];

    public function result()
    {
        return $this->belongsTo(AssessmentResult::class, 'assessment_result_id');
    }

    public function assessment()
    {
        return $this->belongsTo(Assessment::class);
    }

    public function student()
    {
        return $this->belongsTo(Student::class);
    }

    public function question()
    {
        return $this->belongsTo(AssessmentQuestion::class, 'question_id');
    }
}