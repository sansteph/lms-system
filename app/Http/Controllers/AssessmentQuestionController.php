<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\AssessmentQuestion;
use App\Models\Assessment;

class AssessmentQuestionController extends Controller
{
    public function index()
    {
        $questions = AssessmentQuestion::whereHas('assessment', function ($query) {
                if (session('user_role') == 'InstituteAdmin') {
                    $query->where('institute', session('user_institute'));
                }
            })
            ->latest()
            ->get();

        $assessments = Assessment::when(session('user_role') == 'InstituteAdmin', function ($query) {
                $query->where('institute', session('user_institute'));
            })
            ->get();

        return view('assessment-questions', compact('questions', 'assessments'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'assessment_id' => 'required|exists:assessments,id',
            'question' => 'required',
            'option_a' => 'required',
            'option_b' => 'required',
            'option_c' => 'required',
            'option_d' => 'required',
            'correct_answer' => 'required',
            'marks' => 'required|integer',
        ]);

        $assessment = Assessment::findOrFail($request->assessment_id);

        if (
            session('user_role') == 'InstituteAdmin' &&
            $assessment->institute != session('user_institute')
        ) {
            abort(403, 'Unauthorized action.');
        }

        AssessmentQuestion::create($request->only([
            'assessment_id',
            'question',
            'option_a',
            'option_b',
            'option_c',
            'option_d',
            'correct_answer',
            'marks',
        ]));

        return redirect()->back()->with('success', 'Question added successfully');
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'assessment_id' => 'required|exists:assessments,id',
            'question' => 'required',
            'option_a' => 'required',
            'option_b' => 'required',
            'option_c' => 'required',
            'option_d' => 'required',
            'correct_answer' => 'required',
            'marks' => 'required|integer',
        ]);

        $question = AssessmentQuestion::findOrFail($id);
        $assessment = Assessment::findOrFail($request->assessment_id);

        if (
            session('user_role') == 'InstituteAdmin' &&
            (
                $question->assessment->institute != session('user_institute') ||
                $assessment->institute != session('user_institute')
            )
        ) {
            abort(403, 'Unauthorized action.');
        }

        $question->update($request->only([
            'assessment_id',
            'question',
            'option_a',
            'option_b',
            'option_c',
            'option_d',
            'correct_answer',
            'marks',
        ]));

        return redirect()->back()->with('success', 'Question updated successfully');
    }

    public function delete($id)
    {
        $question = AssessmentQuestion::findOrFail($id);

        if (
            session('user_role') == 'InstituteAdmin' &&
            $question->assessment->institute != session('user_institute')
        ) {
            abort(403, 'Unauthorized action.');
        }

        $question->delete();

        return redirect()->back()->with('success', 'Question deleted successfully');
    }
}