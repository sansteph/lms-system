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
    public function storeStudent(Request $request)
    {
        $request->validate([
            'student_id' => 'required|string|max:50',
            'name' => 'required|string|max:100',
            'institute' => 'required|string|max:100',
            'class' => 'required|string|max:50',
            'section' => 'required|string|max:20',
            'contact' => 'required|string|max:20',
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

        return redirect()->back()->with('success', 'Student added successfully');
    }
    public function updateStudent(Request $request, $id)
    {
        $request->validate([
            'student_id' => 'required|string|max:50',
            'name' => 'required|string|max:100',
            'institute' => 'required|string|max:100',
            'class' => 'required|string|max:50',
            'section' => 'required|string|max:20',
            'contact' => 'required|string|max:20',
            'status' => 'required|boolean',
        ]);

        $student = Student::findOrFail($id);

        $student->update([
            'student_id' => $request->student_id,
            'name' => $request->name,
            'institute' => $request->institute,
            'class' => $request->class,
            'section' => $request->section,
            'contact' => $request->contact,
            'status' => $request->status,
        ]);

        return redirect()->back()->with('success', 'Student updated successfully');
    }

    public function deleteStudent($id)
    {
        $student = Student::findOrFail($id);
        $student->delete();

        return redirect()->route('students')->with('success', 'Student deleted successfully!');
    }

    public function teacherDashboard()
    {
        return view('teacher-dashboard');
    }
    public function teacherClasses()
    {
        return view('teacher.my-classes');
    }
    public function teacherContent()
    {
        return view('teacher.teacher-content');
    }
    public function teacherAssessments()
    {
        return view('teacher.teacher-assessments');
    }
    public function teacherReports()
    {
        return view('teacher.teacher-reports');
    }
    public function teacherCertificates()
    {
        return view('teacher.teacher-certificates');
    }
    public function teacherNotifications()
    {
        return view('teacher.teacher-notifications');
    }
    public function teacherProfile()
    {
        return view('teacher.teacher-profile');
    }
}
