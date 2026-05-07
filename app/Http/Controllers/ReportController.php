<?php

namespace App\Http\Controllers;

use App\Models\Student;
use App\Models\SchoolClass;
use App\Models\User;
use App\Models\Institute;
use App\Models\Content;
use App\Models\Assessment;
use App\Models\Notification;

class ReportController extends Controller
{
    public function index()
    {
        $studentCount = Student::count();
        $teacherCount = User::where('role', 'Teacher')->count();
        $classCount = SchoolClass::count();
        $instituteCount = Institute::count();
        $contentCount = Content::count();
        $assessmentCount = Assessment::count();
        $notificationCount = Notification::count();

        return view('reports', compact(
            'studentCount',
            'teacherCount',
            'classCount',
            'instituteCount',
            'contentCount',
            'assessmentCount',
            'notificationCount'
        ));
    }
}