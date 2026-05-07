<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Assessment;

class AssessmentController extends Controller
{
    public function index(Request $request)
    {
        $search = $request->search;

        $assessments = Assessment::when($search, function ($query, $search) {
            return $query->where('assessment_title', 'like', "%{$search}%")
                         ->orWhere('assessment_type', 'like', "%{$search}%")
                         ->orWhere('assigned_class', 'like', "%{$search}%");
        })->get();

        return view('assessments', compact('assessments'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'assessment_title' => 'required|string|max:255',
            'assessment_type' => 'required|string',
            'assigned_class' => 'required|string',
            'total_marks' => 'required|integer',
            'duration' => 'required|string|max:50',
            'question_paper_type' => 'nullable|string',
            'file' => 'nullable|file|max:20480',
            'status' => 'required|boolean',
        ]);

        $filePath = null;

        if ($request->hasFile('file')) {
            $file = $request->file('file');
            $fileName = time() . '_' . $file->getClientOriginalName();
            $filePath = $file->storeAs('assessments', $fileName, 'public');
        }

        Assessment::create([
            'assessment_title' => $request->assessment_title,
            'assessment_type' => $request->assessment_type,
            'assigned_class' => $request->assigned_class,
            'total_marks' => $request->total_marks,
            'duration' => $request->duration,
            'question_paper_type' => $request->question_paper_type,
            'file_path' => $filePath,
            'status' => $request->status,
        ]);

        return redirect()->back()->with('success', 'Assessment created successfully');
    }
    public function update(Request $request, $id)
    {
        $request->validate([
            'assessment_title' => 'required|string|max:255',
            'assessment_type' => 'required|string',
            'assigned_class' => 'required|string',
            'total_marks' => 'required|integer',
            'duration' => 'required|string|max:50',
            'question_paper_type' => 'nullable|string',
            'file' => 'nullable|file|max:20480',
            'status' => 'required|boolean',
        ]);

        $assessment = Assessment::findOrFail($id);

        $filePath = $assessment->file_path;

        if ($request->hasFile('file')) {
            $file = $request->file('file');
            $fileName = time() . '_' . $file->getClientOriginalName();
            $filePath = $file->storeAs('assessments', $fileName, 'public');
        }

        $assessment->update([
            'assessment_title' => $request->assessment_title,
            'assessment_type' => $request->assessment_type,
            'assigned_class' => $request->assigned_class,
            'total_marks' => $request->total_marks,
            'duration' => $request->duration,
            'question_paper_type' => $request->question_paper_type,
            'file_path' => $filePath,
            'status' => $request->status,
        ]);

        return redirect()->back()->with('success', 'Assessment updated successfully');
    }
    public function delete($id)
    {
        $assessment = Assessment::findOrFail($id);

        $assessment->delete();

        return redirect()->back()->with('success', 'Assessment deleted successfully');
    }
}