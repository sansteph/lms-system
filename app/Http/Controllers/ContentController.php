<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Content;
use App\Models\Course;

class ContentController extends Controller
{
    public function index(Request $request)
    {
        $search = $request->search;

        $courses = Course::where('status', 1)
            ->when(session('user_role') == 'InstituteAdmin', function ($query) {
                $query->where(function ($q) {
                    $q->where('availability_type', 'Institute')
                      ->orWhere('availability_type', 'Both');
                });
            })
            ->get();

        $contents = Content::when(session('user_role') == 'InstituteAdmin', function ($query) {
                $query->where('institute', session('user_institute'));
            })
            ->when($search, function ($query, $search) {
                $query->where(function ($q) use ($search) {
                    $q->where('content_title', 'like', "%{$search}%")
                      ->orWhere('content_type', 'like', "%{$search}%")
                      ->orWhere('assigned_class', 'like', "%{$search}%");
                });
            })
            ->get();

        return view('content', compact('contents', 'courses'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'content_title' => 'required|string|max:255',
            'course_id' => 'required|exists:courses,id',
            'lesson_order' => 'required|integer|min:1',
            'content_type' => 'required|string',
            'assigned_class' => 'required|string|max:255',
            'file' => 'required|file',
            'status' => 'required|boolean',
            'institute' => session('user_role') == 'Admin'
                ? 'required|string|max:255'
                : 'nullable|string|max:255',
        ]);

        $filePath = null;

        if ($request->hasFile('file')) {
            $file = $request->file('file');
            $fileName = time() . '_' . $file->getClientOriginalName();
            $filePath = $file->storeAs('contents', $fileName, 'public');
        }

        Content::create([
            'institute' => session('user_role') == 'InstituteAdmin'
                ? session('user_institute')
                : $request->institute,
            'content_title' => $request->content_title,
            'course_id' => $request->course_id,
            'lesson_order' => $request->lesson_order,
            'content_type' => $request->content_type,
            'assigned_class' => $request->assigned_class,
            'file_path' => $filePath,
            'status' => $request->status,
        ]);

        return redirect()->back()->with('success', 'Content uploaded successfully');
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'content_title' => 'required|string|max:255',
            'course_id' => 'required|exists:courses,id',
            'lesson_order' => 'required|integer|min:1',
            'content_type' => 'required|string',
            'assigned_class' => 'required|string|max:255',
            'file' => 'nullable|file',
            'status' => 'required|boolean',
            'institute' => session('user_role') == 'Admin'
                ? 'required|string|max:255'
                : 'nullable|string|max:255',
        ]);

        $content = Content::findOrFail($id);

        if (
            session('user_role') == 'InstituteAdmin' &&
            $content->institute != session('user_institute')
        ) {
            abort(403, 'Unauthorized action.');
        }

        $filePath = $content->file_path;

        if ($request->hasFile('file')) {
            $file = $request->file('file');
            $fileName = time() . '_' . $file->getClientOriginalName();
            $filePath = $file->storeAs('contents', $fileName, 'public');
        }

        $content->update([
            'institute' => session('user_role') == 'InstituteAdmin'
                ? session('user_institute')
                : $request->institute,
            'content_title' => $request->content_title,
            'course_id' => $request->course_id,
            'lesson_order' => $request->lesson_order,
            'content_type' => $request->content_type,
            'assigned_class' => $request->assigned_class,
            'file_path' => $filePath,
            'status' => $request->status,
        ]);

        return redirect()->back()->with('success', 'Content updated successfully');
    }

    public function delete($id)
    {
        $content = Content::findOrFail($id);

        if (
            session('user_role') == 'InstituteAdmin' &&
            $content->institute != session('user_institute')
        ) {
            abort(403, 'Unauthorized action.');
        }

        $content->delete();

        return redirect()->back()->with('success', 'Content deleted successfully');
    }
}