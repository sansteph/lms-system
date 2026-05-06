<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Institute extends Model
{
    protected $fillable = [
        'institute_id',
        'institute_name',
        'location',
        'contact_person',
        'email',
        'phone',
        'status',
    ];
}