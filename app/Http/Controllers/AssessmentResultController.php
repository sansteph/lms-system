<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\AssessmentResult;
use App\Models\AssessmentSession;
use App\Models\Certificate;
use Illuminate\Support\Str;
use App\Models\Assessment;
use App\Models\Student;
use App\Models\SchoolClass;
use App\Models\User;
use Illuminate\Support\Facades\Storage;

class AssessmentResultController extends Controller
{
    public function store(Request $request)
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

        AssessmentResult::create([
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

        return redirect()->route('student.history')
            ->with('success', 'Assessment submitted successfully. It is waiting for manual evaluation.');
    }

    public function reviewResults()
    {
        $pendingResults = AssessmentResult::with([
                'student',
                'assessment',
                'assessment.teacher',
                'evaluator',
            ])
            ->where('status', 'Pending Review')
            ->when(session('user_role') == 'Teacher', function ($query) {
                $teacher = User::findOrFail(session('user_id'));

                $query->whereIn('student_id', $this->teacherAssignedStudentsQuery($teacher)->pluck('id'))
                    ->whereHas('assessment', function ($q) use ($teacher) {
                        $q->where('teacher_id', $teacher->id);
                    });
            })
            ->when(session('user_role') == 'InstituteAdmin', function ($query) {
                $query->whereHas('assessment', function ($q) {
                    $q->where('institute', session('user_institute'));
                });
            })
            ->latest()
            ->get();

        return view('review-assessment-answers', compact('pendingResults'));
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
                !$this->teacherCanAccessStudent($teacher, $result->student)
            ) {
                abort(403, 'You can only evaluate students assigned to your class.');
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
                $this->teacherCanAccessStudent($teacher, $result->student);
        }

        if (session('student_id')) {
            return (int) $result->student_id === (int) session('student_id');
        }

        return false;
    }

    private function teacherAssignedStudentsQuery(User $teacher)
    {
        $classes = SchoolClass::where('institute', $teacher->institute)
            ->where('class_teacher', $teacher->name)
            ->get(['class_name', 'section']);

        $query = Student::where('institute', $teacher->institute);

        if ($classes->isEmpty()) {
            return $query->whereRaw('1 = 0');
        }

        return $query->where(function ($studentQuery) use ($classes) {
            foreach ($classes as $class) {
                $studentQuery->orWhere(function ($q) use ($class) {
                    $q->where('class', $class->class_name)
                        ->where('section', $class->section);
                });
            }
        });
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
            (
                $assessment->assessment_date &&
                $assessment->assessment_date > today()->toDateString()
            )
        ) {
            return false;
        }

        return $this->studentClassName($student) ==
            preg_replace('/\s+/', ' ', trim((string) $assessment->assigned_class));
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
            'status' => 'Pending Approval',
            'certificate_type' => 'Annual',
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
