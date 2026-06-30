<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ClassTimetable extends Model
{
    protected $fillable = [
        'class_id',
        'content_id',
        'session_date',
        'day',
        'day_type',
        'status',
        'from_time',
        'to_time',
    ];

    public function schoolClass()
    {
        return $this->belongsTo(
            SchoolClass::class,
            'class_id'
        );
    }

    public function content()
    {
        return $this->belongsTo(
            Content::class,
            'content_id'
        );
    }
}