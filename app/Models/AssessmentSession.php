<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AssessmentSession extends Model
{
    protected $fillable = [
        'assessment_id',
        'user_id',
        'user_type',
        'started_at',
        'submitted_at',
        'status',
        'violation_count',
        'last_violation_at',
    ];

    public function assessment()
    {
        return $this->belongsTo(Assessment::class);
    }

    public function student()
    {
        return $this->belongsTo(Student::class, 'user_id');
    }

    public function teacher()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}