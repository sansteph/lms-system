<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Content extends Model
{
    protected $fillable = [
        'content_title',
        'content_type',
        'assigned_class',
        'priority',
        'access_rule',
        'file_path',
        'status',
    ];
}