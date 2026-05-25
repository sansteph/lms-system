<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Content;

class ContentController extends Controller
{
    public function index(Request $request)
    {
        $search = $request->search;
        $courses = \App\Models\Course::where('status', 1)->get();

        $contents = Content::when($search, function ($query, $search) {
            return $query->where('content_title', 'like', "%{$search}%")
                         ->orWhere('content_type', 'like', "%{$search}%")
                         ->orWhere('assigned_class', 'like', "%{$search}%");
        })->get();

        return view('content', compact('contents','courses'));
    }

    public function store(Request $request)
    {
        $request->validate([

            'content_title' => 'required|string|max:255',

            'course_category' => 'required|string|max:255',

            'lesson_order' => 'required|integer|min:1',

            'content_type' => 'required|string',

            'assigned_class' => 'required|string|max:255',

            'file' => 'required|file',

            'status' => 'required|boolean',

        ]);

        $filePath = null;

        if ($request->hasFile('file')) {

            $file = $request->file('file');

            $fileName = time() . '_' . $file->getClientOriginalName();

            $filePath = $file->storeAs('contents', $fileName, 'public');
        }

        Content::create([

            'content_title' => $request->content_title,

            'course_category' => $request->course_category,

            'lesson_order' => $request->lesson_order,

            'content_type' => $request->content_type,

            'assigned_class' => $request->assigned_class,

            'file' => $filePath,

            'status' => $request->status,

        ]);

        return redirect()->back()->with('success', 'Content uploaded successfully');
    }
    public function update(Request $request, $id)
    {
        $request->validate([

            'content_title' => 'required|string|max:255',

            'course_category' => 'required|string|max:255',

            'lesson_order' => 'required|integer|min:1',

            'content_type' => 'required|string',

            'assigned_class' => 'required|string|max:255',

            'status' => 'required|boolean',

        ]);

        $content = Content::findOrFail($id);

        $filePath = $content->file_path;

        if ($request->hasFile('file')) {
            $file = $request->file('file');
            $fileName = time() . '_' . $file->getClientOriginalName();
            $filePath = $file->storeAs('contents', $fileName, 'public');
        }

        $content->update([

            'content_title' => $request->content_title,

            'course_category' => $request->course_category,

            'lesson_order' => $request->lesson_order,

            'content_type' => $request->content_type,

            'assigned_class' => $request->assigned_class,

            'status' => $request->status,

        ]);

        return redirect()->back()->with('success', 'Content updated successfully');
    }
    public function delete($id)
    {
        $content = Content::findOrFail($id);

        $content->delete();

        return redirect()->back()->with('success', 'Content deleted successfully');
    }
}