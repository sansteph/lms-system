<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ClassContentSession extends Model
{
    protected $casts = [
        'started_at' => 'datetime',
        'ended_at' => 'datetime',
        'duration_seconds' => 'integer',
    ];

    protected $fillable = [
        'institute',
        'class_id',
        'course_id',
        'course_content_id',
        'teaching_plan_id',
        'teaching_plan_week_id',
        'teaching_plan_item_id',
        'content_id',
        'delivered_content_id',
        'stem_engineer_id',
        'class',
        'section',
        'session_day',
        'session_date',
        'start_time',
        'end_time',
        'started_at',
        'ended_at',
        'duration_seconds',
        'status',
        'timetable_id',
        'planned_topic',
        'delivered_topic',
        'remarks',
    ];

    public function schoolClass()
    {
        return $this->belongsTo(SchoolClass::class, 'class_id');
    }

    public function content()
    {
        return $this->belongsTo(Content::class, 'content_id');
    }

    public function course()
    {
        return $this->belongsTo(Course::class);
    }

    public function courseContent()
    {
        return $this->belongsTo(CourseContent::class);
    }

    public function teachingPlan()
    {
        return $this->belongsTo(TeachingPlan::class);
    }

    public function teachingPlanWeek()
    {
        return $this->belongsTo(TeachingPlanWeek::class);
    }

    public function teachingPlanItem()
    {
        return $this->belongsTo(TeachingPlanItem::class);
    }

    public function stemEngineer()
    {
        return $this->belongsTo(User::class, 'stem_engineer_id');
    }

    public function teacher()
    {
        return $this->belongsTo(User::class, 'stem_engineer_id');
    }

    public function timetable()
    {
        return $this->belongsTo(ClassTimetable::class, 'timetable_id');
    }
}
