<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Content;

class ContentController extends Controller
{
    public function index(Request $request)
    {
        $search = $request->search;

        $contents = Content::when($search, function ($query, $search) {
            return $query->where('content_title', 'like', "%{$search}%")
                         ->orWhere('content_type', 'like', "%{$search}%")
                         ->orWhere('assigned_class', 'like', "%{$search}%");
        })->get();

        return view('content', compact('contents'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'content_title' => 'required|string|max:255',
            'content_type' => 'required|string',
            'assigned_class' => 'required|string',
            'priority' => 'required|integer',
            'access_rule' => 'required|string',
            'file' => 'required|file|max:20480',
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
            'content_type' => $request->content_type,
            'assigned_class' => $request->assigned_class,
            'priority' => $request->priority,
            'access_rule' => $request->access_rule,
            'file_path' => $filePath,
            'status' => $request->status,
        ]);

        return redirect()->back()->with('success', 'Content uploaded successfully');
    }
    public function update(Request $request, $id)
    {
        $request->validate([
            'content_title' => 'required|string|max:255',
            'content_type' => 'required|string',
            'assigned_class' => 'required|string',
            'priority' => 'required|integer',
            'access_rule' => 'required|string',
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
            'content_type' => $request->content_type,
            'assigned_class' => $request->assigned_class,
            'priority' => $request->priority,
            'access_rule' => $request->access_rule,
            'file_path' => $filePath,
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