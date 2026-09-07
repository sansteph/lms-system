<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\AssessmentResult;
use App\Models\AssessmentSession;
use App\Models\Certificate;
use App\Models\Institute;
use App\Models\AiComponentContentProfile;
use App\Models\AiQuizAttempt;
use App\Models\Content;
use Illuminate\Support\Str;
use App\Models\Assessment;
use App\Models\Student;
use App\Models\SchoolClass;
use App\Models\User;
use Illuminate\Support\Facades\Storage;
use App\Support\BuildsInstituteSectionPager;
use App\Services\Ai\GeminiAiService;

class AssessmentResultController extends Controller
{
    use BuildsInstituteSectionPager;

    public function store(Request $request, GeminiAiService $ai)
    {
        $request->validate([
            'student_id' => 'required',
            'assessment_id' => 'required',
            'total_marks' => 'required|integer|min:1',
            'answer_text' => 'nullable|string',
            'auto_submitted' => 'nullable|boolean',
        ]);

        $isAutoSubmitted = $request->boolean('auto_submitted');

        if (!$isAutoSubmitted && !$request->filled('answer_text')) {
            return redirect()->back()
                ->with('error', 'Please type your answer before submitting.');
        }

        if ((int) $request->student_id !== (int) session('student_id')) {
            abort(403, 'Invalid student assessment submission.');
        }

        if (!$this->studentCanSubmitAssessment($request->assessment_id, $request->student_id)) {
            abort(403, 'This assessment is not assigned to your class or is not approved yet.');
        }

        $existingResult = AssessmentResult::where('student_id', $request->student_id)
            ->where('assessment_id', $request->assessment_id)
            ->first();

        if ($existingResult) {
            return redirect()->route('student.history')
                ->with('error', 'You have already submitted this assessment.');
        }

        $assessment = Assessment::findOrFail($request->assessment_id);

        $result = AssessmentResult::create([
            'student_id' => $request->student_id,
            'assessment_id' => $request->assessment_id,
            'score' => 0,
            'total_marks' => $request->total_marks,
            'status' => 'Pending Review',
            'badge' => null,
            'percentage' => 0,
            'answer_text' => $request->answer_text,
            'answer_file_path' => null,
            'feedback' => null,
            'passed' => null,
            'evaluated_by' => null,
            'evaluated_at' => null,
        ]);

        if (session('active_assessment_session_id')) {
            $assessmentSession = AssessmentSession::find(session('active_assessment_session_id'));

            if ($assessmentSession) {
                $assessmentSession->update([
                    'status' => $assessmentSession->status == 'AutoSubmitted'
                        ? 'AutoSubmitted'
                        : 'Submitted',
                    'submitted_at' => $assessmentSession->submitted_at ?? now(),
                ]);
            }

            session()->forget([
                'active_assessment_session_id',
                'active_assessment_id',
            ]);
        }

        if ($assessment->assessment_category === 'Component Mastery') {
            return $this->evaluateComponentMasteryResult($result, $assessment, $ai);
        }

        return redirect()->route('student.history')
            ->with('success', 'Assessment submitted successfully. It is waiting for manual evaluation.');
    }

    private function evaluateComponentMasteryResult(AssessmentResult $result, Assessment $assessment, GeminiAiService $ai)
    {
        try {
            $paper = json_decode((string) $assessment->ai_generation_payload, true) ?: [];
            $evaluation = $ai->evaluateComponentMasteryAssessment([
                'assessment_title' => $assessment->assessment_title,
                'component' => $assessment->component_label,
                'total_marks' => $assessment->total_marks,
                'sections' => $paper['sections'] ?? [],
                'student_answer' => $result->answer_text,
            ]);

            $totalMarks = max(1, (float) ($evaluation['total_marks'] ?? $assessment->total_marks));
            $score = max(0, min($totalMarks, (float) ($evaluation['score'] ?? 0)));
            $percentage = round(($score / $totalMarks) * 100, 2);
            $passed = $percentage >= 40 && (bool) ($evaluation['passed'] ?? true);

            $result->update([
                'score' => $score,
                'total_marks' => $totalMarks,
                'percentage' => $percentage,
                'status' => 'Completed',
                'badge' => $passed ? $this->calculateBadge($percentage) : null,
                'feedback' => $evaluation['feedback'] ?? 'AI evaluation completed.',
                'passed' => $passed,
                'evaluated_by' => null,
                'evaluated_at' => now(),
            ]);

            if ($passed) {
                $this->prepareCertificateRequestIfEligible($result->student_id, $assessment);
            }

            return redirect()->route('student.history')
                ->with('success', 'Component Mastery assessment evaluated automatically by AI.');
        } catch (\Throwable $exception) {
            report($exception);

            return redirect()->route('student.history')
                ->with('success', 'Assessment submitted. AI evaluation is temporarily unavailable, so it has been queued for review.');
        }
    }

    public function reviewResults(Request $request)
    {
        $instituteOptions = Institute::where('status', 1)
            ->orderBy('institute_name')
            ->pluck('institute_name')
            ->values();

        $selectedInstitute = session('user_role') == 'InstituteAdmin'
            ? session('user_institute')
            : ($request->input('institute') ?: (session('user_role') == 'Teacher' ? User::find(session('user_id'))?->institute : null));

        $selectedStudentClass = null;
        $selectedStudentSection = null;
        $reviewClassOptions = collect();
        $reviewSectionOptions = collect();
        $routeName = $request->route()?->getName() ?: 'assessment.review';
        $reviewRouteName = session('user_role') == 'Teacher' ? 'assessment.review' : 'admin.assessment.review';

        if ($selectedInstitute) {
            $reviewClassOptions = SchoolClass::where('institute', $selectedInstitute)
                ->orderBy('class_name')
                ->pluck('class_name')
                ->map(fn ($className) => trim((string) $className))
                ->filter()
                ->unique(fn ($className) => mb_strtolower($className))
                ->values();

            $selectedStudentClass = $request->input('student_class');

            if ($selectedStudentClass) {
                $reviewSectionOptions = Student::where('institute', $selectedInstitute)
                    ->where('class', $selectedStudentClass)
                    ->whereNotNull('section')
                    ->orderBy('section')
                    ->pluck('section')
                    ->map(fn ($section) => trim((string) $section))
                    ->filter()
                    ->unique(fn ($section) => mb_strtolower($section))
                    ->values();
            }

            $selectedStudentSection = $request->input('student_section');
        }

        $statusFilter = $request->input('status');
        $searchFilter = $request->input('search');
        $hasFilters = $request->filled('institute')
            || $request->filled('student_class')
            || $request->filled('student_section')
            || $request->filled('status')
            || $request->filled('search')
            || session('user_role') == 'InstituteAdmin'
            || session('user_role') == 'Teacher';

        $pendingResults = AssessmentResult::with([
                'student',
                'assessment',
                'assessment.teacher',
                'evaluator',
            ])
            ->where('status', 'Pending Review')
            ->when(session('user_role') == 'Teacher', function ($query) {
                $teacher = User::findOrFail(session('user_id'));

                $query->whereHas('student', function ($q) use ($teacher) {
                        $q->where('institute', $teacher->institute);
                    })
                    ->whereHas('assessment', function ($q) use ($teacher) {
                        $q->where('teacher_id', $teacher->id);
                    });
            })
            ->when($selectedInstitute, function ($query) use ($selectedInstitute, $selectedStudentClass, $selectedStudentSection, $searchFilter) {
                $query->whereHas('student', function ($q) use ($selectedInstitute, $selectedStudentClass, $selectedStudentSection, $searchFilter) {
                    $q->where('institute', $selectedInstitute)
                        ->when($selectedStudentClass, fn ($innerQuery) => $innerQuery->where('class', $selectedStudentClass))
                        ->when($selectedStudentSection, fn ($innerQuery) => $innerQuery->where('section', $selectedStudentSection))
                        ->when($searchFilter, function ($innerQuery) use ($searchFilter) {
                            $innerQuery->where(function ($searchQuery) use ($searchFilter) {
                                $searchQuery->where('name', 'like', '%' . $searchFilter . '%')
                                    ->orWhere('student_id', 'like', '%' . $searchFilter . '%');
                            });
                        });
                });
            })
            ->when($statusFilter, fn ($query) => $query->where('status', $statusFilter))
            ->latest()
            ->paginate(20)
            ->withQueryString();

        return view('review-assessment-answers', compact(
            'pendingResults',
            'instituteOptions',
            'selectedInstitute',
            'selectedStudentClass',
            'selectedStudentSection',
            'reviewRouteName',
            'reviewClassOptions',
            'reviewSectionOptions',
            'statusFilter',
            'searchFilter'
        ) + ['showFilterPlaceholder' => ! $hasFilters || ! $selectedInstitute]);
    }

    private function buildStudentClassPager(Request $request, ?string $institute, string $routeName): array
    {
        if (!$institute) {
            return [
                'selectedClass' => null,
                'sectionPager' => null,
            ];
        }

        $classOptions = SchoolClass::where('institute', $institute)
            ->whereNotNull('class_name')
            ->orderBy('class_name')
            ->pluck('class_name')
            ->map(fn ($className) => trim((string) $className));

        $studentClassOptions = Student::where('institute', $institute)
            ->whereNotNull('class')
            ->select('class')
            ->distinct()
            ->orderBy('class')
            ->pluck('class')
            ->map(fn ($className) => trim((string) $className));

        $classOptions = $classOptions
            ->merge($studentClassOptions)
            ->filter()
            ->unique(fn ($className) => mb_strtolower($className))
            ->sort()
            ->values();

        if ($classOptions->isEmpty()) {
            return [
                'selectedClass' => null,
                'sectionPager' => null,
            ];
        }

        $requestedClass = $request->input('student_class');
        $requestedIndex = $requestedClass ? $classOptions->search($requestedClass) : false;
        $lastPage = $classOptions->count();
        $currentPage = $requestedIndex !== false
            ? $requestedIndex + 1
            : min(max((int) $request->input('class_page', 1), 1), $lastPage);

        $selectedClass = $classOptions->get($currentPage - 1);
        $previousClass = $currentPage > 1 ? $classOptions->get($currentPage - 2) : null;
        $nextClass = $currentPage < $lastPage ? $classOptions->get($currentPage) : null;
        $query = $request->except(['class_page', 'student_class', 'student_section_page', 'student_section', 'page', 'class']);

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
                        'class_page' => $currentPage - 1,
                        'student_class' => $previousClass,
                    ]))
                    : null,
                'next_url' => $nextClass
                    ? route($routeName, array_merge($query, [
                        'class_page' => $currentPage + 1,
                        'student_class' => $nextClass,
                    ]))
                    : null,
            ],
        ];
    }

    private function buildStudentSectionPager(Request $request, ?string $institute, ?string $className, string $routeName): array
    {
        if (!$institute || !$className) {
            return [
                'selectedSection' => null,
                'sectionPager' => null,
            ];
        }

        $sectionOptions = SchoolClass::where('institute', $institute)
            ->where('class_name', $className)
            ->whereNotNull('section')
            ->orderBy('section')
            ->pluck('section')
            ->map(fn ($section) => trim((string) $section));

        $studentSectionOptions = Student::where('institute', $institute)
            ->where('class', $className)
            ->whereNotNull('section')
            ->select('section')
            ->distinct()
            ->orderBy('section')
            ->pluck('section')
            ->map(fn ($section) => trim((string) $section));

        $sectionOptions = $sectionOptions
            ->merge($studentSectionOptions)
            ->filter()
            ->unique(fn ($section) => mb_strtolower($section))
            ->sort()
            ->values();

        if ($sectionOptions->isEmpty()) {
            return [
                'selectedSection' => null,
                'sectionPager' => null,
            ];
        }

        $requestedSection = $request->input('student_section');
        $requestedIndex = $requestedSection ? $sectionOptions->search($requestedSection) : false;
        $lastPage = $sectionOptions->count();
        $currentPage = $requestedIndex !== false
            ? $requestedIndex + 1
            : min(max((int) $request->input('student_section_page', 1), 1), $lastPage);

        $selectedSection = $sectionOptions->get($currentPage - 1);
        $previousSection = $currentPage > 1 ? $sectionOptions->get($currentPage - 2) : null;
        $nextSection = $currentPage < $lastPage ? $sectionOptions->get($currentPage) : null;
        $query = $request->except(['student_section_page', 'student_section', 'page']);

        return [
            'selectedSection' => $selectedSection,
            'sectionPager' => [
                'current_label' => 'Section ' . $selectedSection,
                'current_page' => $currentPage,
                'last_page' => $lastPage,
                'previous_label' => $previousSection ? 'Section ' . $previousSection : null,
                'next_label' => $nextSection ? 'Section ' . $nextSection : null,
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

    public function reviewAnswer(Request $request, $id)
    {
        $request->validate([
            'marks_awarded' => 'required|integer|min:0',
            'feedback' => 'nullable|string|max:2000',
            'passed' => 'required|in:0,1',
        ]);

        $result = AssessmentResult::with(['assessment', 'student'])->findOrFail($id);

        if (session('user_role') == 'Teacher') {
            $teacher = User::findOrFail(session('user_id'));

            if (
                !$result->assessment ||
                $result->assessment->teacher_id != $teacher->id ||
                !$result->student ||
                $result->student->institute != $teacher->institute
            ) {
                abort(403, 'You can only evaluate students from your institute.');
            }
        }

        if (
            session('user_role') == 'InstituteAdmin' &&
            (!$result->assessment || $result->assessment->institute != session('user_institute'))
        ) {
            abort(403, 'Unauthorized action.');
        }

        if ($request->marks_awarded > $result->total_marks) {
            return redirect()->back()
                ->with('error', 'Awarded marks cannot exceed maximum marks.');
        }

        $percentage = $result->total_marks > 0
            ? ($request->marks_awarded / $result->total_marks) * 100
            : 0;

        $passed = (bool) $request->passed;

        $result->update([
            'score' => $request->marks_awarded,
            'percentage' => $percentage,
            'badge' => $passed ? $this->calculateBadge($percentage) : null,
            'status' => 'Completed',
            'feedback' => $request->feedback,
            'passed' => $passed,
            'evaluated_by' => session('user_id'),
            'evaluated_at' => now(),
        ]);

        if ($result->assessment) {
            $this->prepareCertificateRequestIfEligible($result->student_id, $result->assessment);
        }

        return redirect()->back()
            ->with('success', 'Assessment evaluated successfully.');
    }

    public function showAnswerFile(AssessmentResult $result)
    {
        if (!$this->canViewAnswerFile($result)) {
            abort(403, 'You are not authorized to view this submission.');
        }

        if (!$result->answer_file_path || !Storage::disk('local')->exists($result->answer_file_path)) {
            abort(404);
        }

        $path = Storage::disk('local')->path($result->answer_file_path);
        $mimeType = mime_content_type($path) ?: 'application/octet-stream';
        $fileName = str_replace('"', '', basename($path));

        return response()->file($path, [
            'Content-Type' => $mimeType,
            'Content-Disposition' => 'inline; filename="' . $fileName . '"',
            'Cache-Control' => 'private, no-store, no-cache, must-revalidate',
            'Pragma' => 'no-cache',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }

    private function canViewAnswerFile(AssessmentResult $result)
    {
        $result->loadMissing(['student', 'assessment']);

        if (session('user_role') == 'Admin') {
            return true;
        }

        if (session('user_role') == 'InstituteAdmin') {
            return $result->assessment && $result->assessment->institute == session('user_institute');
        }

        if (session('user_role') == 'Teacher') {
            $teacher = User::find(session('user_id'));

            return $teacher &&
                $result->assessment &&
                $result->assessment->teacher_id == $teacher->id &&
                $result->student &&
                $result->student->institute == $teacher->institute;
        }

        if (session('student_id')) {
            return (int) $result->student_id === (int) session('student_id');
        }

        return false;
    }

    private function teacherAssignedStudentsQuery(User $teacher)
    {
        return Student::where('institute', $teacher->institute);
    }

    private function teacherCanAccessStudent(User $teacher, Student $student)
    {
        return $this->teacherAssignedStudentsQuery($teacher)
            ->where('id', $student->id)
            ->exists();
    }

    private function studentClassName(Student $student)
    {
        return preg_replace('/\s+/', ' ', trim($student->class . ' ' . $student->section));
    }

    private function studentCanSubmitAssessment($assessmentId, $studentId)
    {
        $student = Student::find($studentId);

        if (!$student) {
            return false;
        }

        $assessment = Assessment::query()
            ->where('id', $assessmentId)
            ->where('institute', $student->institute)
            ->where('status', 1)
            ->where('question_paper_status', 'Approved')
            ->whereNotNull('file_path')
            ->first();

        if (
            !$assessment ||
            !$this->assessmentWindowIsOpen($assessment)
        ) {
            return false;
        }

        $classMatches = $this->studentClassName($student) ==
            preg_replace('/\s+/', ' ', trim((string) $assessment->assigned_class));

        if (!$classMatches) {
            return false;
        }

        if ($assessment->assessment_category === 'Component Mastery') {
            return $this->studentHasComponentMasteryEligibility($student, $assessment->component_key);
        }

        return true;
    }

    private function studentHasComponentMasteryEligibility(Student $student, ?string $componentKey): bool
    {
        if (!$componentKey) {
            return false;
        }

        $contentIds = Content::query()
            ->where('status', 1)
            ->where('institute', $student->institute)
            ->where(function ($query) use ($student) {
                $assignedClass = $this->studentClassName($student);

                $query->whereRaw(
                    "REPLACE(TRIM(assigned_class), '  ', ' ') = ?",
                    [$assignedClass]
                )
                ->orWhere(function ($courseQuery) use ($assignedClass, $student) {
                    $courseQuery->whereHas('course', function ($q) use ($assignedClass, $student) {
                        $q->where('institute', $student->institute)
                            ->whereRaw(
                                "REPLACE(TRIM(assigned_class), '  ', ' ') = ?",
                                [$assignedClass]
                            );
                    });
                });
            })
            ->pluck('id');

        $eligibleContentIds = AiComponentContentProfile::whereIn('content_id', $contentIds)
            ->where('component_key', $componentKey)
            ->where('is_practical', true)
            ->where('confidence', '>=', 45)
            ->pluck('content_id');

        if ($eligibleContentIds->count() < 5) {
            return false;
        }

        $aiQuizOwnerIds = Content::with('courseContent.sourceTemplateContent.aiSummary')
            ->whereIn('id', $eligibleContentIds)
            ->get()
            ->map(function (Content $content) {
                $sourceContent = $content->courseContent?->sourceTemplateContent;

                return $sourceContent && ($sourceContent->aiSummary || $sourceContent->hasAiPdfMaterial())
                    ? $sourceContent->id
                    : $content->id;
            })
            ->unique()
            ->values();

        return AiQuizAttempt::where('student_id', $student->id)
            ->where('attempt_type', 'student')
            ->where('status', 'passed')
            ->whereIn('content_id', $aiQuizOwnerIds)
            ->distinct('content_id')
            ->count('content_id') >= 5;
    }

    private function assessmentWindowIsOpen(Assessment $assessment): bool
    {
        if ($assessment->assessment_date) {
            $today = today()->toDateString();

            if ($assessment->assessment_date > $today) {
                return false;
            }

            if (($assessment->start_time || $assessment->end_time) && $assessment->assessment_date < $today) {
                return false;
            }
        }

        if ($assessment->start_time && now()->format('H:i:s') < $assessment->start_time) {
            return false;
        }

        if ($assessment->end_time && now()->format('H:i:s') > $assessment->end_time) {
            return false;
        }

        return true;
    }

    private function calculateBadge($percentage)
    {
        if ($percentage >= 80) {
            return 'Gold';
        }

        if ($percentage >= 60) {
            return 'Silver';
        }

        if ($percentage >= 40) {
            return 'Bronze';
        }

        return null;
    }

    private function prepareCertificateRequestIfEligible($studentId, Assessment $assessment)
    {
        if ($assessment->assessment_category === 'Component Mastery') {
            $this->prepareComponentMasteryCertificateRequest($studentId, $assessment);
            return;
        }

        if ($assessment->assessment_category != 'Annual') {
            return;
        }

        $student = Student::find($studentId);

        if (!$student) {
            return;
        }

        $assignedClass = preg_replace('/\s+/', ' ', trim((string) $assessment->assigned_class));

        if ($this->studentClassName($student) != $assignedClass) {
            return;
        }

        $annualResult = AssessmentResult::where('student_id', $studentId)
            ->where('assessment_id', $assessment->id)
            ->where('status', 'Completed')
            ->whereNotNull('evaluated_at')
            ->where('passed', true)
            ->first();

        if (!$annualResult) {
            return;
        }

        $monthlyAssessmentIds = Assessment::where('institute', $assessment->institute)
            ->where('status', 1)
            ->where('question_paper_status', 'Approved')
            ->where('assessment_category', 'Monthly')
            ->whereRaw(
                "REPLACE(TRIM(assigned_class), '  ', ' ') = ?",
                [$assignedClass]
            )
            ->whereDate('assessment_date', '<=', today())
            ->pluck('id');

        $monthlyResults = AssessmentResult::where('student_id', $studentId)
            ->whereIn('assessment_id', $monthlyAssessmentIds)
            ->where('status', 'Completed')
            ->whereNotNull('evaluated_at')
            ->get();

        $finalResults = $monthlyResults->push($annualResult);

        $averageScore = round($finalResults->avg('percentage'), 2);

        if ($averageScore < 40) {
            return;
        }

        $existingCertificate = Certificate::where('student_id', $studentId)
            ->where('certificate_type', 'Annual')
            ->first();

        if ($existingCertificate) {
            return;
        }

        Certificate::create([
            'student_id' => $studentId,
            'course_id' => null,
            'certificate_code' => 'CERT-' . strtoupper(Str::random(10)),
            'badge_count' => round($averageScore),
            'final_score' => $averageScore,
            'final_grade' => $this->calculateFinalGrade($averageScore),
            'final_classification' => $this->calculateFinalClassification($averageScore),
            'issued_date' => null,
            'status' => 'pending_admin_approval',
            'certificate_type' => 'Annual',
        ]);
    }

    private function prepareComponentMasteryCertificateRequest($studentId, Assessment $assessment): void
    {
        if (!$assessment->certificate_eligible || !$assessment->component_key) {
            return;
        }

        $result = AssessmentResult::where('student_id', $studentId)
            ->where('assessment_id', $assessment->id)
            ->where('status', 'Completed')
            ->whereNotNull('evaluated_at')
            ->where('passed', true)
            ->first();

        if (!$result || (float) $result->percentage < 40) {
            return;
        }

        $existingCertificate = Certificate::where('student_id', $studentId)
            ->where('certificate_type', 'Component Mastery')
            ->where('course_id', null)
            ->where('final_classification', 'like', '%' . $assessment->component_label . '%')
            ->first();

        if ($existingCertificate) {
            return;
        }

        $finalScore = round((float) $result->percentage, 2);

        Certificate::create([
            'student_id' => $studentId,
            'course_id' => null,
            'certificate_code' => 'CM-' . strtoupper(Str::random(10)),
            'badge_count' => round($finalScore),
            'final_score' => $finalScore,
            'final_grade' => $this->calculateFinalGrade($finalScore),
            'final_classification' => trim('Component Mastery - ' . $assessment->component_label),
            'issued_date' => null,
            'status' => 'pending_admin_approval',
            'certificate_type' => 'Component Mastery',
        ]);
    }

    private function calculateFinalGrade($percentage)
    {
        if ($percentage >= 90) {
            return 'A+';
        }

        if ($percentage >= 80) {
            return 'A';
        }

        if ($percentage >= 70) {
            return 'B+';
        }

        if ($percentage >= 60) {
            return 'B';
        }

        if ($percentage >= 50) {
            return 'C+';
        }

        if ($percentage >= 40) {
            return 'C';
        }

        return 'F';
    }

    private function calculateFinalClassification($percentage)
    {
        if ($percentage >= 90) {
            return 'Outstanding';
        }

        if ($percentage >= 80) {
            return 'Distinction';
        }

        if ($percentage >= 70) {
            return 'First Class';
        }

        if ($percentage >= 60) {
            return 'Second Class';
        }

        if ($percentage >= 50) {
            return 'Pass';
        }

        if ($percentage >= 40) {
            return 'Satisfactory';
        }

        return 'Fail';
    }
}
