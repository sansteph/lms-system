<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\AssessmentResult;
use App\Models\AssessmentQuestion;
use App\Models\AssessmentSession;

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
        $answers = $request->answers ?? [];

        foreach ($answers as $questionId => $selectedAnswer) {
            $question = AssessmentQuestion::find($questionId);

            if ($question && $question->correct_answer == $selectedAnswer) {
                $score += $question->marks;
            }
        }

        $percentage = 0;

        if ($request->total_marks > 0) {
            $percentage = ($score / $request->total_marks) * 100;
        }

        $badge = null;

        if ($percentage >= 85) {
            $badge = 'Gold';
        } elseif ($percentage >= 65) {
            $badge = 'Silver';
        } elseif ($percentage >= 40) {
            $badge = 'Bronze';
        }

        AssessmentResult::create([
            'student_id' => $request->student_id,
            'assessment_id' => $request->assessment_id,
            'score' => $score,
            'total_marks' => $request->total_marks,
            'status' => 'Completed',
            'badge' => $badge,
            'percentage' => $percentage,
        ]);

        if (session('active_assessment_session_id')) {
            AssessmentSession::where('id', session('active_assessment_session_id'))
                ->update([
                    'status' => 'Submitted',
                    'submitted_at' => now(),
                ]);

            session()->forget([
                'active_assessment_session_id',
                'active_assessment_id',
            ]);
        }

        return redirect()->route('student.history')
            ->with('success', 'Assessment submitted successfully');
    }
}