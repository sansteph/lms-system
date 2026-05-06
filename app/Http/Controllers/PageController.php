<?php

namespace App\Http\Controllers;
use App\Models\Student;
use Illuminate\Http\Request;

class PageController extends Controller
{
    public function home()
    {
        return view('home');
    }

    public function adminLogin()
    {
        return view('admin-login');
    }

    public function teacherLogin()
    {
        return view('teacher-login');
    }

    public function studentAssessment()
    {
        return view('student-assessment');
    }

    public function adminDashboard()
    {
        return view('admin-dashboard');
    }
    public function students()
    {
    $students = Student::latest()->get();
    return view('students', compact('students'));
    }
    public function classes()
    {
        return view('classes');
    }
    public function content()
    {
        return view('content');
    }
    public function assessments()
    {
        return view('assessments');
    }
    public function users()
    {
        return view('users');
    }
    public function institutes()
    {
        return view('institutes');
    }
    public function reports()
    {
        return view('reports');
    }
    public function notifications()
    {
        return view('notifications');
    }
    public function updateStudent(Request $request, $id)
{
    $student = Student::findOrFail($id);

    $request->validate([
        'student_id' => 'required|unique:students,student_id,' . $id,
        'name' => 'required',
        'institute' => 'required',
        'class' => 'required',
        'section' => 'required',
        'contact' => 'required',
    ]);

    $student->update([
        'student_id' => $request->student_id,
        'name' => $request->name,
        'institute' => $request->institute,
        'class' => $request->class,
        'section' => $request->section,
        'contact' => $request->contact,
        'status' => $request->status,
    ]);

    return redirect()->route('students')->with('success', 'Student updated successfully!');
}

public function deleteStudent($id)
{
    $student = Student::findOrFail($id);
    $student->delete();

    return redirect()->route('students')->with('success', 'Student deleted successfully!');
}
    public function storeStudent(Request $request)
    {
        $request->validate([
            'student_id' => 'required|unique:students,student_id',
            'name' => 'required',
            'institute' => 'required',
            'class' => 'required',
            'section' => 'required',
            'contact' => 'required',
        ]);

        Student::create([
            'student_id' => $request->student_id,
            'name' => $request->name,
            'institute' => $request->institute,
            'class' => $request->class,
            'section' => $request->section,
            'contact' => $request->contact,
            'status' => 1,
        ]);

        return redirect()->route('students')->with('success', 'Student added successfully!');
    }
}
