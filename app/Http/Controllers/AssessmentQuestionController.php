<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\AssessmentQuestion;
use App\Models\Assessment;

class AssessmentQuestionController extends Controller
{
    public function index()
    {
        $questions = AssessmentQuestion::latest()->get();

        $assessments = Assessment::all();

        return view('assessment-questions', compact(
            'questions',
            'assessments'
        ));
    }

    public function store(Request $request)
    {
        $request->validate([
            'assessment_id' => 'required',
            'question' => 'required',
            'option_a' => 'required',
            'option_b' => 'required',
            'option_c' => 'required',
            'option_d' => 'required',
            'correct_answer' => 'required',
            'marks' => 'required|integer',
        ]);

        AssessmentQuestion::create([
            'assessment_id' => $request->assessment_id,
            'question' => $request->question,
            'option_a' => $request->option_a,
            'option_b' => $request->option_b,
            'option_c' => $request->option_c,
            'option_d' => $request->option_d,
            'correct_answer' => $request->correct_answer,
            'marks' => $request->marks,
        ]);

        

        return redirect()->back()->with(
            'success',
            'Question added successfully'
        );
    }
    public function update(Request $request, $id)
    {
        $request->validate([
            'assessment_id' => 'required',
            'question' => 'required',
            'option_a' => 'required',
            'option_b' => 'required',
            'option_c' => 'required',
            'option_d' => 'required',
            'correct_answer' => 'required',
            'marks' => 'required|integer',
        ]);

        $question = AssessmentQuestion::findOrFail($id);

        $question->update([
            'assessment_id' => $request->assessment_id,
            'question' => $request->question,
            'option_a' => $request->option_a,
            'option_b' => $request->option_b,
            'option_c' => $request->option_c,
            'option_d' => $request->option_d,
            'correct_answer' => $request->correct_answer,
            'marks' => $request->marks,
        ]);

        return redirect()->back()->with('success', 'Question updated successfully');
    }

    public function delete($id)
    {
        $question = AssessmentQuestion::findOrFail($id);

        $question->delete();

        return redirect()->back()->with('success', 'Question deleted successfully');
    }
}