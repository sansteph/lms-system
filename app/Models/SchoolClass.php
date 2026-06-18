<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\Content;
use App\Models\ClassTimetable;

class SchoolClass extends Model
{
    protected $table = 'classes';

    protected $fillable = [
        'class_name',
        'section',
        'class_teacher',
        'academic_year',
        'status',
        'institute',
    ];

    public function timetables()
    {
        return $this->hasMany(ClassTimetable::class, 'class_id');
    }
}