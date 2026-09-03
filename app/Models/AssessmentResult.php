<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\Assessment;
use App\Models\AssessmentAnswer;
use App\Models\Student;
use App\Models\User;
use Illuminate\Support\Facades\Storage;

class AssessmentResult extends Model
{
    protected $casts = [
        'evaluated_at' => 'datetime',
    ];

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

    protected static function booted()
    {
        static::deleting(function (AssessmentResult $result) {
            $answerQuery = AssessmentAnswer::where('assessment_result_id', $result->id);

            if ($result->assessment_id && $result->student_id) {
                $answerQuery->orWhere(function ($query) use ($result) {
                    $query->where('assessment_id', $result->assessment_id)
                        ->where('student_id', $result->student_id);
                });
            }

            $answerQuery->delete();

            if ($result->answer_file_path) {
                foreach (['public', 'local'] as $disk) {
                    if (Storage::disk($disk)->exists($result->answer_file_path)) {
                        Storage::disk($disk)->delete($result->answer_file_path);
                    }
                }
            }
        });
    }

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
