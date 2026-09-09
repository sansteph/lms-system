<?php

namespace App\Services;

use App\Models\{AssessmentSession, AssessmentResult};
use Carbon\Carbon;
use Illuminate\Support\Facades\{Cache, DB};
use Illuminate\Validation\ValidationException;

class MobileAssessmentService
{
    public function deadline(AssessmentSession $session): Carbon
    {
        $assessment = $session->assessment;
        $duration = max(1, (int) $assessment->duration);
        $deadline = Carbon::parse($session->started_at)->addMinutes($duration);
        if ($assessment->end_time && $assessment->assessment_date) {
            $windowEnd = Carbon::parse(Carbon::parse($assessment->assessment_date)->format('Y-m-d').' '.$assessment->end_time);
            if ($windowEnd->lt($deadline)) $deadline = $windowEnd;
        }
        return $deadline;
    }

    public function draftKey(AssessmentSession $session): string
    {
        return 'mobile-assessment-draft:'.$session->user_id.':'.$session->id;
    }

    public function submit(AssessmentSession $session, ?string $answer, bool $automatic = false): AssessmentResult
    {
        $result = DB::transaction(function () use ($session, $answer, $automatic) {
            $session = AssessmentSession::with('assessment')->lockForUpdate()->findOrFail($session->id);
            $existing = AssessmentResult::where('student_id', $session->user_id)->where('assessment_id', $session->assessment_id)->first();
            if ($existing) return $existing;
            abort_unless(in_array($session->status, ['Started', 'AutoSubmitted'], true), 409, 'This assessment session is closed.');
            $expired = $this->deadline($session)->lte(now());
            $violations = (int) $session->violation_count >= 3;
            $automatic = $expired || $violations || $session->status === 'AutoSubmitted' || $automatic;
            if ($expired || $violations) $answer = Cache::get($this->draftKey($session), '');
            if (!$automatic && trim((string) $answer) === '') {
                throw ValidationException::withMessages(['answer_text' => 'Please type your answer before submitting.']);
            }
            $result = AssessmentResult::create(['student_id' => $session->user_id, 'assessment_id' => $session->assessment_id,
                'score' => 0, 'total_marks' => max(1, (int) $session->assessment->total_marks), 'status' => 'Pending Review',
                'percentage' => 0, 'answer_text' => $answer, 'answer_file_path' => null, 'badge' => null, 'passed' => null]);
            $session->update(['status' => $automatic ? 'AutoSubmitted' : 'Submitted', 'submitted_at' => now()]);
            Cache::forget($this->draftKey($session));
            return $result;
        });

        app(AssessmentAutoEvaluationService::class)->evaluateIfEligible($result);

        return $result->refresh();
    }
}
