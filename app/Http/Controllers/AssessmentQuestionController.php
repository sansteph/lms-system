<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\AssessmentQuestion;
use App\Models\Assessment;

class AssessmentQuestionController extends Controller
{
    public function index()
    {
        $questions = AssessmentQuestion::with('assessment')
            ->whereHas('assessment', function ($query) {
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
            'topic' => 'required|string|max:255',
            'question_type' => 'required|in:MCQ,Short Answer,Long Answer',
            'question' => 'required',
            'marks' => 'required|integer|min:1',

            'option_a' => 'nullable',
            'option_b' => 'nullable',
            'option_c' => 'nullable',
            'option_d' => 'nullable',
            'correct_answer' => 'nullable',

            'short_answer' => 'nullable',
            'long_answer' => 'nullable',

            'explanation' => 'nullable',
        ]);

        if ($request->question_type == 'MCQ') {
        $request->validate([
            'option_a' => 'required',
            'option_b' => 'required',
            'option_c' => 'required',
            'option_d' => 'required',
            'correct_answer' => 'required',
        ]);
        }

        if ($request->question_type == 'Short Answer') {
            $request->validate([
                'short_answer' => 'required',
            ]);
        }

        if ($request->question_type == 'Long Answer') {
            $request->validate([
                'long_answer' => 'required',
            ]);
        }

        if ($request->question_type != 'MCQ') {
            $request->merge([
                'option_a' => null,
                'option_b' => null,
                'option_c' => null,
                'option_d' => null,
                'correct_answer' => null,
            ]);
        }

        if ($request->question_type == 'MCQ') {
            $request->merge([
                'short_answer' => null,
                'long_answer' => null,
            ]);
        }

        $assessment = Assessment::findOrFail($request->assessment_id);

        if (
            session('user_role') == 'InstituteAdmin' &&
            $assessment->institute != session('user_institute')
        ) {
            abort(403, 'Unauthorized action.');
        }

        AssessmentQuestion::create([

            'assessment_id' => $request->assessment_id,

            'topic' => $request->topic,

            'question_type' => $request->question_type,

            'question' => $request->question,

            'option_a' => $request->option_a,
            'option_b' => $request->option_b,
            'option_c' => $request->option_c,
            'option_d' => $request->option_d,

            'correct_answer' => $request->correct_answer,

            'short_answer' => $request->short_answer,

            'long_answer' => $request->long_answer,

            'explanation' => $request->explanation,

            'marks' => $request->marks,

        ]);

        return redirect()->back()->with('success', 'Question added successfully');
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'assessment_id' => 'required|exists:assessments,id',
            'topic' => 'required|string|max:255',
            'question_type' => 'required|in:MCQ,Short Answer,Long Answer',
            'question' => 'required',
            'marks' => 'required|integer|min:1',

            'option_a' => 'nullable',
            'option_b' => 'nullable',
            'option_c' => 'nullable',
            'option_d' => 'nullable',
            'correct_answer' => 'nullable',

            'short_answer' => 'nullable',
            'long_answer' => 'nullable',

            'explanation' => 'nullable',
        ]);
        if ($request->question_type == 'MCQ') {
            $request->validate([
                'option_a' => 'required',
                'option_b' => 'required',
                'option_c' => 'required',
                'option_d' => 'required',
                'correct_answer' => 'required',
            ]);
        }

        if ($request->question_type == 'Short Answer') {
            $request->validate([
                'short_answer' => 'required',
            ]);
        }

        if ($request->question_type == 'Long Answer') {
            $request->validate([
                'long_answer' => 'required',
            ]);
        }

        if ($request->question_type != 'MCQ') {
            $request->merge([
                'option_a' => null,
                'option_b' => null,
                'option_c' => null,
                'option_d' => null,
                'correct_answer' => null,
            ]);
        }

        if ($request->question_type == 'MCQ') {
            $request->merge([
                'short_answer' => null,
                'long_answer' => null,
            ]);
        }

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
            'topic',
            'question_type',
            'short_answer',
            'long_answer',
            'explanation',  
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