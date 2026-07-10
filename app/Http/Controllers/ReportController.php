<?php

namespace App\Http\Controllers;

use App\Models\Student;
use App\Models\SchoolClass;
use App\Models\User;
use App\Models\Institute;
use App\Models\Content;
use App\Models\Assessment;
use App\Models\AssessmentResult;
use App\Models\Certificate;
use App\Models\ClassContentSession;
use App\Models\TeachingPlan;
use App\Models\TeachingPlanItem;
use App\Models\TeachingPlanWeek;

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

            $classSessionCount = ClassContentSession::where('institute', $institute)
                ->count();

            $approvedTeachingPlans = TeachingPlan::where('institute', $institute)
                ->where('status', 'active')
                ->count();

            $pendingTeachingPlans = TeachingPlan::where('institute', $institute)
                ->where('status', 'inactive')
                ->count();

            $releasedTeachingWeeks = TeachingPlanWeek::whereHas('plan', function ($query) use ($institute) {
                    $query->where('institute', $institute);
                })
                ->where('status', 'released')
                ->count();

            $lockedTeachingWeeks = TeachingPlanWeek::whereHas('plan', function ($query) use ($institute) {
                    $query->where('institute', $institute);
                })
                ->where('status', 'locked')
                ->count();

            $completedTeachingWeeks = TeachingPlanWeek::whereHas('plan', function ($query) use ($institute) {
                    $query->where('institute', $institute);
                })
                ->where('status', 'completed')
                ->count();

            $pendingTeachingItems = TeachingPlanItem::whereHas('plan', function ($query) use ($institute) {
                    $query->where('institute', $institute);
                })
                ->whereIn('status', ['locked', 'released'])
                ->count();

            $todayClassCount = ClassContentSession::where('institute', $institute)
                ->where('session_date', $today)
                ->count();

            $todayCompletedSessions = ClassContentSession::where('institute', $institute)
                ->where('session_date', $today)
                ->whereIn('status', ['completed', 'partially_completed'])
                ->count();

            $activeSessions = ClassContentSession::where('institute', $institute)
                ->where('status', 'in_progress')
                ->count();

            $totalTeachingHours = ClassContentSession::where('institute', $institute)
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

            $completedResults = AssessmentResult::where('status', 'Completed')->count();

            $pendingReviewResults = AssessmentResult::where('status', 'Pending Review')->count();

            $averageScore = AssessmentResult::where('status', 'Completed')
                ->avg('percentage') ?? 0;

            $certificateCount = Certificate::count();

            $classSessionCount = ClassContentSession::count();

            $approvedTeachingPlans = TeachingPlan::where('status', 'active')->count();

            $pendingTeachingPlans = TeachingPlan::where('status', 'inactive')->count();

            $releasedTeachingWeeks = TeachingPlanWeek::where('status', 'released')->count();

            $lockedTeachingWeeks = TeachingPlanWeek::where('status', 'locked')->count();

            $completedTeachingWeeks = TeachingPlanWeek::where('status', 'completed')->count();

            $pendingTeachingItems = TeachingPlanItem::whereIn('status', ['locked', 'released'])->count();

            $todayClassCount = ClassContentSession::where('session_date', $today)
                ->count();

            $todayCompletedSessions = ClassContentSession::where('session_date', $today)
                ->whereIn('status', ['completed', 'partially_completed'])
                ->count();

            $activeSessions = ClassContentSession::where('status', 'in_progress')
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
            'completedResults',
            'pendingReviewResults',
            'averageScore',
            'certificateCount',
            'classSessionCount',
            'todayClassCount',
            'todayCompletedSessions',
            'activeSessions',
            'totalTeachingHours',
            'contentReleasedCount',
            'approvedTeachingPlans',
            'pendingTeachingPlans',
            'releasedTeachingWeeks',
            'lockedTeachingWeeks',
            'completedTeachingWeeks',
            'pendingTeachingItems'
        ));
    }

    public function exportCsv()
    {
        $today = now()->format('Y-m-d');
        $scopeLabel = session('user_role') == 'InstituteAdmin'
            ? session('user_institute')
            : 'All Institutes';

        if (session('user_role') == 'InstituteAdmin') {
            $institute = session('user_institute');

            $studentCount = Student::where('institute', $institute)->count();
            $teacherCount = User::where('role', 'Teacher')->where('institute', $institute)->count();
            $classCount = SchoolClass::where('institute', $institute)->count();
            $instituteCount = 1;
            $contentCount = Content::where('institute', $institute)->count();
            $assessmentCount = Assessment::where('institute', $institute)->count();
            $completedResults = AssessmentResult::whereHas('student', fn ($q) => $q->where('institute', $institute))
                ->where('status', 'Completed')
                ->count();
            $pendingReviewResults = AssessmentResult::whereHas('student', fn ($q) => $q->where('institute', $institute))
                ->where('status', 'Pending Review')
                ->count();
            $averageScore = AssessmentResult::whereHas('student', fn ($q) => $q->where('institute', $institute))
                ->where('status', 'Completed')
                ->avg('percentage') ?? 0;
            $certificateCount = Certificate::whereHas('student', fn ($q) => $q->where('institute', $institute))->count();
            $classSessionCount = ClassContentSession::where('institute', $institute)->count();
            $todayClassCount = ClassContentSession::where('institute', $institute)->where('session_date', $today)->count();
            $todayCompletedSessions = ClassContentSession::where('institute', $institute)
                ->where('session_date', $today)
                ->whereIn('status', ['completed', 'partially_completed'])
                ->count();
            $activeSessions = ClassContentSession::where('institute', $institute)->where('status', 'in_progress')->count();
            $totalTeachingHours = round(ClassContentSession::where('institute', $institute)->sum('duration_seconds') / 3600, 1);
            $contentReleasedCount = Content::where('institute', $institute)->where('is_released', 1)->count();
            $approvedTeachingPlans = TeachingPlan::where('institute', $institute)->where('status', 'active')->count();
            $pendingTeachingPlans = TeachingPlan::where('institute', $institute)->where('status', 'inactive')->count();
            $releasedTeachingWeeks = TeachingPlanWeek::whereHas('plan', fn ($query) => $query->where('institute', $institute))
                ->where('status', 'released')
                ->count();
            $lockedTeachingWeeks = TeachingPlanWeek::whereHas('plan', fn ($query) => $query->where('institute', $institute))
                ->where('status', 'locked')
                ->count();
            $completedTeachingWeeks = TeachingPlanWeek::whereHas('plan', fn ($query) => $query->where('institute', $institute))
                ->where('status', 'completed')
                ->count();
            $pendingTeachingItems = TeachingPlanItem::whereHas('plan', fn ($query) => $query->where('institute', $institute))
                ->whereIn('status', ['locked', 'released'])
                ->count();
        } else {
            $studentCount = Student::count();
            $teacherCount = User::where('role', 'Teacher')->count();
            $classCount = SchoolClass::count();
            $instituteCount = Institute::count();
            $contentCount = Content::count();
            $assessmentCount = Assessment::count();
            $completedResults = AssessmentResult::where('status', 'Completed')->count();
            $pendingReviewResults = AssessmentResult::where('status', 'Pending Review')->count();
            $averageScore = AssessmentResult::where('status', 'Completed')->avg('percentage') ?? 0;
            $certificateCount = Certificate::count();
            $classSessionCount = ClassContentSession::count();
            $todayClassCount = ClassContentSession::where('session_date', $today)->count();
            $todayCompletedSessions = ClassContentSession::where('session_date', $today)
                ->whereIn('status', ['completed', 'partially_completed'])
                ->count();
            $activeSessions = ClassContentSession::where('status', 'in_progress')->count();
            $totalTeachingHours = round(ClassContentSession::sum('duration_seconds') / 3600, 1);
            $contentReleasedCount = Content::where('is_released', 1)->count();
            $approvedTeachingPlans = TeachingPlan::where('status', 'active')->count();
            $pendingTeachingPlans = TeachingPlan::where('status', 'inactive')->count();
            $releasedTeachingWeeks = TeachingPlanWeek::where('status', 'released')->count();
            $lockedTeachingWeeks = TeachingPlanWeek::where('status', 'locked')->count();
            $completedTeachingWeeks = TeachingPlanWeek::where('status', 'completed')->count();
            $pendingTeachingItems = TeachingPlanItem::whereIn('status', ['locked', 'released'])->count();
        }

        $rows = [
            ['Report Scope', $scopeLabel],
            ['Generated At', now()->format('Y-m-d H:i:s')],
            [],
            ['Report Area', 'Metric', 'Current Value', 'Status'],
            ['Institutes', 'Total Institutes', $instituteCount, 'Live'],
            ['Students', 'Total Registered Students', $studentCount, 'Live'],
            ['STEM Engineers', 'Total STEM Engineers', $teacherCount, 'Live'],
            ['Classes', 'Total Classes', $classCount, 'Live'],
            ['Content', 'Total Uploaded Content', $contentCount, 'Live'],
            ['Content', 'Released Learning Content', $contentReleasedCount, 'Available'],
            ['Assessments', 'Total Assessments', $assessmentCount, 'Live'],
            ['Assessment Results', 'Completed Results', $completedResults, 'Completed'],
            ['Assessment Results', 'Pending Manual Review', $pendingReviewResults, 'Pending'],
            ['Performance', 'Average Score', number_format($averageScore, 2) . '%', 'Calculated'],
            ['Certificates', 'Total Certificates Issued', $certificateCount, 'Live'],
            ['Class Sessions', 'Total Sessions Conducted', $classSessionCount, 'Tracked'],
            ['Class Sessions', 'Classes Scheduled Today', $todayClassCount, 'Scheduled'],
            ['Class Sessions', 'Sessions Completed Today', $todayCompletedSessions, 'Completed'],
            ['Class Sessions', 'Active Live Sessions', $activeSessions, 'Live'],
            ['Class Sessions', 'Total Teaching Hours Delivered', $totalTeachingHours, 'Tracked'],
            ['Teaching Plans', 'Active Plans', $approvedTeachingPlans, 'Active'],
            ['Teaching Plans', 'Inactive Plans', $pendingTeachingPlans, 'Inactive'],
            ['Teaching Plan Weeks', 'Released Weeks', $releasedTeachingWeeks, 'Released'],
            ['Teaching Plan Weeks', 'Locked Weeks', $lockedTeachingWeeks, 'Locked'],
            ['Teaching Plan Weeks', 'Completed Weeks', $completedTeachingWeeks, 'Completed'],
            ['Teaching Plan Items', 'Pending Plan Items', $pendingTeachingItems, 'Pending'],
        ];

        $filename = 'admin_report_' . now()->format('Ymd_His') . '.csv';

        return response()->streamDownload(function () use ($rows) {
            $file = fopen('php://output', 'w');

            foreach ($rows as $row) {
                fputcsv($file, $row);
            }

            fclose($file);
        }, $filename, [
            'Content-Type' => 'text/csv',
        ]);
    }
}
