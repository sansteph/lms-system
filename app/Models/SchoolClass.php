<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

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
}
