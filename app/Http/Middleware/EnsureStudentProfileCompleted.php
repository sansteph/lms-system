<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use App\Models\Student;

class EnsureStudentProfileCompleted
{
    public function handle(Request $request, Closure $next): Response
    {
        $student = Student::find(session('student_id'));

        if (!$student) {
            return redirect()->route('student.login');
        }

        if (!$student->profile_completed) {
            return redirect()->route('student.basic-details');
        }

        return $next($request);
    }
}