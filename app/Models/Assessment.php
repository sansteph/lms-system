<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Assessment extends Model
{
    protected $fillable = [
        'assessment_title',
        'assessment_type',
        'assigned_class',
        'total_marks',
        'duration',
        'question_paper_type',
        'file_path',
        'status',
    ];
}