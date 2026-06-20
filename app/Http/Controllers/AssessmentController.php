<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Assessment;
use App\Models\AssessmentResult;
use App\Models\AssessmentQuestion;
use Illuminate\Support\Facades\Storage;

class AssessmentController extends Controller
{
    public function index(Request $request)
    {
        $search = $request->search;

        $assessments = Assessment::with('questions')
            ->when(session('user_role') == 'InstituteAdmin', function ($query) {
                $query->where('institute', session('user_institute'));
            })
            ->when($search, function ($query, $search) {
                $query->where(function ($q) use ($search) {
                    $q->where('assessment_title', 'like', "%{$search}%")
                    ->orWhere('assessment_type', 'like', "%{$search}%")
                    ->orWhere('assigned_class', 'like', "%{$search}%");
                });
            })
            ->get();

        $contents = \App\Models\Content::where('status', 1)
            ->when(session('user_role') == 'InstituteAdmin', function ($query) {
                $query->where('institute', session('user_institute'));
            })
            ->orderBy('lesson_order')
            ->get();

        return view('assessments', compact('assessments', 'contents'));
    }

    public function store(Request $request)
    {
        if (session('user_role') != 'Teacher') {
            abort(403, 'Only STEM Engineers can create assessments.');
        }
        $request->validate([
            'assessment_title' => 'required|string|max:255',
            'assessment_type' => 'required|string',
            'assigned_class' => 'required|string',
            'total_marks' => 'required|integer',
            'duration' => 'required|string|max:50',
            'question_paper_type' => 'nullable|string',
            'file' => 'nullable|file|max:20480',
            'status' => 'required|boolean',
            'content_id' => 'required|exists:contents,id',
            'institute' => session('user_role') == 'Admin'
                ? 'required|string|max:255'
                : 'nullable|string|max:255',
        ]);

        $filePath = null;

        if ($request->hasFile('file')) {
            $file = $request->file('file');
            $fileName = time() . '_' . $file->getClientOriginalName();
            $filePath = $file->storeAs('assessments', $fileName, 'public');
        }

        Assessment::create([
            'institute' => session('user_role') == 'InstituteAdmin'
                ? session('user_institute')
                : $request->institute,
            'assessment_title' => $request->assessment_title,
            'assessment_type' => $request->assessment_type,
            'assigned_class' => $request->assigned_class,
            'total_marks' => $request->total_marks,
            'duration' => $request->duration,
            'question_paper_type' => $request->question_paper_type,
            'file_path' => $filePath,
            'status' => $request->status,
            'content_id' => $request->content_id,
        ]);

        return redirect()->back()
            ->with('success', 'Assessment created successfully');
    }

    public function update(Request $request, $id)
    {
        if (session('user_role') != 'Teacher') {
            abort(403, 'Only STEM Engineers can edit assessments.');
        }
        $request->validate([
            'assessment_title' => 'required|string|max:255',
            'assessment_type' => 'required|string',
            'assigned_class' => 'required|string',
            'total_marks' => 'required|integer',
            'duration' => 'required|string|max:50',
            'question_paper_type' => 'nullable|string',
            'file' => 'nullable|file|max:20480',
            'status' => 'required|boolean',
            'content_id' => 'required|exists:contents,id',
            'institute' => session('user_role') == 'Admin'
                ? 'required|string|max:255'
                : 'nullable|string|max:255',
        ]);

        $assessment = Assessment::findOrFail($id);

        if (
            session('user_role') == 'InstituteAdmin' &&
            $assessment->institute != session('user_institute')
        ) {
            abort(403, 'Unauthorized action.');
        }

        $filePath = $assessment->file_path;

        if ($request->hasFile('file')) {

            if ($assessment->file_path &&
                Storage::disk('public')->exists($assessment->file_path)) {

                Storage::disk('public')->delete($assessment->file_path);
            }

            $file = $request->file('file');

            $fileName = time() . '_' . $file->getClientOriginalName();

            $filePath = $file->storeAs(
                'assessments',
                $fileName,
                'public'
            );
        }

        $assessment->update([
            'institute' => session('user_role') == 'InstituteAdmin'
                ? session('user_institute')
                : $request->institute,
            'assessment_title' => $request->assessment_title,
            'assessment_type' => $request->assessment_type,
            'assigned_class' => $request->assigned_class,
            'total_marks' => $request->total_marks,
            'duration' => $request->duration,
            'question_paper_type' => $request->question_paper_type,
            'file_path' => $filePath,
            'status' => $request->status,
            'content_id' => $request->content_id,
        ]);

        return redirect()->back()
            ->with('success', 'Assessment updated successfully');
    }

    public function delete($id)
    {
        if (session('user_role') != 'Teacher') {
            abort(403, 'Only STEM Engineers can delete assessments.');
        }
        $assessment = Assessment::findOrFail($id);

        if (
            session('user_role') == 'InstituteAdmin' &&
            $assessment->institute != session('user_institute')
        ) {
            abort(403, 'Unauthorized action.');
        }

        AssessmentResult::where('assessment_id', $id)->delete();
        AssessmentQuestion::where('assessment_id', $id)->delete();

        if ($assessment->file_path &&
            Storage::disk('public')->exists($assessment->file_path)) {

            Storage::disk('public')->delete($assessment->file_path);
        }

        $assessment->delete();

        return redirect()->back()
            ->with('success', 'Assessment deleted successfully. Related questions, results, badges, and history were also removed.');
    }
}