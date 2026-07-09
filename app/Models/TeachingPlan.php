<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TeachingPlan extends Model
{
    protected $fillable = [
        'is_template',
        'parent_template_id',
        'title',
        'institute',
        'institute_id',
        'class',
        'section',
        'course_id',
        'course_content_id',
        'content_id',
        'start_date',
        'release_day',
        'contents_per_week',
        'current_batch',
        'release_policy',
        'created_by',
        'plan_start_date',
        'plan_end_date',
        'status',
        'approved_by',
        'approved_at',
        'remarks',
    ];

    protected $casts = [
        'is_template' => 'boolean',
    ];

    public function course()
    {
        return $this->belongsTo(Course::class);
    }

    public function parentTemplate()
    {
        return $this->belongsTo(TeachingPlan::class, 'parent_template_id');
    }

    public function deployedPlans()
    {
        return $this->hasMany(TeachingPlan::class, 'parent_template_id');
    }

    public function courseContent()
    {
        return $this->belongsTo(CourseContent::class);
    }

    public function content()
    {
        return $this->belongsTo(Content::class);
    }

    public function approver()
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function sessions()
    {
        return $this->hasMany(ClassContentSession::class);
    }

    public function weeks()
    {
        return $this->hasMany(TeachingPlanWeek::class);
    }

    public function items()
    {
        return $this->hasMany(TeachingPlanItem::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
