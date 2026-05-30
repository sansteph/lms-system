<?php

namespace App\Models;
use App\Models\CourseEnrollment;
use App\Models\Certificate;

use Illuminate\Database\Eloquent\Model;

class IndependentLearner extends Model
{
    protected $fillable = [
        'name',
        'email',
        'phone',
        'password',
        'status',
    ];

    public function enrollments()
    {
        return $this->hasMany(
            CourseEnrollment::class,
            'learner_id'
        );
    }

    public function certificates()
    {
        return $this->hasMany(
            Certificate::class,
            'independent_learner_id'
        );
    }
}