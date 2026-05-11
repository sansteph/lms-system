<?php

namespace App\Http\Controllers;
use App\Models\Student;
use Illuminate\Http\Request;
use App\Models\Content;
use App\Models\Assessment;
use App\Models\Notification;
use App\Models\SchoolClass;
use Illuminate\Support\Facades\Hash;
class PageController extends Controller
{
    public function home()
    {
        return view('home');
    }

    public function adminLogin()
    {
        if (session('user_role') == 'Admin') {
            return redirect()->route('admin.dashboard');
        }

        return view('admin-login');
    }

    public function teacherLogin()
    {
        if (session('user_role') == 'Teacher') {
            return redirect()->route('teacher.dashboard');
        }

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
    public function students(Request $request)
    {
        $search = $request->search;

        $students = Student::when($search, function ($query, $search) {
            return $query->where('name', 'like', "%{$search}%")
                        ->orWhere('student_id', 'like', "%{$search}%")
                        ->orWhere('institute', 'like', "%{$search}%")
                        ->orWhere('class', 'like', "%{$search}%")
                        ->orWhere('section', 'like', "%{$search}%");
        })->get();

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
            'password' => 'required|min:6',
        ]);

        Student::create([
            'student_id' => $request->student_id,
            'name' => $request->name,
            'institute' => $request->institute,
            'class' => $request->class,
            'section' => $request->section,
            'contact' => $request->contact,
            'password' => Hash::make($request->password),
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
            'password' => 'nullable|min:6',
            'status' => 'required|boolean',
        ]);

        $student = Student::findOrFail($id);

        $studentData = [
            'student_id' => $request->student_id,
            'name' => $request->name,
            'institute' => $request->institute,
            'class' => $request->class,
            'section' => $request->section,
            'contact' => $request->contact,
            'status' => $request->status,
        ];

        if ($request->filled('password')) {
            $studentData['password'] = Hash::make($request->password);
        }

        $student->update($studentData);

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
        $teacherName = session('user_name');

        $contentCount = Content::count();
        $assessmentCount = Assessment::count();
        $notificationCount = Notification::count();

        return view('teacher.teacher-dashboard', compact(
            'teacherName',
            'contentCount',
            'assessmentCount',
            'notificationCount'
        ));
    }
    public function teacherClasses()
    {
        $classes = SchoolClass::latest()->get();

        return view('teacher.teacher-classes', compact('classes'));
    }
    public function teacherContent()
    {
        $contents = Content::latest()->get();

        return view('teacher.teacher-content', compact('contents'));
    }
    public function teacherAssessments()
    {
        $assessments = Assessment::latest()->get();

        return view('teacher.teacher-assessments', compact('assessments'));
    }
    public function teacherReports()
    {
        $studentCount = Student::count();
        $classCount = SchoolClass::count();
        $contentCount = Content::count();
        $assessmentCount = Assessment::count();

        return view('teacher.teacher-reports', compact(
            'studentCount',
            'classCount',
            'contentCount',
            'assessmentCount'
        ));
    }
    public function teacherCertificates()
    {
        return view('teacher.teacher-certificates');
    }
    public function teacherNotifications()
    {
        $notifications = Notification::latest()->get();

        return view('teacher.teacher-notifications', compact('notifications'));
    }
   public function teacherProfile()
    {
        $teacher = \App\Models\User::find(session('user_id'));

        return view('teacher.teacher-profile', compact('teacher'));
    }

    public function studentLogin()
    {
        return view('student-login');
    }
    public function studentLoginSubmit(Request $request)
    {
        $request->validate([
            'student_id' => 'required|string',
            'password' => 'required|string',
        ]);

        $student = Student::where('student_id', $request->student_id)
                        ->where('status', 1)
                        ->first();

        if ($student && Hash::check($request->password, $student->password)) {
            session([
                'student_id' => $student->id,
                'student_name' => $student->name,
                'student_code' => $student->student_id,
            ]);

            return redirect()->route('student.dashboard');
        }

        return redirect()->back()->with('error', 'Invalid student login details');
    }

    public function studentDashboard()
    {
        $studentName = session('student_name');
        $studentCode = session('student_code');

        return view('student.student-dashboard', compact(
            'studentName',
            'studentCode'
        ));
    }
    public function studentTakeAssessment()
    {
        return view('student.student-assessment');
    }
    public function studentHistory()
    {
        return view('student.student-history');
    }
    public function studentBadges()
    {
        return view('student.student-badges');
    }
    public function studentNotifications()
    {
        return view('student.student-notifications');
    }
    public function studentProfile()
    {
        $student = Student::find(session('student_id'));

        return view('student.student-profile', compact('student'));
    }
}
