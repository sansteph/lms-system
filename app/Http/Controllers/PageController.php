<?php

namespace App\Http\Controllers;
use App\Models\Student;
use Illuminate\Http\Request;
use App\Models\Content;
use App\Models\Assessment;
use App\Models\Notification;
use App\Models\SchoolClass;
use Illuminate\Support\Facades\Hash;
use App\Models\AssessmentResult;
use App\Models\AssessmentQuestion;
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

        AssessmentResult::where('student_id', $id)->delete();

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

        return view('teacher.my-classes', compact('classes'));
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

    public function teacherResults(Request $request)
    {
        $search = $request->search;
        $badge = $request->badge;
        $status = $request->status;
        $sort = $request->sort;

        $results = AssessmentResult::with(['assessment', 'student'])

            ->when($search, function ($query, $search) {

                $query->whereHas('student', function ($q) use ($search) {

                    $q->where('name', 'like', "%{$search}%")
                    ->orWhere('student_id', 'like', "%{$search}%");

                })

                ->orWhereHas('assessment', function ($q) use ($search) {

                    $q->where('assessment_title', 'like', "%{$search}%");

                })

                ->orWhere('badge', 'like', "%{$search}%");

            })

            ->when($badge, function ($query, $badge) {

                $query->where('badge', $badge);

            })

            ->when($status, function ($query, $status) {

                $query->where('status', $status);

            })

            ->when($sort == 'highest', function ($query) {
                $query->orderByDesc('percentage');
            })

            ->when($sort == 'lowest', function ($query) {
                $query->orderBy('percentage');
            })

            ->when($sort == 'latest', function ($query) {
                $query->latest();
            })

            ->when($sort == 'oldest', function ($query) {
                $query->oldest();
            })

            ->get();

        $topPerformer = $results->sortByDesc('percentage')->first();
        $lowestPerformer = $results->sortBy('percentage')->first();

        $passPercentage = 0;

        if ($results->count() > 0) {
            $passedCount = $results->where('percentage', '>=', 50)->count();
            $passPercentage = ($passedCount / $results->count()) * 100;
        }

        return view('teacher.teacher-results', compact(
            'results',
            'topPerformer',
            'lowestPerformer',
            'passPercentage'
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
        $studentId = session('student_id');

        $results = AssessmentResult::where('student_id', $studentId)
                    ->latest()
                    ->get();

        $badgeCount = $results->whereNotNull('badge')->count();

        $attemptedAssessmentIds = AssessmentResult::where('student_id', $studentId)
                    ->pluck('assessment_id');

        $pendingAssessmentCount = Assessment::where('status', 1)
                    ->whereNotIn('id', $attemptedAssessmentIds)
                    ->count();

        $totalAssessmentCount = Assessment::where('status', 1)->count();

        $notifications = Notification::whereIn('target', ['Students', 'Class'])
                    ->latest()
                    ->take(3)
                    ->get();

        return view('student.student-dashboard', compact(
            'studentName',
            'studentCode',
            'results',
            'badgeCount',
            'notifications',
            'pendingAssessmentCount',
            'totalAssessmentCount'
        ));
    }
    public function studentTakeAssessment(Request $request)
    {
        $attemptedAssessmentIds = AssessmentResult::where('student_id', session('student_id'))->pluck('assessment_id');
        $assessments = Assessment::with('questions')
            ->where('status', 1)
            ->whereNotIn('id', $attemptedAssessmentIds)
            ->get()
            ->filter(function ($assessment) {
                return $assessment->questions->count() > 0;
            });

        $selectedAssessment = null;
        $questions = collect();

        if ($request->assessment_id) {
            $alreadyAttempted = AssessmentResult::where(
                    'student_id',
                    session('student_id')
                )->where(
                    'assessment_id',
                    $request->assessment_id
                )->exists();

                if ($alreadyAttempted) {
                    return redirect()->route('student.history')
                        ->with('error', 'Assessment already completed.');
                }
            $selectedAssessment = Assessment::find($request->assessment_id);
            if (!$selectedAssessment) {
                return redirect()->route('student.assessment')
                    ->with('error', 'Assessment not found.');
            }

            $questions = AssessmentQuestion::where('assessment_id', $request->assessment_id)->get();

            if ($questions->count() == 0) {
                return redirect()->route('student.assessment')
                    ->with('error', 'This assessment is not ready yet.');
            }

            $questions = \App\Models\AssessmentQuestion::where('assessment_id', $request->assessment_id)
                        ->get();
        }

        return view('student.student-assessment', compact(
            'assessments',
            'selectedAssessment',
            'questions'
        ));
    }
    public function studentHistory()
    {
        $studentId = session('student_id');

        $results = AssessmentResult::where('student_id', $studentId)
                    ->latest()
                    ->get();

        return view('student.student-history', compact('results'));
    }
    public function studentBadges()
    {
        $studentId = session('student_id');

        $results = AssessmentResult::where('student_id', $studentId)
                    ->whereNotNull('badge')
                    ->latest()
                    ->get();

        $goldCount = $results->where('badge', 'Gold')->count();
        $silverCount = $results->where('badge', 'Silver')->count();
        $bronzeCount = $results->where('badge', 'Bronze')->count();

        $certificateEligible = $results->count() >= 5;

        return view('student.student-badges', compact(
            'results',
            'goldCount',
            'silverCount',
            'bronzeCount',
            'certificateEligible',
        ));
    }
   public function studentNotifications()
    {
        $notifications = Notification::whereIn('target', ['Students', 'Class'])
                        ->latest()
                        ->get();

        return view('student.student-notifications', compact('notifications'));
    }
    public function studentProfile()
    {
        $student = Student::find(session('student_id'));

        $badgeCount = AssessmentResult::where('student_id', session('student_id'))
            ->whereNotNull('badge')
            ->count();

        return view('student.student-profile', compact('student', 'badgeCount'));
    }
    public function disqualifyResult($id)
    {
        $result = AssessmentResult::findOrFail($id);

        $result->delete();

        return redirect()->back()->with('success', 'Student result disqualified successfully');
    }
}
