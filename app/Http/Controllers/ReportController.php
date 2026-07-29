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
use App\Support\BuildsInstituteSectionPager;
use App\Services\Ai\GeminiAiService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;

class ReportController extends Controller
{
    use BuildsInstituteSectionPager;

    public function index(Request $request)
    {
        $reportMode = request()->route('reportMode') ?? 'overview';
        $today = now()->format('Y-m-d');
        [$periodFrom, $periodTo, $periodLabel] = $this->reportDateWindow($request, $reportMode);
        $sectionPager = null;
        $selectedReportInstitute = null;

        if (session('user_role') == 'Admin') {
            ['currentInstitute' => $selectedReportInstitute, 'sectionPager' => $sectionPager] =
                $this->buildInstituteSectionPager($request, $request->route()?->getName() ?: 'reports.student-ai-review');
        }

        $isInstituteScoped = session('user_role') == 'InstituteAdmin'
            || (session('user_role') == 'Admin' && $selectedReportInstitute);
        $institute = session('user_role') == 'InstituteAdmin'
            ? session('user_institute')
            : $selectedReportInstitute;

        if ($isInstituteScoped) {

            $institute = (string) $institute;

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
            ->when($isInstituteScoped, function ($query) use ($institute) {
                $query->where('institute', $institute);
            });

        $classBreakdowns = $classScope
            ->orderBy('institute')
            ->orderBy('class_name')
            ->orderBy('section')
            ->get()
            ->map(function ($class) use ($periodFrom, $periodTo, $reportMode) {
                $studentIds = Student::where('institute', $class->institute)
                    ->where('class', $class->class_name)
                    ->where('section', $class->section)
                    ->pluck('id');
                $aiReviewAttempts = AiQuizAttempt::whereIn('student_id', $studentIds)
                    ->where('attempt_type', 'student')
                    ->whereIn('status', ['passed', 'failed'])
                    ->when($periodFrom, fn ($query) => $query->whereDate('submitted_at', '>=', $periodFrom))
                    ->when($periodTo, fn ($query) => $query->whereDate('submitted_at', '<=', $periodTo));

                $sessionQuery = ClassContentSession::where('institute', $class->institute)
                    ->where('class', $class->class_name)
                    ->where('section', $class->section)
                    ->when($periodFrom, fn ($query) => $query->whereDate('session_date', '>=', $periodFrom))
                    ->when($periodTo, fn ($query) => $query->whereDate('session_date', '<=', $periodTo));

                $assessmentResultQuery = AssessmentResult::whereIn('student_id', $studentIds)
                    ->where('status', 'Completed')
                    ->when($periodFrom, fn ($query) => $query->whereDate('evaluated_at', '>=', $periodFrom))
                    ->when($periodTo, fn ($query) => $query->whereDate('evaluated_at', '<=', $periodTo));

                return [
                    'institute' => $class->institute ?? 'N/A',
                    'class_label' => trim($class->class_name . ' ' . $class->section),
                    'students' => $studentIds->count(),
                    'sessions' => (clone $sessionQuery)->count(),
                    'active_plans' => TeachingPlan::where('institute', $class->institute)
                        ->where('class', $class->class_name)
                        ->where('section', $class->section)
                        ->where('status', 'active')
                        ->count(),
                    'ai_reviews' => (clone $aiReviewAttempts)->count(),
                    'ai_reviews_passed' => (clone $aiReviewAttempts)->where('status', 'passed')->count(),
                    'ai_review_average' => round((clone $aiReviewAttempts)->avg('percentage') ?? 0, 2),
                    'assessment_results' => (clone $assessmentResultQuery)->count(),
                    'assessment_average' => round((clone $assessmentResultQuery)->avg('percentage') ?? 0, 2),
                ];
            });

        $teacherPerformance = User::where('role', 'Teacher')
            ->when($isInstituteScoped, function ($query) use ($institute) {
                $query->where('institute', $institute);
            })
            ->orderBy('institute')
            ->orderBy('name')
            ->get()
            ->map(function ($teacher) use ($periodFrom, $periodTo) {
                $sessions = ClassContentSession::where('stem_engineer_id', $teacher->id)
                    ->when($periodFrom, fn ($query) => $query->whereDate('session_date', '>=', $periodFrom))
                    ->when($periodTo, fn ($query) => $query->whereDate('session_date', '<=', $periodTo));
                $aiPrepAttempts = AiQuizAttempt::where('teacher_id', $teacher->id)
                    ->where('attempt_type', 'teacher_prep')
                    ->whereIn('status', ['passed', 'failed'])
                    ->when($periodFrom, fn ($query) => $query->whereDate('submitted_at', '>=', $periodFrom))
                    ->when($periodTo, fn ($query) => $query->whereDate('submitted_at', '<=', $periodTo));

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
                : ($isInstituteScoped
                    ? Institute::where('institute_name', $institute)
                    : Institute::query())
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
            ->when($isInstituteScoped, function ($query) use ($institute) {
                $query->where('institute', $institute);
            })
            ->pluck('id');

        $teacherIdsForAi = User::where('role', 'Teacher')
            ->when($isInstituteScoped, function ($query) use ($institute) {
                $query->where('institute', $institute);
            })
            ->pluck('id');

        $studentAiReviews = AiQuizAttempt::whereIn('student_id', $studentIdsForAi)
            ->where('attempt_type', 'student')
            ->whereIn('status', ['passed', 'failed'])
            ->when($periodFrom, fn ($query) => $query->whereDate('submitted_at', '>=', $periodFrom))
            ->when($periodTo, fn ($query) => $query->whereDate('submitted_at', '<=', $periodTo));

        $teacherAiPrep = AiQuizAttempt::whereIn('teacher_id', $teacherIdsForAi)
            ->where('attempt_type', 'teacher_prep')
            ->whereIn('status', ['passed', 'failed'])
            ->when($periodFrom, fn ($query) => $query->whereDate('submitted_at', '>=', $periodFrom))
            ->when($periodTo, fn ($query) => $query->whereDate('submitted_at', '<=', $periodTo));

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
            'teacherAiPrepAverage',
            'reportMode',
            'sectionPager',
            'periodFrom',
            'periodTo',
            'periodLabel'
        ));
    }

    public function downloadPdf(Request $request, GeminiAiService $ai)
    {
        $reportMode = $request->route('reportMode') ?? 'student-ai-review';
        $payload = $this->downloadableReportPayload($request, $reportMode);

        try {
            $insights = $ai->generateReportInsights($payload['title'], $payload['metrics']);
        } catch (\Throwable $exception) {
            return redirect()
                ->back()
                ->with('error', 'AI report PDF could not be generated: ' . $exception->getMessage());
        }

        $pdf = Pdf::loadView('pdf.generated-lms-report', [
            'title' => $payload['title'],
            'scope' => $payload['scope'],
            'periodLabel' => $payload['period_label'],
            'metrics' => $payload['metrics'],
            'tableTitle' => $payload['table_title'],
            'tableHeaders' => $payload['table_headers'],
            'tableRows' => $payload['table_rows'],
            'visuals' => $payload['visuals'],
            'insights' => $insights,
        ])->setPaper('a4', 'portrait');

        return $pdf->download($payload['file_name']);
    }

    private function downloadableReportPayload(Request $request, string $reportMode): array
    {
        [$periodFrom, $periodTo, $periodLabel] = $this->reportDateWindow($request, $reportMode);
        $institute = $this->selectedReportInstitute($request, $this->reportDownloadRouteName($reportMode));
        $scope = $institute ?: 'All Institutes';
        $isStudentReport = in_array($reportMode, ['student-ai-review', 'weekly-student-performance', 'monthly-student-performance'], true);
        $isTeacherReport = in_array($reportMode, ['stem-engineer-prep', 'weekly-stem-engineer-performance', 'monthly-stem-engineer-performance'], true);

        $studentIds = Student::query()
            ->when($institute, fn ($query) => $query->where('institute', $institute))
            ->pluck('id');

        $teacherIds = User::where('role', 'Teacher')
            ->when($institute, fn ($query) => $query->where('institute', $institute))
            ->pluck('id');

        $studentAttempts = AiQuizAttempt::whereIn('student_id', $studentIds)
            ->where('attempt_type', 'student')
            ->whereIn('status', ['passed', 'failed'])
            ->when($periodFrom, fn ($query) => $query->whereDate('submitted_at', '>=', $periodFrom))
            ->when($periodTo, fn ($query) => $query->whereDate('submitted_at', '<=', $periodTo));

        $teacherAttempts = AiQuizAttempt::whereIn('teacher_id', $teacherIds)
            ->where('attempt_type', 'teacher_prep')
            ->whereIn('status', ['passed', 'failed'])
            ->when($periodFrom, fn ($query) => $query->whereDate('submitted_at', '>=', $periodFrom))
            ->when($periodTo, fn ($query) => $query->whereDate('submitted_at', '<=', $periodTo));

        $sessions = ClassContentSession::query()
            ->when($institute, fn ($query) => $query->where('institute', $institute))
            ->when($periodFrom, fn ($query) => $query->whereDate('session_date', '>=', $periodFrom))
            ->when($periodTo, fn ($query) => $query->whereDate('session_date', '<=', $periodTo));

        $results = AssessmentResult::whereIn('student_id', $studentIds)
            ->where('status', 'Completed')
            ->when($periodFrom, fn ($query) => $query->whereDate('evaluated_at', '>=', $periodFrom))
            ->when($periodTo, fn ($query) => $query->whereDate('evaluated_at', '<=', $periodTo));

        $studentAttemptCount = (clone $studentAttempts)->count();
        $studentPassedCount = (clone $studentAttempts)->where('status', 'passed')->count();
        $teacherAttemptCount = (clone $teacherAttempts)->count();
        $teacherPassedCount = (clone $teacherAttempts)->where('status', 'passed')->count();
        $sessionCount = (clone $sessions)->count();
        $completedSessions = (clone $sessions)->where('status', 'completed')->count();
        $resultCount = (clone $results)->count();

        $metrics = [
            'scope' => $scope,
            'period' => $periodLabel,
            'students' => $studentIds->count(),
            'stem_engineers' => $teacherIds->count(),
            'student_ai_attempts' => $studentAttemptCount,
            'student_ai_passed' => $studentPassedCount,
            'student_ai_pass_rate' => $studentAttemptCount ? round(($studentPassedCount / $studentAttemptCount) * 100, 2) : 0,
            'student_ai_average' => round((clone $studentAttempts)->avg('percentage') ?? 0, 2),
            'stem_engineer_prep_attempts' => $teacherAttemptCount,
            'stem_engineer_prep_passed' => $teacherPassedCount,
            'stem_engineer_prep_pass_rate' => $teacherAttemptCount ? round(($teacherPassedCount / $teacherAttemptCount) * 100, 2) : 0,
            'stem_engineer_prep_average' => round((clone $teacherAttempts)->avg('percentage') ?? 0, 2),
            'sessions' => $sessionCount,
            'completed_sessions' => $completedSessions,
            'session_completion_rate' => $sessionCount ? round(($completedSessions / $sessionCount) * 100, 2) : 0,
            'teaching_hours' => round(((clone $sessions)->sum('duration_seconds') ?? 0) / 3600, 2),
            'assessment_results' => $resultCount,
            'assessment_average' => round((clone $results)->avg('percentage') ?? 0, 2),
        ];

        $title = match ($reportMode) {
            'student-ai-review' => 'Weekly Student AI Review Report',
            'stem-engineer-prep' => 'Weekly STEM Engineer Prep Report',
            'weekly-student-performance' => 'Weekly Student Performance Report',
            'monthly-student-performance' => 'Monthly Student Performance Report',
            'weekly-stem-engineer-performance' => 'Weekly STEM Engineer Performance Report',
            'monthly-stem-engineer-performance' => 'Monthly STEM Engineer Performance Report',
            default => 'LMS Report',
        };

        $tableRows = $isTeacherReport
            ? $this->teacherReportRows($teacherIds, $periodFrom, $periodTo)
            : $this->studentClassReportRows($studentIds, $periodFrom, $periodTo);

        return [
            'title' => $title,
            'scope' => $scope,
            'period_label' => $periodLabel,
            'metrics' => $metrics,
            'table_title' => $isTeacherReport ? 'STEM Engineer Metrics' : 'Class-wise Student Metrics',
            'table_headers' => $isTeacherReport
                ? ['STEM Engineer', 'Institute', 'Sessions', 'Completed', 'Hours', 'Prep Attempts', 'Prep Passed', 'Prep Avg']
                : ['Class', 'Institute', 'Students', 'Assessments', 'Assessment Avg', 'AI Attempts', 'AI Passed', 'AI Avg'],
            'table_rows' => $tableRows,
            'visuals' => $this->reportVisuals($metrics, $isStudentReport, $isTeacherReport),
            'file_name' => (string) str($title)->slug('_') . '_' . now()->format('Ymd_His') . '.pdf',
        ];
    }

    private function selectedReportInstitute(Request $request, string $routeName): ?string
    {
        if (session('user_role') == 'InstituteAdmin') {
            return session('user_institute');
        }

        if (session('user_role') != 'Admin') {
            return null;
        }

        ['currentInstitute' => $currentInstitute] = $this->buildInstituteSectionPager($request, $routeName);

        return $currentInstitute;
    }

    private function reportDownloadRouteName(string $reportMode): string
    {
        return match ($reportMode) {
            'student-ai-review' => 'reports.student-ai-review.download',
            'stem-engineer-prep' => 'reports.stem-engineer-prep.download',
            'weekly-student-performance' => 'reports.student-performance.weekly.download',
            'monthly-student-performance' => 'reports.student-performance.monthly.download',
            'weekly-stem-engineer-performance' => 'reports.stem-engineer-performance.weekly.download',
            'monthly-stem-engineer-performance' => 'reports.stem-engineer-performance.monthly.download',
            default => 'reports.student-ai-review.download',
        };
    }

    private function studentClassReportRows($studentIds, ?string $periodFrom, ?string $periodTo): array
    {
        return SchoolClass::orderBy('institute')
            ->orderBy('class_name')
            ->orderBy('section')
            ->get()
            ->map(function ($class) use ($studentIds, $periodFrom, $periodTo) {
                $classStudentIds = Student::whereIn('id', $studentIds)
                    ->where('institute', $class->institute)
                    ->where('class', $class->class_name)
                    ->where('section', $class->section)
                    ->pluck('id');

                if ($classStudentIds->isEmpty()) {
                    return null;
                }

                $results = AssessmentResult::whereIn('student_id', $classStudentIds)
                    ->where('status', 'Completed')
                    ->when($periodFrom, fn ($query) => $query->whereDate('evaluated_at', '>=', $periodFrom))
                    ->when($periodTo, fn ($query) => $query->whereDate('evaluated_at', '<=', $periodTo));

                $attempts = AiQuizAttempt::whereIn('student_id', $classStudentIds)
                    ->where('attempt_type', 'student')
                    ->whereIn('status', ['passed', 'failed'])
                    ->when($periodFrom, fn ($query) => $query->whereDate('submitted_at', '>=', $periodFrom))
                    ->when($periodTo, fn ($query) => $query->whereDate('submitted_at', '<=', $periodTo));

                return [
                    trim($class->class_name . ' ' . $class->section),
                    $class->institute,
                    $classStudentIds->count(),
                    (clone $results)->count(),
                    number_format((clone $results)->avg('percentage') ?? 0, 2) . '%',
                    (clone $attempts)->count(),
                    (clone $attempts)->where('status', 'passed')->count(),
                    number_format((clone $attempts)->avg('percentage') ?? 0, 2) . '%',
                ];
            })
            ->filter()
            ->take(40)
            ->values()
            ->all();
    }

    private function teacherReportRows($teacherIds, ?string $periodFrom, ?string $periodTo): array
    {
        return User::whereIn('id', $teacherIds)
            ->orderBy('institute')
            ->orderBy('name')
            ->get()
            ->map(function ($teacher) use ($periodFrom, $periodTo) {
                $sessions = ClassContentSession::where('stem_engineer_id', $teacher->id)
                    ->when($periodFrom, fn ($query) => $query->whereDate('session_date', '>=', $periodFrom))
                    ->when($periodTo, fn ($query) => $query->whereDate('session_date', '<=', $periodTo));

                $attempts = AiQuizAttempt::where('teacher_id', $teacher->id)
                    ->where('attempt_type', 'teacher_prep')
                    ->whereIn('status', ['passed', 'failed'])
                    ->when($periodFrom, fn ($query) => $query->whereDate('submitted_at', '>=', $periodFrom))
                    ->when($periodTo, fn ($query) => $query->whereDate('submitted_at', '<=', $periodTo));

                return [
                    $teacher->name,
                    $teacher->institute,
                    (clone $sessions)->count(),
                    (clone $sessions)->where('status', 'completed')->count(),
                    number_format(((clone $sessions)->sum('duration_seconds') ?? 0) / 3600, 2),
                    (clone $attempts)->count(),
                    (clone $attempts)->where('status', 'passed')->count(),
                    number_format((clone $attempts)->avg('percentage') ?? 0, 2) . '%',
                ];
            })
            ->take(40)
            ->values()
            ->all();
    }

    private function reportVisuals(array $metrics, bool $isStudentReport, bool $isTeacherReport): array
    {
        $visuals = [];

        if ($isStudentReport) {
            $visuals[] = [
                'title' => 'Student AI Review Pass Rate',
                'labels' => ['Passed', 'Remaining'],
                'values' => [$metrics['student_ai_passed'], max(0, $metrics['student_ai_attempts'] - $metrics['student_ai_passed'])],
            ];
            $visuals[] = [
                'title' => 'Assessment Average vs Target',
                'labels' => ['Average', 'Target'],
                'values' => [$metrics['assessment_average'], 60],
            ];
        }

        if ($isTeacherReport) {
            $visuals[] = [
                'title' => 'STEM Engineer Prep Pass Rate',
                'labels' => ['Passed', 'Remaining'],
                'values' => [$metrics['stem_engineer_prep_passed'], max(0, $metrics['stem_engineer_prep_attempts'] - $metrics['stem_engineer_prep_passed'])],
            ];
            $visuals[] = [
                'title' => 'Session Completion',
                'labels' => ['Completed', 'Remaining'],
                'values' => [$metrics['completed_sessions'], max(0, $metrics['sessions'] - $metrics['completed_sessions'])],
            ];
        }

        return $visuals;
    }

    private function reportDateWindow(Request $request, string $reportMode): array
    {
        if (str_starts_with($reportMode, 'monthly-')) {
            $month = $request->input('report_month', now()->format('Y-m'));
            $start = \Carbon\Carbon::parse($month . '-01')->startOfMonth();
            $end = $start->copy()->endOfMonth();

            return [$start->toDateString(), $end->toDateString(), $start->format('F Y')];
        }

        if (str_starts_with($reportMode, 'weekly-')) {
            $start = $request->filled('from_date')
                ? \Carbon\Carbon::parse($request->from_date)
                : now()->startOfWeek();
            $end = $request->filled('to_date')
                ? \Carbon\Carbon::parse($request->to_date)
                : now()->endOfWeek();

            return [$start->toDateString(), $end->toDateString(), $start->format('d M Y') . ' - ' . $end->format('d M Y')];
        }

        if (in_array($reportMode, ['student-ai-review', 'stem-engineer-prep'], true)) {
            $start = $request->filled('from_date')
                ? \Carbon\Carbon::parse($request->from_date)
                : now()->startOfWeek();
            $end = $request->filled('to_date')
                ? \Carbon\Carbon::parse($request->to_date)
                : now()->endOfWeek();

            return [$start->toDateString(), $end->toDateString(), $start->format('d M Y') . ' - ' . $end->format('d M Y')];
        }

        return [null, null, 'All available data'];
    }

}
