<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\SchoolClass;

class ClassController extends Controller
{
    public function index(Request $request)
    {
        $search = $request->search;

        $classes = SchoolClass::when($search, function ($query, $search) {
            return $query->where('class_name', 'like', "%{$search}%")
                        ->orWhere('section', 'like', "%{$search}%")
                        ->orWhere('class_teacher', 'like', "%{$search}%")
                        ->orWhere('academic_year', 'like', "%{$search}%");
        })->get();

        return view('classes', compact('classes'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'class_name' => 'required|string|max:50',
            'section' => 'required|string|max:20',
            'class_teacher' => 'required|string|max:100',
            'academic_year' => 'required|string|max:20',
            'status' => 'required|boolean',
        ]);

        SchoolClass::create([
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
            'status' => 'required|boolean',
        ]);

        $class = SchoolClass::findOrFail($id);

        $class->update([
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

        $class->delete();

        return redirect()->back()->with('success', 'Class deleted successfully');
    }
}