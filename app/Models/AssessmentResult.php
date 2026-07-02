<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\Assessment;
use App\Models\Student;
use App\Models\User;

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
        'answer_text',
        'answer_file_path',
        'feedback',
        'passed',
        'evaluated_by',
        'evaluated_at',
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

    public function evaluator()
    {
        return $this->belongsTo(User::class, 'evaluated_by');
    }
}
