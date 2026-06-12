<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\Assessment;
use App\Models\Student;
use App\Models\AssessmentAnswer;

class AssessmentResult extends Model
{
    protected $fillable = [
        'student_id',
        'assessment_id',
        'score',
        'total_marks',
        'status',
        'badge',
        'percentage',
    ];

    public function assessment()
    {
        return $this->belongsTo(
            Assessment::class,
            'assessment_id'
        );
    }

    public function student()
    {
        return $this->belongsTo(
            Student::class,
            'student_id'
        );
    }

    public function answers()
    {
        return $this->hasMany(
            AssessmentAnswer::class,
            'assessment_result_id'
        );
    }
}