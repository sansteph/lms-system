<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Content;
use App\Models\SchoolClass;

class ClassController extends Controller
{
    public function index(Request $request)
    {
        $search = $request->search;

        $contents = Content::when(
            session('user_role') == 'InstituteAdmin',
            function ($query) {
                $query->where('institute', session('user_institute'));
            }
        )->where('status', 1)
        ->orderBy('content_title')
        ->get();

        $classes = SchoolClass::when(session('user_role') == 'InstituteAdmin', function ($query) {
                $query->where('institute', session('user_institute'));
            })
            ->when($search, function ($query, $search) {
                $query->where(function ($q) use ($search) {
                    $q->where('class_name', 'like', "%{$search}%")
                    ->orWhere('section', 'like', "%{$search}%")
                    ->orWhere('class_teacher', 'like', "%{$search}%")
                    ->orWhere('academic_year', 'like', "%{$search}%");
                });
            })
            ->get();

        $stemEngineers = \App\Models\User::where('role', 'Teacher')
            ->where('status', 1)
            ->when(session('user_role') == 'InstituteAdmin', function ($query) {
                $query->where('institute', session('user_institute'));
            })
            ->orderBy('name')
            ->get();

        return view('classes', compact('classes', 'stemEngineers','contents'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'class_name' => 'required|string|max:50',
            'section' => 'required|string|max:20',
            'class_teacher' => 'required|string|max:100',
            'academic_year' => 'required|string|max:20',
            'status' => 'required|boolean',
            'content_id' => 'nullable|exists:contents,id',
            'institute' => session('user_role') == 'Admin'
                ? 'required|string|max:255'
                : 'nullable|string|max:255',
        ]);

        SchoolClass::create([
            'institute' => session('user_role') == 'InstituteAdmin'
                ? session('user_institute')
                : $request->institute,
            'class_name' => $request->class_name,
            'section' => $request->section,
            'class_teacher' => $request->class_teacher,
            'academic_year' => $request->academic_year,
            'status' => $request->status,
        ]);

        return redirect()->back()->with('success', 'Class added successfully');
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'class_name' => 'required|string|max:50',
            'section' => 'required|string|max:20',
            'class_teacher' => 'required|string|max:100',
            'academic_year' => 'required|string|max:20',
            'content_id' => 'nullable|exists:contents,id',
            'status' => 'required|boolean',
            'institute' => session('user_role') == 'Admin'
                ? 'required|string|max:255'
                : 'nullable|string|max:255',
        ]);

        $class = SchoolClass::findOrFail($id);

        if (
            session('user_role') == 'InstituteAdmin' &&
            $class->institute != session('user_institute')
        ) {
            abort(403, 'Unauthorized action.');
        }

        $class->update([
            'institute' => session('user_role') == 'InstituteAdmin'
                ? session('user_institute')
                : $request->institute,
            'class_name' => $request->class_name,
            'section' => $request->section,
            'class_teacher' => $request->class_teacher,
            'academic_year' => $request->academic_year,
            'status' => $request->status,
        ]);

        return redirect()->back()->with('success', 'Class updated successfully');
    }

    public function delete($id)
    {
        $class = SchoolClass::findOrFail($id);

        if (
            session('user_role') == 'InstituteAdmin' &&
            $class->institute != session('user_institute')
        ) {
            abort(403, 'Unauthorized action.');
        }

        $class->delete();

        return redirect()->back()->with('success', 'Class deleted successfully');
    }
}