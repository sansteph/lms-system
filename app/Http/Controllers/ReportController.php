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
        $selectedReportInstitute = null;
        $selectedStudentReportClass = null;
        $selectedStudentReportSection = null;
        $reportInstituteOptions = session('user_role') == 'Admin'
            ? Institute::where('status', 1)->orderBy('institute_name')->pluck('institute_name')
            : collect([session('user_institute')]);
        $selectedReportInstitute = session('user_role') == 'InstituteAdmin'
            ? session('user_institute')
            : ($request->filled('institute') ? trim((string) $request->input('institute')) : null);
        $isStudentScopedReport = in_array($reportMode, [
            'student-ai-review',
            'weekly-student-performance',
            'monthly-student-performance',
        ], true);
        $selectedStudentReportClass = $request->filled('student_class')
            ? trim((string) $request->input('student_class'))
            : null;
        $selectedStudentReportSection = $request->filled('student_section')
            ? trim((string) $request->input('student_section'))
            : null;
        $reportClassOptions = $selectedReportInstitute
            ? Student::where('institute', $selectedReportInstitute)
                ->whereNotNull('class')
                ->where('class', '!=', '')
                ->orderBy('class')
                ->distinct()
                ->pluck('class')
                ->map(fn ($className) => preg_replace('/\s+/', ' ', trim((string) $className)))
                ->unique()
                ->values()
            : collect();
        $reportSectionOptions = ($selectedReportInstitute && $selectedStudentReportClass)
            ? Student::where('institute', $selectedReportInstitute)
                ->where('class', $selectedStudentReportClass)
                ->whereNotNull('section')
                ->where('section', '!=', '')
                ->orderBy('section')
                ->distinct()
                ->pluck('section')
                ->map(fn ($section) => preg_replace('/\s+/', ' ', trim((string) $section)))
                ->unique()
                ->values()
            : collect();
        $hasFilters = $request->filled('institute')
            || $request->filled('student_class')
            || $request->filled('student_section')
            || $request->filled('report_month')
            || $request->filled('from_date')
            || $request->filled('to_date');
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
            })
            ->when($isStudentScopedReport && $selectedStudentReportClass, function ($query) use ($selectedStudentReportClass) {
                $query->where('class_name', $selectedStudentReportClass);
            })
            ->when($isStudentScopedReport && $selectedStudentReportSection, function ($query) use ($selectedStudentReportSection) {
                if ($selectedStudentReportSection === '__unassigned') {
                    $query->where(function ($sectionQuery) {
                        $sectionQuery->whereNull('section')->orWhere('section', '');
                    });

                    return;
                }

                $query->where('section', $selectedStudentReportSection);
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
            ->when($isStudentScopedReport && $selectedStudentReportClass, function ($query) use ($selectedStudentReportClass) {
                $query->where('class', $selectedStudentReportClass);
            })
            ->when($isStudentScopedReport && $selectedStudentReportSection, function ($query) use ($selectedStudentReportSection) {
                if ($selectedStudentReportSection === '__unassigned') {
                    $query->where(function ($sectionQuery) {
                        $sectionQuery->whereNull('section')->orWhere('section', '');
                    });

                    return;
                }

                $query->where('section', $selectedStudentReportSection);
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
            'selectedReportInstitute',
            'selectedStudentReportClass',
            'selectedStudentReportSection',
            'reportInstituteOptions',
            'reportClassOptions',
            'reportSectionOptions',
            'hasFilters',
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

    /**
     * Produces the same report payload for authenticated mobile clients without
     * relying on a browser session. The caller owns institute scoping.
     */
    public function mobileReportPayload(Request $request, string $reportMode, ?string $institute): array
    {
        return $this->downloadableReportPayload($request, $reportMode, $institute);
    }

    private function downloadableReportPayload(Request $request, string $reportMode, ?string $forcedInstitute = null): array
    {
        [$periodFrom, $periodTo, $periodLabel] = $this->reportDateWindow($request, $reportMode);
        $downloadRouteName = $this->reportDownloadRouteName($reportMode);
        $institute = $forcedInstitute ?? $this->selectedReportInstitute($request, $downloadRouteName);
        $isStudentReport = in_array($reportMode, ['student-ai-review', 'weekly-student-performance', 'monthly-student-performance'], true);
        $isTeacherReport = in_array($reportMode, ['stem-engineer-prep', 'weekly-stem-engineer-performance', 'monthly-stem-engineer-performance'], true);
        $selectedClass = $isStudentReport ? ($request->filled('student_class') ? trim((string) $request->input('student_class')) : null) : null;
        $selectedSection = $isStudentReport ? ($request->filled('student_section') ? trim((string) $request->input('student_section')) : null) : null;

        $scopeParts = array_filter([
            $institute ?: 'All Institutes',
            $selectedClass ? 'Class ' . $selectedClass : null,
            $selectedSection ? ($selectedSection === '__unassigned' ? 'No Section' : 'Section ' . $selectedSection) : null,
        ]);
        $scope = implode(' / ', $scopeParts);

        $studentIds = Student::query()
            ->when($institute, fn ($query) => $query->where('institute', $institute))
            ->when($isStudentReport && $selectedClass, fn ($query) => $query->where('class', $selectedClass))
            ->when($isStudentReport && $selectedSection, function ($query) use ($selectedSection) {
                if ($selectedSection === '__unassigned') {
                    $query->where(function ($sectionQuery) {
                        $sectionQuery->whereNull('section')->orWhere('section', '');
                    });

                    return;
                }

                $query->where('section', $selectedSection);
            })
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
            ->when($isStudentReport && $selectedClass, fn ($query) => $query->where('class', $selectedClass))
            ->when($isStudentReport && $selectedSection, function ($query) use ($selectedSection) {
                if ($selectedSection === '__unassigned') {
                    $query->where(function ($sectionQuery) {
                        $sectionQuery->whereNull('section')->orWhere('section', '');
                    });

                    return;
                }

                $query->where('section', $selectedSection);
            })
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
        if ($request->filled('institute')) {
            return trim((string) $request->input('institute'));
        }

        if (session('user_role') == 'InstituteAdmin') {
            return session('user_institute');
        }

        if (session('user_role') != 'Admin') {
            return null;
        }

        return null;
    }

    private function buildStudentReportClassPager(Request $request, string $institute, string $routeName): array
    {
        $classes = Student::where('institute', $institute)
            ->whereNotNull('class')
            ->pluck('class')
            ->map(fn ($className) => preg_replace('/\s+/', ' ', trim((string) $className)))
            ->filter()
            ->unique(fn ($className) => mb_strtolower($className))
            ->sort(fn ($first, $second) => strnatcasecmp($first, $second))
            ->values();

        if ($classes->isEmpty()) {
            return ['selectedClass' => null, 'sectionPager' => null];
        }

        $requestedClass = trim((string) $request->input('student_class'));
        $requestedIndex = $requestedClass !== '' ? $classes->search($requestedClass) : false;
        $lastPage = $classes->count();
        $currentPage = $requestedIndex !== false
            ? $requestedIndex + 1
            : min(max((int) $request->input('student_class_page', 1), 1), $lastPage);
        $selectedClass = $classes->get($currentPage - 1);
        $previousClass = $currentPage > 1 ? $classes->get($currentPage - 2) : null;
        $nextClass = $currentPage < $lastPage ? $classes->get($currentPage) : null;
        $query = $request->except([
            'student_class_page',
            'student_class',
            'student_section_page',
            'student_section',
        ]);

        return [
            'selectedClass' => $selectedClass,
            'sectionPager' => [
                'current_label' => 'Class ' . $selectedClass,
                'current_page' => $currentPage,
                'last_page' => $lastPage,
                'previous_label' => $previousClass ? 'Class ' . $previousClass : null,
                'next_label' => $nextClass ? 'Class ' . $nextClass : null,
                'previous_url' => $previousClass
                    ? route($routeName, array_merge($query, [
                        'student_class_page' => $currentPage - 1,
                        'student_class' => $previousClass,
                    ]))
                    : null,
                'next_url' => $nextClass
                    ? route($routeName, array_merge($query, [
                        'student_class_page' => $currentPage + 1,
                        'student_class' => $nextClass,
                    ]))
                    : null,
            ],
        ];
    }

    private function buildStudentReportSectionPager(
        Request $request,
        string $institute,
        ?string $className,
        string $routeName
    ): array {
        if (!$className) {
            return ['selectedSection' => null, 'sectionPager' => null];
        }

        $sections = Student::where('institute', $institute)
            ->where('class', $className)
            ->pluck('section')
            ->map(fn ($section) => filled($section) ? preg_replace('/\s+/', ' ', trim((string) $section)) : '__unassigned')
            ->filter()
            ->unique(fn ($section) => mb_strtolower($section))
            ->sort(fn ($first, $second) => strnatcasecmp($first === '__unassigned' ? 'zzzz' : $first, $second === '__unassigned' ? 'zzzz' : $second))
            ->values();

        if ($sections->isEmpty()) {
            return ['selectedSection' => null, 'sectionPager' => null];
        }

        $requestedSection = trim((string) $request->input('student_section'));
        $requestedIndex = $requestedSection !== '' ? $sections->search($requestedSection) : false;
        $lastPage = $sections->count();
        $currentPage = $requestedIndex !== false
            ? $requestedIndex + 1
            : min(max((int) $request->input('student_section_page', 1), 1), $lastPage);
        $selectedSection = $sections->get($currentPage - 1);
        $previousSection = $currentPage > 1 ? $sections->get($currentPage - 2) : null;
        $nextSection = $currentPage < $lastPage ? $sections->get($currentPage) : null;
        $query = $request->except(['student_section_page', 'student_section']);
        $label = fn ($section) => $section === '__unassigned' ? 'No Section' : 'Section ' . $section;

        return [
            'selectedSection' => $selectedSection,
            'sectionPager' => [
                'current_label' => $label($selectedSection),
                'current_page' => $currentPage,
                'last_page' => $lastPage,
                'previous_label' => $previousSection ? $label($previousSection) : null,
                'next_label' => $nextSection ? $label($nextSection) : null,
                'previous_url' => $previousSection
                    ? route($routeName, array_merge($query, [
                        'student_section_page' => $currentPage - 1,
                        'student_section' => $previousSection,
                    ]))
                    : null,
                'next_url' => $nextSection
                    ? route($routeName, array_merge($query, [
                        'student_section_page' => $currentPage + 1,
                        'student_section' => $nextSection,
                    ]))
                    : null,
            ],
        ];
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
