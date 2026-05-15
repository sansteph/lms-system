<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\Student;

class Certificate extends Model
{
    public function student()
    {
        return $this->belongsTo(Student::class, 'student_id', 'id');
    }
    protected $fillable = [
        'student_id',
        'certificate_code',
        'badge_count',
        'issued_date',
        'status',
    ];
}