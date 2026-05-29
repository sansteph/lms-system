<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class IndependentLearner extends Model
{
    protected $fillable = [
        'name',
        'email',
        'phone',
        'password',
        'status',
    ];
}