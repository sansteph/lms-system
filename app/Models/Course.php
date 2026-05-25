<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Course extends Model
{
    protected $fillable = [

        'course_title',

        'description',

        'target',

        'assigned_class',

        'certificate_enabled',

        'status',

    ];

    public function contents()
    {
        return $this->hasMany(Content::class);
    }
}