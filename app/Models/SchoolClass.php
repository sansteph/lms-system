<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SchoolClass extends Model
{
    public function content()
    {
        return $this->belongsTo(Content::class);
    }
    protected $table = 'classes';

    protected $fillable = [
        'class_name',
        'section',
        'class_teacher',
        'academic_year',
        'status',
        'institute',
    ];
}
