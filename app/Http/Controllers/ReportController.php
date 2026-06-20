<?php

namespace App\Http\Controllers;

use App\Models\Student;
use App\Models\SchoolClass;
use App\Models\User;
use App\Models\Institute;
use App\Models\Content;
use App\Models\Assessment;
use App\Models\Notification;
use App\Models\AssessmentResult;
use App\Models\Certificate;
use App\Models\ClassContentSession;
use App\Models\ClassTimetable;

class ReportController extends Controller
{
    public function index()
    {
        $today = now()->format('Y-m-d');

        if (session('user_role') == 'InstituteAdmin') {

            $institute = session('user_institute');

            $studentCount = Student::where('institute', $institute)->count();

            $teacherCount = User::where('role', 'Teacher')
                ->where('institute', $institute)
                ->count();

            $classCount = SchoolClass::where('institute', $institute)->count();

            $instituteCount = 1;

            $contentCount = Content::where('institute', $institute)->count();

            $assessmentCount = Assessment::where('institute', $institute)->count();

            $notificationCount = Notification::where('institute', $institute)->count();

            $completedResults = AssessmentResult::whereHas('student', function ($q) use ($institute) {
                    $q->where('institute', $institute);
                })
                ->where('status', 'Completed')
                ->count();

            $pendingReviewResults = AssessmentResult::whereHas('student', function ($q) use ($institute) {
                    $q->where('institute', $institute);
                })
                ->where('status', 'Pending Review')
                ->count();

            $averageScore = AssessmentResult::whereHas('student', function ($q) use ($institute) {
                    $q->where('institute', $institute);
                })
                ->where('status', 'Completed')
                ->avg('percentage') ?? 0;

            $certificateCount = Certificate::whereHas('student', function ($q) use ($institute) {
                    $q->where('institute', $institute);
                })
                ->count();

            $classSessionCount = ClassContentSession::whereHas('schoolClass', function ($q) use ($institute) {
                    $q->where('institute', $institute);
                })
                ->count();

            // New Analytics

            $todayClassCount = ClassTimetable::whereHas('schoolClass', function ($q) use ($institute) {
                    $q->where('institute', $institute);
                })
                ->where('session_date', $today)
                ->count();

            $todayCompletedSessions = ClassTimetable::whereHas('schoolClass', function ($q) use ($institute) {
                    $q->where('institute', $institute);
                })
                ->where('session_date', $today)
                ->where('status', 'Completed')
                ->count();

            $activeSessions = ClassContentSession::whereHas('schoolClass', function ($q) use ($institute) {
                    $q->where('institute', $institute);
                })
                ->where('status', 'Started')
                ->count();

            $totalTeachingHours = ClassContentSession::whereHas('schoolClass', function ($q) use ($institute) {
                    $q->where('institute', $institute);
                })
                ->sum('duration_seconds');

            $totalTeachingHours = round($totalTeachingHours / 3600, 1);

            $contentReleasedCount = Content::where('institute', $institute)
                ->where('is_released', 1)
                ->count();

        } else {

            $studentCount = Student::count();

            $teacherCount = User::where('role', 'Teacher')->count();

            $classCount = SchoolClass::count();

            $instituteCount = Institute::count();

            $contentCount = Content::count();

            $assessmentCount = Assessment::count();

            $notificationCount = Notification::count();

            $completedResults = AssessmentResult::where('status', 'Completed')->count();

            $pendingReviewResults = AssessmentResult::where('status', 'Pending Review')->count();

            $averageScore = AssessmentResult::where('status', 'Completed')
                ->avg('percentage') ?? 0;

            $certificateCount = Certificate::count();

            $classSessionCount = ClassContentSession::count();

            $todayClassCount = ClassTimetable::where('session_date', $today)
                ->count();

            $todayCompletedSessions = ClassTimetable::where('session_date', $today)
                ->where('status', 'Completed')
                ->count();

            $activeSessions = ClassContentSession::where('status', 'Started')
                ->count();

            $totalTeachingHours = round(
                ClassContentSession::sum('duration_seconds') / 3600,
                1
            );

            $contentReleasedCount = Content::where('is_released', 1)
                ->count();
        }

        return view('reports', compact(
            'studentCount',
            'teacherCount',
            'classCount',
            'instituteCount',
            'contentCount',
            'assessmentCount',
            'notificationCount',
            'completedResults',
            'pendingReviewResults',
            'averageScore',
            'certificateCount',
            'classSessionCount',
            'todayClassCount',
            'todayCompletedSessions',
            'activeSessions',
            'totalTeachingHours',
            'contentReleasedCount'
        ));
    }
}
