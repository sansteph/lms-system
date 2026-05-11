<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\AssessmentResult;
use App\Models\AssessmentQuestion;

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

        $score = 0;
        $answers = $request->answers ?? [];

        foreach ($answers as $questionId => $selectedAnswer) {

            $question = \App\Models\AssessmentQuestion::find($questionId);

            if ($question && $question->correct_answer == $selectedAnswer) {
                $score += $question->marks;
            }
        }

        $percentage = 0;

        if ($request->total_marks > 0) {
            $percentage = ($score / $request->total_marks) * 100;
        }

        $badge = null;

        if ($percentage >= 90) {
            $badge = 'Gold';
        } elseif ($percentage >= 75) {
            $badge = 'Silver';
        } elseif ($percentage >= 50) {
            $badge = 'Bronze';
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
            'score' => $score,
            'total_marks' => $request->total_marks,
            'status' => 'Completed',
            'badge' => $badge,
            'percentage' => $percentage,
        ]);

        return redirect()->route('student.history')
            ->with('success', 'Assessment submitted successfully');
    }
}