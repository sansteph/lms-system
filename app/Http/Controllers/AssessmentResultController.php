<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\AssessmentResult;
use App\Models\AssessmentQuestion;
use App\Models\AssessmentSession;
use App\Models\AssessmentAnswer;
use App\Models\Certificate;
use Illuminate\Support\Str;

class AssessmentResultController extends Controller
{
    public function store(Request $request)
    {
        $request->validate([
            'student_id' => 'required',
            'assessment_id' => 'required',
            'total_marks' => 'required|integer',
            'answers' => 'nullable|array',
        ]);

        $existingResult = AssessmentResult::where('student_id', $request->student_id)
            ->where('assessment_id', $request->assessment_id)
            ->first();

        if ($existingResult) {
            return redirect()->route('student.history')
                ->with('error', 'You have already submitted this assessment.');
        }

        $score = 0;
        $hasPendingReview = false;
        $answers = $request->answers ?? [];

        $result = AssessmentResult::create([
            'student_id' => $request->student_id,
            'assessment_id' => $request->assessment_id,
            'score' => 0,
            'total_marks' => $request->total_marks,
            'status' => 'Pending Review',
            'badge' => null,
            'percentage' => 0,
        ]);

        foreach ($answers as $questionId => $submittedAnswer) {
            $question = AssessmentQuestion::find($questionId);

            if (!$question) {
                continue;
            }

            $questionType = $question->question_type ?? 'MCQ';

            if ($questionType == 'MCQ') {
                $isCorrect = $question->correct_answer == $submittedAnswer;
                $marksAwarded = $isCorrect ? $question->marks : 0;

                $score += $marksAwarded;

                AssessmentAnswer::create([
                    'assessment_result_id' => $result->id,
                    'assessment_id' => $request->assessment_id,
                    'student_id' => $request->student_id,
                    'question_id' => $question->id,
                    'question_type' => 'MCQ',
                    'submitted_answer' => $submittedAnswer,
                    'is_correct' => $isCorrect,
                    'marks_awarded' => $marksAwarded,
                    'review_status' => 'Auto Graded',
                ]);
            } else {
                $hasPendingReview = true;

                AssessmentAnswer::create([
                    'assessment_result_id' => $result->id,
                    'assessment_id' => $request->assessment_id,
                    'student_id' => $request->student_id,
                    'question_id' => $question->id,
                    'question_type' => $questionType,
                    'submitted_answer' => $submittedAnswer,
                    'is_correct' => null,
                    'marks_awarded' => 0,
                    'review_status' => 'Pending Review',
                ]);
            }
        }

        $percentage = $request->total_marks > 0
            ? ($score / $request->total_marks) * 100
            : 0;

        $badge = null;

        if (!$hasPendingReview) {
            $badge = $this->calculateBadge($percentage);
        }

        $result->update([
            'score' => $score,
            'percentage' => $percentage,
            'badge' => $badge,
            'status' => $hasPendingReview ? 'Pending Review' : 'Completed',
        ]);

        if (!$hasPendingReview) {
            $this->issueCertificateIfEligible($request->student_id);
        }

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
            ->with('success', 'Assessment submitted successfully');
    }

    public function reviewResults()
    {
        $answers = AssessmentAnswer::with([
                'student',
                'assessment',
                'question',
                'result',
            ])
            ->where('review_status', 'Pending Review')
            ->when(session('user_role') == 'Teacher', function ($query) {
                $teacher = \App\Models\User::find(session('user_id'));

                $query->whereHas('student', function ($q) use ($teacher) {
                    $q->where('institute', $teacher->institute);
                });
            })
            ->latest()
            ->get();

        return view('review-assessment-answers', compact('answers'));
    }

    public function reviewAnswer(Request $request, $id)
    {
        $request->validate([
            'marks_awarded' => 'required|integer|min:0',
        ]);

        $answer = AssessmentAnswer::with(['result', 'question'])->findOrFail($id);

        if ($request->marks_awarded > $answer->question->marks) {
            return redirect()->back()
                ->with('error', 'Awarded marks cannot exceed maximum marks.');
        }

        $answer->update([
            'marks_awarded' => $request->marks_awarded,
            'review_status' => 'Reviewed',
            'is_correct' => $request->marks_awarded > 0,
        ]);

        $result = $answer->result;

        $pendingCount = AssessmentAnswer::where('assessment_result_id', $result->id)
            ->where('review_status', 'Pending Review')
            ->count();

        if ($pendingCount == 0) {
            $totalScore = AssessmentAnswer::where('assessment_result_id', $result->id)
                ->sum('marks_awarded');

            $percentage = $result->total_marks > 0
                ? ($totalScore / $result->total_marks) * 100
                : 0;

            $badge = $this->calculateBadge($percentage);

            $result->update([
                'score' => $totalScore,
                'percentage' => $percentage,
                'badge' => $badge,
                'status' => 'Completed',
            ]);

            $this->issueCertificateIfEligible($result->student_id);
        }

        return redirect()->back()
            ->with('success', 'Answer reviewed successfully.');
    }

    private function calculateBadge($percentage)
    {
        if ($percentage >= 85) {
            return 'Gold';
        }

        if ($percentage >= 65) {
            return 'Silver';
        }

        if ($percentage >= 40) {
            return 'Bronze';
        }

        return null;
    }

    private function issueCertificateIfEligible($studentId)
    {
        $badgeCount = AssessmentResult::where('student_id', $studentId)
            ->where('status', 'Completed')
            ->whereNotNull('badge')
            ->count();

        $existingCertificate = Certificate::where('student_id', $studentId)
            ->where(function ($query) {
                $query->where('certificate_type', 'Student')
                    ->orWhereNull('certificate_type');
            })
            ->first();

        if ($badgeCount >= 5 && !$existingCertificate) {
            Certificate::create([
                'student_id' => $studentId,
                'certificate_code' => 'CERT-' . strtoupper(Str::random(10)),
                'badge_count' => $badgeCount,
                'issued_date' => now(),
                'status' => 'Issued',
                'certificate_type' => 'Student',
            ]);
        }
    }
}