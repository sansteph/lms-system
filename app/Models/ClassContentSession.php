<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ClassContentSession extends Model
{
    protected $fillable = [
        'class_id',
        'content_id',
        'stem_engineer_id',
        'started_at',
        'ended_at',
        'duration_seconds',
        'status',
        'timetable_id',
    ];

    public function schoolClass()
    {
        return $this->belongsTo(SchoolClass::class, 'class_id');
    }

    public function content()
    {
        return $this->belongsTo(Content::class, 'content_id');
    }

    public function stemEngineer()
    {
        return $this->belongsTo(User::class, 'stem_engineer_id');
    }

    public function timetable()
    {
        return $this->belongsTo(ClassTimetable::class, 'timetable_id');
    }
}