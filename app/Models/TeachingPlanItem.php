<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TeachingPlanItem extends Model
{
    protected $fillable = [
        'teaching_plan_id',
        'teaching_plan_week_id',
        'course_id',
        'course_content_id',
        'content_id',
        'sort_order',
        'status',
        'released_at',
        'completed_at',
        'completed_by',
        'completed_by_role',
    ];

    protected $casts = [
        'released_at' => 'datetime',
        'completed_at' => 'datetime',
    ];

    public function plan()
    {
        return $this->belongsTo(TeachingPlan::class, 'teaching_plan_id');
    }

    public function week()
    {
        return $this->belongsTo(TeachingPlanWeek::class, 'teaching_plan_week_id');
    }

    public function course()
    {
        return $this->belongsTo(Course::class);
    }

    public function courseContent()
    {
        return $this->belongsTo(CourseContent::class);
    }

    public function content()
    {
        return $this->belongsTo(Content::class);
    }

    public function sessions()
    {
        return $this->hasMany(ClassContentSession::class, 'teaching_plan_item_id');
    }
}
