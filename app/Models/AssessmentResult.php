<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\Assessment;
use App\Models\Student;

class AssessmentResult extends Model
{
    public function assessment()
    {
        return $this->belongsTo(Assessment::class, 'assessment_id');
    }

    public function student()
    {
        return $this->belongsTo(Student::class, 'student_id');
    }
    protected $fillable = [
        'student_id',
        'assessment_id',
        'score',
        'total_marks',
        'status',
        'badge',
        'percentage',
    ];
}
