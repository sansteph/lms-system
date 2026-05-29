<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MySpace extends Model
{
    protected $fillable = [

        'title',

        'description',

        'type',

        'blueprint_pdf',

        'repository_link',

        'created_by_type',

        'created_by_id',

        'status',

    ];

    public function submitter()
    {
        if ($this->created_by_type == 'Teacher') {
            return \App\Models\User::find($this->created_by_id);
        }

        if ($this->created_by_type == 'Student') {
            return \App\Models\Student::find($this->created_by_id);
        }

        return null;
    }
}