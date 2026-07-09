<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TeachingPlanWeek extends Model
{
    protected $fillable = [
        'teaching_plan_id',
        'week_number',
        'week_start_date',
        'week_end_date',
        'release_date',
        'status',
        'released_at',
        'completed_at',
        'release_reason',
    ];

    protected $casts = [
        'week_start_date' => 'date',
        'week_end_date' => 'date',
        'release_date' => 'date',
        'released_at' => 'datetime',
        'completed_at' => 'datetime',
    ];

    public function plan()
    {
        return $this->belongsTo(TeachingPlan::class, 'teaching_plan_id');
    }

    public function items()
    {
        return $this->hasMany(TeachingPlanItem::class, 'teaching_plan_week_id');
    }
}
