<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\AssessmentResult;

class AssessmentResultController extends Controller
{
    public function store(Request $request)
    {
        $request->validate([
            'student_id' => 'required',
            'assessment_id' => 'required',
            'score' => 'required|integer',
            'total_marks' => 'required|integer',
        ]);

        $percentage = 0;

        if ($request->total_marks > 0) {
            $percentage = ($request->score / $request->total_marks) * 100;
        }

        $badge = null;

        if ($percentage >= 90) {
            $badge = 'Gold';
        } elseif ($percentage >= 75) {
            $badge = 'Silver';
        } elseif ($percentage >= 50) {
            $badge = 'Bronze';
        }

        AssessmentResult::create([
            'student_id' => $request->student_id,
            'assessment_id' => $request->assessment_id,
            'score' => $request->score,
            'total_marks' => $request->total_marks,
            'status' => 'Completed',
            'badge' => $badge,
        ]);

        return redirect()->back()->with(
            'success',
            'Assessment submitted successfully'
        );
    }
}