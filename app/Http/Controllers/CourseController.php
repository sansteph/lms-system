<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Course;

class CourseController extends Controller
{
    public function index()
    {
        $courses = Course::when(session('user_role') == 'InstituteAdmin', function ($query) {
                $query->where('institute', session('user_institute'));
            })
            ->latest()
            ->get();

        return view('courses', compact('courses'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'course_title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'target' => 'required|string',
            'assigned_class' => 'nullable|string',
            'price' => 'required|numeric|min:0',
            'availability_type' => 'required',
            'is_active' => 'required',
            'institute' => session('user_role') == 'Admin'
                ? 'required|string|max:255'
                : 'nullable|string|max:255',
        ]);

        Course::create([
            'institute' => session('user_role') == 'InstituteAdmin'
                ? session('user_institute')
                : $request->institute,
            'course_title' => $request->course_title,
            'description' => $request->description,
            'target' => $request->target,
            'assigned_class' => $request->assigned_class,
            'price' => $request->price,
            'availability_type' => $request->availability_type,
            'is_active' => $request->is_active,
            'certificate_enabled' => 1,
            'status' => 1,
        ]);

        return redirect()->back()
            ->with('success', 'Course created successfully');
    }

    public function update(Request $request, $id)
    {
        $course = Course::findOrFail($id);

        if (
            session('user_role') == 'InstituteAdmin' &&
            $course->institute != session('user_institute')
        ) {
            abort(403, 'Unauthorized action.');
        }

        $request->validate([
            'course_title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'target' => 'required|string',
            'assigned_class' => 'nullable|string',
            'price' => 'required|numeric|min:0',
            'availability_type' => 'required',
            'is_active' => 'required',
            'institute' => session('user_role') == 'Admin'
                ? 'required|string|max:255'
                : 'nullable|string|max:255',
        ]);

        $course->update([
            'institute' => session('user_role') == 'InstituteAdmin'
                ? session('user_institute')
                : $request->institute,
            'course_title' => $request->course_title,
            'description' => $request->description,
            'target' => $request->target,
            'assigned_class' => $request->assigned_class,
            'price' => $request->price,
            'availability_type' => $request->availability_type,
            'is_active' => $request->is_active,
            'status' => $request->is_active,
        ]);

        return redirect()->back()
            ->with('success', 'Course updated successfully');
    }

    public function delete($id)
    {
        $course = Course::findOrFail($id);

        if (
            session('user_role') == 'InstituteAdmin' &&
            $course->institute != session('user_institute')
        ) {
            abort(403, 'Unauthorized action.');
        }

        $course->delete();

        return redirect()->back()
            ->with('success', 'Course deleted successfully');
    }
}