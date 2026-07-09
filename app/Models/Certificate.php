<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\Student;

class Certificate extends Model
{
    public function independentLearner()
    {
        return $this->belongsTo(IndependentLearner::class);
    }

    public function course()
    {
        return $this->belongsTo(Course::class,'course_id');
    }
    public function student()
    {
        return $this->belongsTo(Student::class, 'student_id', 'id');
    }
    protected $fillable = [
        'student_id',
        'certificate_code',
        'badge_count',
        'final_score',
        'final_grade',
        'final_classification',
        'issued_date',
        'status',
        'approved_by',
        'approved_at',
        'rejection_reason',
        'certificate_type',
        'course_id',
        'independent_learner_id',
    ];
}
