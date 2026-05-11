<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\Assessment;  

class AssessmentResult extends Model
{
    public function assessment()
    {
        return $this->belongsTo(Assessment::class, 'assessment_id');
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
