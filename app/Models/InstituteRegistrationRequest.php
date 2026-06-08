<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class InstituteRegistrationRequest extends Model
{
    protected $fillable = [
        'request_id',
        'admin_name',
        'admin_email',
        'phone',
        'institute_name',
        'location',
        'status',
        'remarks',
    ];
}