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
use App\Models\AiQuizAttempt;
use App\Services\Ai\GeminiAiService;
use Barryvdh\DomPDF\Facade\Pdf;

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

        $classScope = SchoolClass::query()
            ->when(session('user_role') == 'InstituteAdmin', function ($query) {
                $query->where('institute', session('user_institute'));
            });

        $classBreakdowns = $classScope
            ->orderBy('institute')
            ->orderBy('class_name')
            ->orderBy('section')
            ->get()
            ->map(function ($class) {
                $studentIds = Student::where('institute', $class->institute)
                    ->where('class', $class->class_name)
                    ->where('section', $class->section)
                    ->pluck('id');
                $aiReviewAttempts = AiQuizAttempt::whereIn('student_id', $studentIds)
                    ->where('attempt_type', 'student')
                    ->whereIn('status', ['passed', 'failed']);

                return [
                    'institute' => $class->institute ?? 'N/A',
                    'class_label' => trim($class->class_name . ' ' . $class->section),
                    'students' => $studentIds->count(),
                    'sessions' => ClassContentSession::where('institute', $class->institute)
                        ->where('class', $class->class_name)
                        ->where('section', $class->section)
                        ->count(),
                    'active_plans' => TeachingPlan::where('institute', $class->institute)
                        ->where('class', $class->class_name)
                        ->where('section', $class->section)
                        ->where('status', 'active')
                        ->count(),
                    'ai_reviews' => (clone $aiReviewAttempts)->count(),
                    'ai_reviews_passed' => (clone $aiReviewAttempts)->where('status', 'passed')->count(),
                    'ai_review_average' => round((clone $aiReviewAttempts)->avg('percentage') ?? 0, 2),
                ];
            });

        $teacherPerformance = User::where('role', 'Teacher')
            ->when(session('user_role') == 'InstituteAdmin', function ($query) {
                $query->where('institute', session('user_institute'));
            })
            ->orderBy('institute')
            ->orderBy('name')
            ->get()
            ->map(function ($teacher) {
                $sessions = ClassContentSession::where('stem_engineer_id', $teacher->id);
                $aiPrepAttempts = AiQuizAttempt::where('teacher_id', $teacher->id)
                    ->where('attempt_type', 'teacher_prep')
                    ->whereIn('status', ['passed', 'failed']);

                return [
                    'institute' => $teacher->institute ?? 'N/A',
                    'name' => $teacher->name,
                    'sessions' => (clone $sessions)->count(),
                    'completed_sessions' => (clone $sessions)->where('status', 'completed')->count(),
                    'partial_sessions' => (clone $sessions)->where('status', 'partially_completed')->count(),
                    'active_sessions' => (clone $sessions)->where('status', 'in_progress')->count(),
                    'hours' => round(((clone $sessions)->sum('duration_seconds') ?? 0) / 3600, 1),
                    'ai_prep' => (clone $aiPrepAttempts)->count(),
                    'ai_prep_passed' => (clone $aiPrepAttempts)->where('status', 'passed')->count(),
                    'ai_prep_average' => round((clone $aiPrepAttempts)->avg('percentage') ?? 0, 2),
                ];
            });

        $instituteBreakdowns = (session('user_role') == 'InstituteAdmin'
                ? Institute::where('institute_name', session('user_institute'))
                : Institute::query()
            )
            ->orderBy('institute_name')
            ->get()
            ->map(function ($institute) {
                $name = $institute->institute_name;

                return [
                    'institute' => $name,
                    'students' => Student::where('institute', $name)->count(),
                    'stem_engineers' => User::where('role', 'Teacher')->where('institute', $name)->count(),
                    'classes' => SchoolClass::where('institute', $name)->count(),
                    'active_sessions' => ClassContentSession::where('institute', $name)->where('status', 'in_progress')->count(),
                    'completed_results' => AssessmentResult::whereHas('student', fn ($q) => $q->where('institute', $name))
                        ->where('status', 'Completed')
                        ->count(),
                ];
            });

        $studentIdsForAi = Student::query()
            ->when(session('user_role') == 'InstituteAdmin', function ($query) {
                $query->where('institute', session('user_institute'));
            })
            ->pluck('id');

        $teacherIdsForAi = User::where('role', 'Teacher')
            ->when(session('user_role') == 'InstituteAdmin', function ($query) {
                $query->where('institute', session('user_institute'));
            })
            ->pluck('id');

        $studentAiReviews = AiQuizAttempt::whereIn('student_id', $studentIdsForAi)
            ->where('attempt_type', 'student')
            ->whereIn('status', ['passed', 'failed']);

        $teacherAiPrep = AiQuizAttempt::whereIn('teacher_id', $teacherIdsForAi)
            ->where('attempt_type', 'teacher_prep')
            ->whereIn('status', ['passed', 'failed']);

        $studentAiReviewCount = (clone $studentAiReviews)->count();
        $studentAiReviewPassedCount = (clone $studentAiReviews)->where('status', 'passed')->count();
        $studentAiReviewAverage = round((clone $studentAiReviews)->avg('percentage') ?? 0, 2);
        $teacherAiPrepCount = (clone $teacherAiPrep)->count();
        $teacherAiPrepPassedCount = (clone $teacherAiPrep)->where('status', 'passed')->count();
        $teacherAiPrepAverage = round((clone $teacherAiPrep)->avg('percentage') ?? 0, 2);

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
            'pendingTeachingItems',
            'instituteBreakdowns',
            'classBreakdowns',
            'teacherPerformance',
            'studentAiReviewCount',
            'studentAiReviewPassedCount',
            'studentAiReviewAverage',
            'teacherAiPrepCount',
            'teacherAiPrepPassedCount',
            'teacherAiPrepAverage'
        ));
    }

    public function generateAiInsights(GeminiAiService $ai)
    {
        $metrics = $this->adminReportAiMetrics();

        try {
            return redirect()
                ->route('reports')
                ->with('aiInsights', $ai->generateReportInsights('Admin LMS Reports', $metrics));
        } catch (\Throwable $exception) {
            return redirect()
                ->route('reports')
                ->with('error', 'AI insights could not be generated: ' . $exception->getMessage());
        }
    }

    public function downloadAiInsights(GeminiAiService $ai)
    {
        $metrics = $this->adminReportAiMetrics();

        try {
            $insights = $ai->generateReportInsights('Admin LMS Reports', $metrics);
        } catch (\Throwable $exception) {
            return redirect()
                ->route('reports')
                ->with('error', 'AI report PDF could not be generated: ' . $exception->getMessage());
        }

        $pdf = Pdf::loadView('pdf.ai-insights-report', [
            'title' => 'AI Generated LMS Report',
            'scope' => $metrics['scope'] ?? 'All Institutes',
            'metrics' => $metrics,
            'insights' => $insights,
        ])->setPaper('a4', 'portrait');

        return $pdf->download('ai_lms_report_' . now()->format('Ymd_His') . '.pdf');
    }

    private function adminReportAiMetrics(): array
    {
        $today = now()->format('Y-m-d');
        $isInstituteAdmin = session('user_role') == 'InstituteAdmin';
        $institute = session('user_institute');
        $scopeLabel = $isInstituteAdmin ? $institute : 'All Institutes';

        $studentQuery = Student::query()
            ->when($isInstituteAdmin, fn ($query) => $query->where('institute', $institute));
        $teacherQuery = User::where('role', 'Teacher')
            ->when($isInstituteAdmin, fn ($query) => $query->where('institute', $institute));
        $classQuery = SchoolClass::query()
            ->when($isInstituteAdmin, fn ($query) => $query->where('institute', $institute));
        $contentQuery = Content::query()
            ->when($isInstituteAdmin, fn ($query) => $query->where('institute', $institute));
        $assessmentQuery = Assessment::query()
            ->when($isInstituteAdmin, fn ($query) => $query->where('institute', $institute));
        $resultQuery = AssessmentResult::query()
            ->when($isInstituteAdmin, function ($query) use ($institute) {
                $query->whereHas('student', fn ($studentQuery) => $studentQuery->where('institute', $institute));
            });
        $certificateQuery = Certificate::query()
            ->when($isInstituteAdmin, function ($query) use ($institute) {
                $query->whereHas('student', fn ($studentQuery) => $studentQuery->where('institute', $institute));
            });
        $sessionQuery = ClassContentSession::query()
            ->when($isInstituteAdmin, fn ($query) => $query->where('institute', $institute));

        return [
            'scope' => $scopeLabel,
            'generated_at' => now()->format('Y-m-d H:i:s'),
            'students' => (clone $studentQuery)->count(),
            'stem_engineers' => (clone $teacherQuery)->count(),
            'classes' => (clone $classQuery)->count(),
            'uploaded_content' => (clone $contentQuery)->count(),
            'released_content' => (clone $contentQuery)->where('is_released', 1)->count(),
            'assessments' => (clone $assessmentQuery)->count(),
            'completed_results' => (clone $resultQuery)->where('status', 'Completed')->count(),
            'pending_manual_reviews' => (clone $resultQuery)->where('status', 'Pending Review')->count(),
            'average_score' => round((clone $resultQuery)->where('status', 'Completed')->avg('percentage') ?? 0, 2),
            'certificates_issued' => (clone $certificateQuery)->count(),
            'total_sessions' => (clone $sessionQuery)->count(),
            'sessions_today' => (clone $sessionQuery)->where('session_date', $today)->count(),
            'completed_sessions_today' => (clone $sessionQuery)->where('session_date', $today)->whereIn('status', ['completed', 'partially_completed'])->count(),
            'active_sessions' => (clone $sessionQuery)->where('status', 'in_progress')->count(),
            'teaching_hours' => round(((clone $sessionQuery)->sum('duration_seconds') ?? 0) / 3600, 1),
            'ai_student_reviews' => (clone $this->studentAiAttemptQuery($isInstituteAdmin, $institute))->count(),
            'ai_student_reviews_passed' => (clone $this->studentAiAttemptQuery($isInstituteAdmin, $institute))->where('status', 'passed')->count(),
            'ai_student_review_average' => round((clone $this->studentAiAttemptQuery($isInstituteAdmin, $institute))->avg('percentage') ?? 0, 2),
            'ai_teacher_prep_quizzes' => (clone $this->teacherAiAttemptQuery($isInstituteAdmin, $institute))->count(),
            'ai_teacher_prep_passed' => (clone $this->teacherAiAttemptQuery($isInstituteAdmin, $institute))->where('status', 'passed')->count(),
            'ai_teacher_prep_average' => round((clone $this->teacherAiAttemptQuery($isInstituteAdmin, $institute))->avg('percentage') ?? 0, 2),
        ];
    }

    private function studentAiAttemptQuery(bool $isInstituteAdmin, ?string $institute)
    {
        $studentIds = Student::query()
            ->when($isInstituteAdmin, fn ($query) => $query->where('institute', $institute))
            ->pluck('id');

        return AiQuizAttempt::whereIn('student_id', $studentIds)
            ->where('attempt_type', 'student')
            ->whereIn('status', ['passed', 'failed']);
    }

    private function teacherAiAttemptQuery(bool $isInstituteAdmin, ?string $institute)
    {
        $teacherIds = User::where('role', 'Teacher')
            ->when($isInstituteAdmin, fn ($query) => $query->where('institute', $institute))
            ->pluck('id');

        return AiQuizAttempt::whereIn('teacher_id', $teacherIds)
            ->where('attempt_type', 'teacher_prep')
            ->whereIn('status', ['passed', 'failed']);
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

        $isInstituteAdmin = session('user_role') == 'InstituteAdmin';
        $institute = session('user_institute');
        $studentAiReviews = $this->studentAiAttemptQuery($isInstituteAdmin, $institute);
        $teacherAiPrep = $this->teacherAiAttemptQuery($isInstituteAdmin, $institute);

        $rows[] = ['AI Progress', 'Student AI Reviews', (clone $studentAiReviews)->count() . ' total | ' . (clone $studentAiReviews)->where('status', 'passed')->count() . ' passed | ' . number_format((clone $studentAiReviews)->avg('percentage') ?? 0, 2) . '% avg', 'Progress Only'];
        $rows[] = ['AI Progress', 'STEM Engineer Prep Quizzes', (clone $teacherAiPrep)->count() . ' total | ' . (clone $teacherAiPrep)->where('status', 'passed')->count() . ' passed | ' . number_format((clone $teacherAiPrep)->avg('percentage') ?? 0, 2) . '% avg', 'Progress Only'];

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
