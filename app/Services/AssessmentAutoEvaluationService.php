<?php

namespace App\Services;

use App\Models\Assessment;
use App\Models\AssessmentResult;
use App\Models\Certificate;
use App\Models\Student;
use App\Services\Ai\GeminiAiService;
use App\Services\Ai\PdfTextExtractionService;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Throwable;

class AssessmentAutoEvaluationService
{
    public function __construct(
        private GeminiAiService $ai,
        private PdfTextExtractionService $pdfTextExtractionService
    ) {
    }

    public function evaluateIfEligible(AssessmentResult $result): bool
    {
        $result->loadMissing(['assessment', 'student']);

        $assessment = $result->assessment;
        if (!$assessment || !$this->supportsAutoEvaluation($assessment)) {
            return false;
        }

        try {
            $answerText = trim((string) $result->answer_text);
            $totalMarks = max(1, (float) ($assessment->total_marks ?: $result->total_marks ?: 1));

            if ($answerText === '') {
                $evaluation = [
                    'score' => 0,
                    'total_marks' => $totalMarks,
                    'percentage' => 0,
                    'passed' => false,
                    'feedback' => 'No answer text was submitted. The assessment was marked as failed automatically.',
                    'answer_feedback' => [],
                ];
            } else {
                $evaluation = $this->ai->evaluateAssessmentSubmission([
                    'assessment_title' => $assessment->assessment_title,
                    'assessment_category' => $assessment->assessment_category,
                    'assessment_type' => $assessment->assessment_type,
                    'assigned_class' => $assessment->assigned_class,
                    'total_marks' => $totalMarks,
                    'question_paper_type' => $assessment->question_paper_type,
                    'question_paper' => $this->assessmentRubric($assessment),
                    'student' => [
                        'id' => $result->student?->student_id,
                        'name' => $result->student?->name,
                        'class' => trim(($result->student?->class ?? '') . ' ' . ($result->student?->section ?? '')),
                    ],
                    'student_answer' => $answerText,
                ]);
            }

            $this->applyEvaluation($result, $assessment, $evaluation);
            $this->prepareCertificateRequestIfEligible($result->student_id, $assessment);

            return true;
        } catch (Throwable $exception) {
            report($exception);
            return false;
        }
    }

    private function supportsAutoEvaluation(Assessment $assessment): bool
    {
        return in_array($assessment->assessment_category, ['Monthly', 'Annual'], true)
            && $assessment->question_paper_status === 'Approved'
            && (bool) $assessment->teacher_id;
    }

    private function assessmentRubric(Assessment $assessment): array
    {
        $paper = json_decode((string) $assessment->ai_generation_payload, true);
        if (is_array($paper) && !empty($paper)) {
            return [
                'format' => 'structured_ai_question_paper',
                'sections' => $paper['sections'] ?? [],
                'blueprint' => $paper['blueprint'] ?? [],
                'instructions' => $paper['instructions'] ?? [],
            ];
        }

        $path = $assessment->file_path;
        if (!$path || strtolower(pathinfo($path, PATHINFO_EXTENSION)) !== 'pdf') {
            throw new \RuntimeException('Only readable PDF question papers can be auto-evaluated.');
        }

        if (!Storage::disk('local')->exists($path)) {
            throw new \RuntimeException('Question paper file is unavailable for auto-evaluation.');
        }

        return [
            'format' => 'uploaded_pdf_text',
            'text' => mb_substr(
                $this->pdfTextExtractionService->extract(Storage::disk('local')->path($path)),
                0,
                (int) config('ai.content.max_summary_input_chars', 24000)
            ),
        ];
    }

    private function applyEvaluation(AssessmentResult $result, Assessment $assessment, array $evaluation): void
    {
        $totalMarks = max(1, (float) ($evaluation['total_marks'] ?? $assessment->total_marks ?? $result->total_marks ?? 1));
        $score = max(0, min($totalMarks, (float) ($evaluation['score'] ?? 0)));
        $percentage = round(($score / $totalMarks) * 100, 2);
        $passed = $percentage >= 40 && (bool) ($evaluation['passed'] ?? true);

        $result->update([
            'score' => round($score),
            'total_marks' => round($totalMarks),
            'percentage' => $percentage,
            'status' => 'Completed',
            'badge' => $passed ? $this->calculateBadge($percentage) : null,
            'feedback' => $evaluation['feedback'] ?? 'AI evaluation completed.',
            'passed' => $passed,
            'evaluated_by' => null,
            'evaluated_at' => now(),
        ]);
    }

    private function calculateBadge(float $percentage): ?string
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

    private function prepareCertificateRequestIfEligible(int $studentId, Assessment $assessment): void
    {
        if ($assessment->assessment_category !== 'Annual') {
            return;
        }

        $student = Student::find($studentId);
        if (!$student) {
            return;
        }

        $assignedClass = $this->normalizedClassName($assessment->assigned_class);
        if ($this->studentClassName($student) !== $assignedClass) {
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
        $averageScore = round((float) $finalResults->avg('percentage'), 2);

        if ($averageScore < 40) {
            return;
        }

        if (Certificate::where('student_id', $studentId)->where('certificate_type', 'Annual')->exists()) {
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

    private function studentClassName(Student $student): string
    {
        return $this->normalizedClassName(trim($student->class . ' ' . $student->section));
    }

    private function normalizedClassName(?string $className): string
    {
        return preg_replace('/\s+/', ' ', trim((string) $className));
    }

    private function calculateFinalGrade(float $percentage): string
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

    private function calculateFinalClassification(float $percentage): string
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
