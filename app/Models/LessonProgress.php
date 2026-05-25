<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LessonProgress extends Model
{
    protected $fillable = [

        'student_id',

        'content_id',

        'is_completed',

        'completed_at',

    ];
}