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
use App\Models\Certificate;
use App\Models\UserSession;
use App\Models\UserActivityLog;
use Symfony\Component\HttpFoundation\StreamedResponse;
use App\Models\StudentAchievement;
use App\Models\LessonProgress;  
use App\Models\AccessRequest;
use App\Models\CertificateVerificationLog;
use App\Models\AssessmentSession;
use App\Models\User;
use App\Models\Course;
use App\Models\ClassContentSession;
use Barryvdh\DomPDF\Facade\Pdf;
use App\Models\ClassTimetable;
use App\Models\Institute;
use App\Models\IndependentLearner;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;




class PageController extends Controller
{
    public function home()
    {
        $institutionalLearners = Student::where('status', 1)->count();

        $independentLearners = IndependentLearner::where('status', 1)->count();

        $activeLearners = $institutionalLearners + $independentLearners;

        $assessmentCount = Assessment::count();

        $institutionCount = Institute::count();

        return view('home', compact(
            'activeLearners',
            'assessmentCount',
            'institutionCount'
        ));
    }

    public function portal()
    {
        return view('portal');
    }

    public function storeAccessRequest(Request $request)
    {
        $request->validate([

            'name' => 'required|string|max:255',

            'email' => 'required|email|max:255',

            'phone' => 'required|string|max:20',

            'role' => 'required|string',

            'institute_name' => 'required|string|max:255',

            'message' => 'nullable|string',

        ]);

        AccessRequest::create([

            'name' => $request->name,

            'email' => $request->email,

            'phone' => $request->phone,

            'role' => $request->role,

            'institute_name' => $request->institute_name,

            'message' => $request->message,

            'status' => 'Pending',

        ]);

        return redirect()
            ->back()
            ->with('success', 'Access request submitted successfully.');
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
        $isInstituteAdmin = session('user_role') == 'InstituteAdmin';
        $institute = session('user_institute');

        $studentCount = Student::when($isInstituteAdmin, function ($query) use ($institute) {
                $query->where('institute', $institute);
            })
            ->count();

        $teacherCount = User::where('role', 'Teacher')
            ->when($isInstituteAdmin, function ($query) use ($institute) {
                $query->where('institute', $institute);
            })
            ->count();

        $classCount = SchoolClass::when($isInstituteAdmin, function ($query) use ($institute) {
                $query->where('institute', $institute);
            })
            ->count();

        $assessmentCount = Assessment::when($isInstituteAdmin, function ($query) use ($institute) {
                $query->where('institute', $institute);
            })
            ->count();

        return view('admin-dashboard', compact(
            'studentCount',
            'teacherCount',
            'classCount',
            'assessmentCount'
        ));
    }
    public function students(Request $request)
    {
        $search = $request->search;

        $students = Student::when(session('user_role') == 'InstituteAdmin', function ($query) {
                $query->where('institute', session('user_institute'));
            })
            ->when($search, function ($query, $search) {
                $query->where(function ($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                    ->orWhere('student_id', 'like', "%{$search}%")
                    ->orWhere('institute', 'like', "%{$search}%")
                    ->orWhere('class', 'like', "%{$search}%")
                    ->orWhere('section', 'like', "%{$search}%");
                });
            })
            ->get();

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
            'class' => 'required|string|max:50',
            'section' => 'required|string|max:20',
            'contact' => 'required|string|max:20',
            'password' => 'required|min:6',
        ]);

        $institute = session('user_role') == 'InstituteAdmin'
            ? session('user_institute')
            : $request->institute;

        Student::create([
            'student_id' => $request->student_id,
            'name' => $request->name,
            'institute' => $institute,
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

        if (
            session('user_role') == 'InstituteAdmin' &&
            $student->institute != session('user_institute')
        ) 
        {
            abort(403, 'Unauthorized action.');
        }

        $studentData = [
            'student_id' => $request->student_id,
            'name' => $request->name,
            'institute' => session('user_role') == 'InstituteAdmin'? session('user_institute'): $request->institute,
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

        if (
            session('user_role') == 'InstituteAdmin' &&
            $student->institute != session('user_institute')
        ) {
            abort(403, 'Unauthorized action.');
        }

        DB::transaction(function () use ($student, $id) {

            AssessmentResult::where('student_id', $id)->delete();

            LessonProgress::where('student_id', $id)->delete();

            UserSession::where('user_type', 'Student')
                ->where('user_id', $id)
                ->delete();

            Certificate::where('student_id', $id)->delete();

            $achievements = StudentAchievement::where('student_id', $id)->get();

            foreach ($achievements as $achievement) {

                if (
                    $achievement->certificate_file &&
                    Storage::disk('public')->exists($achievement->certificate_file)
                ) {
                    Storage::disk('public')->delete($achievement->certificate_file);
                }

                $achievement->delete();
            }

            $student->delete();
        });

        return redirect()
            ->route('students')
            ->with('success', 'Student and all related records deleted successfully.');
    }

    public function adminCertificates()
    {
        $certificates = Certificate::with(['student', 'course'])
            ->when(session('user_role') == 'InstituteAdmin', function ($query) {
                $query->whereHas('student', function ($q) {
                    $q->where('institute', session('user_institute'));
                });
            })
            ->latest()
            ->get();

        return view('certificates', compact('certificates'));
    }

    public function approveCertificate($id)
    {
        $certificate = Certificate::with('student')->findOrFail($id);

        if (
            session('user_role') == 'InstituteAdmin' &&
            $certificate->student &&
            $certificate->student->institute != session('user_institute')
        ) {
            abort(403, 'Unauthorized action.');
        }

        if ($certificate->status == 'Revoked') {
            return redirect()->back()
                ->with('error', 'Revoked certificates must be reissued instead of approved.');
        }

        $finalScore = $certificate->final_score ?? $certificate->badge_count ?? 0;

        if ($finalScore < 40) {
            return redirect()->back()
                ->with('error', 'Students below 40% are not eligible for certificate approval.');
        }

        $certificate->update([
            'status' => 'Issued',
            'issued_date' => now(),
            'final_score' => $finalScore,
            'final_grade' => $this->calculateCertificateGrade($finalScore),
            'final_classification' => $this->calculateCertificateClassification($finalScore),
        ]);

        return redirect()->back()
            ->with('success', 'Certificate approved and released to the student.');
    }

    public function revokeCertificate($id)
    {
        $certificate = Certificate::with('student')->findOrFail($id);

        if (
            session('user_role') == 'InstituteAdmin' &&
            $certificate->student &&
            $certificate->student->institute != session('user_institute')
        ) {
            abort(403, 'Unauthorized action.');
        }

        $certificate->update([
            'status' => 'Revoked'
        ]);

        return redirect()->back()->with('success', 'Certificate revoked successfully.');
    }

    public function teacherDashboard()
    {
        $teacherName = session('user_name');
        $teacher = User::find(session('user_id'));
        $assignedClassNames = $this->teacherAssignedClassNames($teacher);
        $classes = SchoolClass::where('institute', $teacher->institute)
            ->where('class_teacher', $teacher->name)
            ->get();
        $contentCount = Content::where('institute', $teacher->institute)
            ->whereHas('course', function ($query) use ($assignedClassNames) {
                $query->whereIn('assigned_class', $assignedClassNames);
            })
            ->count();
        $assessmentCount = Assessment::where('institute', $teacher->institute)
            ->where('teacher_id', $teacher->id)
            ->count();
        $monthlyAssessmentCount = Assessment::where('institute', $teacher->institute)
            ->where('teacher_id', $teacher->id)
            ->where('assessment_category', 'Monthly')
            ->count();
        $annualAssessmentCount = Assessment::where('institute', $teacher->institute)
            ->where('teacher_id', $teacher->id)
            ->where('assessment_category', 'Annual')
            ->count();
        $notificationCount = Notification::where('institute', $teacher->institute)->count();
        $assignedClasses = $classes->count();
        $totalStudents = $this->teacherAssignedStudentsQuery($teacher)->count();

        return view('teacher.teacher-dashboard', compact(
            'teacherName',
            'contentCount',
            'assessmentCount',
            'monthlyAssessmentCount',
            'annualAssessmentCount',
            'notificationCount',
            'assignedClasses',
            'totalStudents',
            'classes',
        ));
    }

    public function teacherClasses()
    {
        $teacher = User::find(session('user_id'));

        $today = now()->format('Y-m-d');

        $scheduledClasses = ClassTimetable::with([
                'schoolClass',
                'content'
            ])
            ->where('session_date', $today)
            ->whereHas('schoolClass', function ($q) use ($teacher) {
                $q->where('institute', $teacher->institute)
                    ->where('class_teacher', $teacher->name);
            })
            ->orderBy('from_time')
            ->get();

        $activeSessions = ClassContentSession::where(
                'stem_engineer_id',
                session('user_id')
            )
            ->where('status', 'Started')
            ->get()
            ->keyBy('timetable_id');

        return view(
            'teacher.my-classes',
            compact(
                'scheduledClasses',
                'activeSessions'
            )
        );
    }

    public function teacherContent()
    {
        $teacher = User::find(session('user_id'));
        $assignedClasses = $this->teacherAssignedClassNames($teacher);

        $contents = Content::where('institute', $teacher->institute)
            ->whereHas('course', function ($query) use ($assignedClasses) {
                $query->whereIn('assigned_class', $assignedClasses);
            })
            ->latest()
            ->get();

        return view('teacher.teacher-content', compact('contents'));
    }

    public function teacherAssessments()
    {
        $teacher = User::find(session('user_id'));
        $assignedClasses = $this->teacherAssignedClassNames($teacher);

        $assessments = Assessment::where('institute', $teacher->institute)
            ->where('teacher_id', $teacher->id)
            ->whereIn('assigned_class', $assignedClasses)
            ->latest()
            ->get();

        return view('teacher.teacher-assessments', compact('assessments'));
    }

   public function teacherReports()
    {
        $teacher = User::find(session('user_id'));
        $assignedClassNames = $this->teacherAssignedClassNames($teacher);
        $studentIds = $this->teacherAssignedStudentIds($teacher);

        $studentCount = $studentIds->count();

        $classCount = SchoolClass::where('institute', $teacher->institute)
            ->where('class_teacher', $teacher->name)
            ->count();

        $contentCount = Content::where('institute', $teacher->institute)
            ->whereHas('course', function ($query) use ($assignedClassNames) {
                $query->whereIn('assigned_class', $assignedClassNames);
            })
            ->count();

        $assessmentCount = Assessment::where('institute', $teacher->institute)
            ->where('teacher_id', $teacher->id)
            ->count();

        $monthlyAssessmentCount = Assessment::where('institute', $teacher->institute)
            ->where('teacher_id', $teacher->id)
            ->where('assessment_category', 'Monthly')
            ->count();

        $annualAssessmentCount = Assessment::where('institute', $teacher->institute)
            ->where('teacher_id', $teacher->id)
            ->where('assessment_category', 'Annual')
            ->count();

        $completedResults = AssessmentResult::whereIn('student_id', $studentIds)
            ->whereHas('assessment', function ($query) use ($teacher) {
                $query->where('teacher_id', $teacher->id);
            })
            ->where('status', 'Completed')
            ->count();

        $pendingReviewCount = AssessmentResult::whereIn('student_id', $studentIds)
            ->whereHas('assessment', function ($query) use ($teacher) {
                $query->where('teacher_id', $teacher->id);
            })
            ->where('status', 'Pending Review')
            ->count();

        $averageScore = AssessmentResult::whereIn('student_id', $studentIds)
            ->whereHas('assessment', function ($query) use ($teacher) {
                $query->where('teacher_id', $teacher->id);
            })
            ->where('status', 'Completed')
            ->avg('percentage') ?? 0;

        $certificateCount = Certificate::whereIn('student_id', $studentIds)
            ->count();

        return view('teacher.teacher-reports', compact(
            'studentCount',
            'classCount',
            'contentCount',
            'assessmentCount',
            'monthlyAssessmentCount',
            'annualAssessmentCount',
            'completedResults',
            'pendingReviewCount',
            'averageScore',
            'certificateCount'
        ));
    }

    public function exportTeacherReports()
    {
        $teacher = User::findOrFail(session('user_id'));
        $assignedClassNames = $this->teacherAssignedClassNames($teacher);
        $studentIds = $this->teacherAssignedStudentIds($teacher);

        $studentCount = $studentIds->count();

        $classCount = SchoolClass::where('institute', $teacher->institute)
            ->where('class_teacher', $teacher->name)
            ->count();

        $contentCount = Content::where('institute', $teacher->institute)
            ->whereHas('course', function ($query) use ($assignedClassNames) {
                $query->whereIn('assigned_class', $assignedClassNames);
            })
            ->count();

        $assessmentCount = Assessment::where('institute', $teacher->institute)
            ->where('teacher_id', $teacher->id)
            ->count();

        $monthlyAssessmentCount = Assessment::where('institute', $teacher->institute)
            ->where('teacher_id', $teacher->id)
            ->where('assessment_category', 'Monthly')
            ->count();

        $annualAssessmentCount = Assessment::where('institute', $teacher->institute)
            ->where('teacher_id', $teacher->id)
            ->where('assessment_category', 'Annual')
            ->count();

        $completedResults = AssessmentResult::whereIn('student_id', $studentIds)
            ->whereHas('assessment', function ($query) use ($teacher) {
                $query->where('teacher_id', $teacher->id);
            })
            ->where('status', 'Completed')
            ->count();

        $pendingReviewCount = AssessmentResult::whereIn('student_id', $studentIds)
            ->whereHas('assessment', function ($query) use ($teacher) {
                $query->where('teacher_id', $teacher->id);
            })
            ->where('status', 'Pending Review')
            ->count();

        $averageScore = AssessmentResult::whereIn('student_id', $studentIds)
            ->whereHas('assessment', function ($query) use ($teacher) {
                $query->where('teacher_id', $teacher->id);
            })
            ->where('status', 'Completed')
            ->avg('percentage') ?? 0;

        $certificateCount = Certificate::whereIn('student_id', $studentIds)
            ->count();

        $filename = 'stem_engineer_report_' . now()->format('Ymd_His') . '.csv';

        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ];

        $rows = [
            ['Report Area', 'Metric', 'Current Value', 'Status'],
            ['Students', 'Students in Assigned Classes', $studentCount, 'Live'],
            ['Classes', 'Assigned Classes', $classCount, 'Live'],
            ['Content', 'Assigned Content', $contentCount, 'Live'],
            ['Assessments', 'Your Assessments', $assessmentCount . ' total | ' . $monthlyAssessmentCount . ' monthly | ' . $annualAssessmentCount . ' annual', 'Live'],
            ['Assessment Results', 'Completed Results', $completedResults, 'Completed'],
            ['Manual Reviews', 'Pending Written Answers', $pendingReviewCount, 'Pending'],
            ['Performance', 'Average Assessment Score', number_format($averageScore, 2) . '%', 'Calculated'],
            ['Certificates', 'Total Certificates Issued', $certificateCount, 'Live'],
        ];

        $callback = function () use ($rows) {
            $file = fopen('php://output', 'w');

            foreach ($rows as $row) {
                fputcsv($file, $row);
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    public function teacherResults(Request $request)
    {
        $search = $request->search;
        $badge = $request->badge;
        $status = $request->status;
        $sort = $request->sort;

        $teacher = User::find(session('user_id'));
        $studentIds = $this->teacherAssignedStudentIds($teacher);

        $results = AssessmentResult::with(['assessment', 'student'])
            ->whereIn('student_id', $studentIds)
            ->whereHas('assessment', function ($query) use ($teacher) {
                $query->where('teacher_id', $teacher->id);
            })
            ->when($search, function ($query, $search) {

                $query->where(function ($searchQuery) use ($search) {
                    $searchQuery->whereHas('student', function ($q) use ($search) {
                        $q->where('name', 'like', "%{$search}%")
                            ->orWhere('student_id', 'like', "%{$search}%");
                    })
                    ->orWhereHas('assessment', function ($q) use ($search) {
                        $q->where('assessment_title', 'like', "%{$search}%");
                    })
                    ->orWhere('badge', 'like', "%{$search}%");
                });

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
        $teacher = User::find(session('user_id'));

        $certificates = Certificate::with(['student', 'course'])
            ->whereIn('student_id', $this->teacherAssignedStudentIds($teacher))
            ->latest()
            ->get();

        $totalCertificates = $certificates->count();

        $issuedCertificates = $certificates
            ->where('status', 'Issued')
            ->count();

        $revokedCertificates = $certificates
            ->where('status', 'Revoked')
            ->count();

        return view('teacher.teacher-certificates', compact(
            'certificates',
            'totalCertificates',
            'issuedCertificates',
            'revokedCertificates'
        ));
    }
    public function teacherNotifications()
    {
        $teacher = User::find(session('user_id'));

        $notifications = Notification::where('institute', $teacher->institute)
            ->whereIn('target', ['Teachers', 'Class'])
            ->latest()
            ->get();

        return view('teacher.teacher-notifications', compact('notifications'));
    }
   public function teacherProfile()
    {
        $teacher = User::find(session('user_id'));

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

            $userSession = UserSession::create([
                'user_type' => 'Student',
                'user_id' => $student->id,
                'login_time' => now(),
                'ip_address' => request()->ip(),
                'browser' => request()->userAgent(),
            ]);

            session([
                'tracking_session_id' => $userSession->id
            ]);

            if (!$student->profile_completed) {
                return redirect()->route('student.basic-details');
            }

            return redirect()->route('student.dashboard');
        }

        return redirect()->back()->with('error', 'Invalid student login details');
    }

    public function studentDashboard()
    {
        $student = Student::find(session('student_id'));

        $studentName = session('student_name');
        $studentCode = session('student_code');
        $studentId = session('student_id');

        $results = AssessmentResult::where('student_id', $studentId)
            ->latest()
            ->get();

        $badgeCount = $results->whereNotNull('badge')->count();

        $attemptedAssessmentIds = AssessmentResult::where('student_id', $studentId)
            ->pluck('assessment_id');

        $assignedClass = $this->studentClassName($student);

        $assessmentBaseQuery = Assessment::where('status', 1)
            ->where('institute', $student->institute)
            ->where('question_paper_status', 'Approved')
            ->whereNotNull('file_path')
            ->whereRaw(
                "REPLACE(TRIM(assigned_class), '  ', ' ') = ?",
                [$assignedClass]
            );

        $pendingAssessmentCount = (clone $assessmentBaseQuery)
            ->whereDate('assessment_date', '<=', today())
            ->whereNotIn('id', $attemptedAssessmentIds)
            ->count();

        $totalAssessmentCount = (clone $assessmentBaseQuery)
            ->count();

        $notifications = Notification::where('institute', $student->institute)
            ->whereIn('target', ['Students', 'Class'])
            ->latest()
            ->take(3)
            ->get();

        $upcomingAssessments = (clone $assessmentBaseQuery)
            ->whereDate('assessment_date', '>', today())
            ->whereNotIn('id', $attemptedAssessmentIds)
            ->orderBy('assessment_date')
            ->take(5)
            ->get();

        return view('student.student-dashboard', compact(
            'studentName',
            'studentCode',
            'results',
            'badgeCount',
            'notifications',
            'pendingAssessmentCount',
            'totalAssessmentCount',
            'upcomingAssessments',
        ));
    }

    public function startAssessmentSession($assessmentId)
    {
        $userType = session('student_id') ? 'Student' : 'Teacher';

        $userId = session('student_id') ?? session('user_id');

        if ($userType == 'Student' && !$this->studentCanAccessAssessment($assessmentId, $userId)) {
            abort(403, 'This assessment is not assigned to your class.');
        }

        if ($userType == 'Teacher') {
            $teacher = User::findOrFail($userId);

            if (!$this->teacherCanAccessAssessment($teacher, $assessmentId)) {
                abort(403, 'You can only start assessments created for your assigned classes.');
            }
        }

        $session = AssessmentSession::where('assessment_id', $assessmentId)
            ->where('user_id', $userId)
            ->where('user_type', $userType)
            ->where('status', 'Started')
            ->first();

        if (!$session) {

            $session = AssessmentSession::create([
                'assessment_id' => $assessmentId,
                'user_id' => $userId,
                'user_type' => $userType,
                'started_at' => now(),
                'status' => 'Started',
            ]);

        }

        session([
            'active_assessment_session_id' => $session->id,
            'active_assessment_id' => $assessmentId,
        ]);

        if ($userType == 'Student') {
            return redirect()
                ->route('student.assessment.take', $assessmentId)
                ->with('success', 'Assessment session started.');
        }

        return redirect()
            ->route('student.assessment', ['assessment_id' => $assessmentId])
            ->with('success', 'Assessment session started.');
    }

    public function recordAssessmentViolation($sessionId)
    {
        $session = AssessmentSession::findOrFail($sessionId);

        $session->increment('violation_count');

        $session->refresh();

        $session->update([
            'last_violation_at' => now(),
        ]);

        if ($session->violation_count >= 3) {

            $session->update([
                'status' => 'AutoSubmitted',
                'submitted_at' => now(),
            ]);

            return response()->json([
                'success' => true,
                'violation_count' => $session->violation_count,
                'auto_submit' => true,
            ]);
        }

        return response()->json([
            'success' => true,
            'violation_count' => $session->violation_count,
            'auto_submit' => false,
        ]);
    }

    public function submitAssessmentSession($sessionId)
    {
        $session = AssessmentSession::findOrFail($sessionId);

        $session->update([
            'status' => 'Submitted',
            'submitted_at' => now(),
        ]);

        session()->forget([
            'active_assessment_session_id',
            'active_assessment_id',
        ]);

        return redirect()->back()
            ->with('success', 'Assessment submitted successfully.');
    }
    private function teacherAssignedClassNames(User $teacher)
    {
        return SchoolClass::where('institute', $teacher->institute)
            ->where('class_teacher', $teacher->name)
            ->get()
            ->map(function ($class) {
                return trim($class->class_name . ' ' . $class->section);
            })
            ->values();
    }

    private function teacherAssignedStudentsQuery(User $teacher)
    {
        $classes = SchoolClass::where('institute', $teacher->institute)
            ->where('class_teacher', $teacher->name)
            ->get(['class_name', 'section']);

        $query = Student::where('institute', $teacher->institute);

        if ($classes->isEmpty()) {
            return $query->whereRaw('1 = 0');
        }

        return $query->where(function ($studentQuery) use ($classes) {
            foreach ($classes as $class) {
                $studentQuery->orWhere(function ($q) use ($class) {
                    $q->where('class', $class->class_name)
                        ->where('section', $class->section);
                });
            }
        });
    }

    private function teacherAssignedStudentIds(User $teacher)
    {
        return $this->teacherAssignedStudentsQuery($teacher)->pluck('id');
    }

    private function teacherCanAccessStudent(User $teacher, Student $student)
    {
        return $this->teacherAssignedStudentsQuery($teacher)
            ->where('id', $student->id)
            ->exists();
    }

    private function teacherCanAccessAssessment(User $teacher, $assessmentId)
    {
        return Assessment::where('id', $assessmentId)
            ->where('institute', $teacher->institute)
            ->where('teacher_id', $teacher->id)
            ->whereIn('assigned_class', $this->teacherAssignedClassNames($teacher))
            ->exists();
    }

    public function studentTakeAssessment(Request $request)
    {
        $studentId = session('student_id');

        $student = Student::findOrFail($studentId);
        $assignedClass = $this->studentClassName($student);

        $availableAssessments = Assessment::where('status', 1)
            ->where('institute', $student->institute)
            ->where('question_paper_status', 'Approved')
            ->whereNotNull('file_path')
            ->whereDate('assessment_date', '<=', today())
            ->whereRaw(
                "REPLACE(TRIM(assigned_class), '  ', ' ') = ?",
                [$assignedClass]
            )
            ->pluck('id');

        $attemptedAssessmentIds = AssessmentResult::where(
            'student_id',
            $studentId
        )->pluck('assessment_id');

        $assessments = Assessment::whereIn('id', $availableAssessments)
            ->whereNotIn('id', $attemptedAssessmentIds)
            ->get();

        $selectedAssessment = null;

        if ($request->assessment_id) {

            $alreadyAttempted = AssessmentResult::where(
                'student_id',
                $studentId
            )
            ->where(
                'assessment_id',
                $request->assessment_id
            )
            ->exists();

            if ($alreadyAttempted) {

                return redirect()
                    ->route('student.history')
                    ->with(
                        'error',
                        'Assessment already completed.'
                    );
            }

            if (
                !in_array(
                    $request->assessment_id,
                    $availableAssessments->toArray()
                )
            ) {

                return redirect()
                    ->route('student.assessment')
                    ->with(
                        'error',
                        'This assessment is not available for your class yet.'
                    );
            }

            $selectedAssessment = Assessment::find(
                $request->assessment_id
            );

            if (!$selectedAssessment) {

                return redirect()
                    ->route('student.assessment')
                    ->with(
                        'error',
                        'Assessment not found.'
                    );
            }


        }

        return view(
            'student.student-assessment',
            compact(
                'assessments',
                'selectedAssessment'
            )
        );
    }

    public function studentAssessmentTaking(Assessment $assessment)
    {
        $studentId = session('student_id');

        if (!$studentId || !$this->studentCanAccessAssessment($assessment->id, $studentId)) {
            abort(403, 'This assessment is not assigned to your class.');
        }

        if ((int) session('active_assessment_id') !== (int) $assessment->id) {
            return redirect()
                ->route('student.assessment', ['assessment_id' => $assessment->id])
                ->with('error', 'Please start the assessment before opening the assessment page.');
        }

        $sessionId = session('active_assessment_session_id');
        $assessmentSession = AssessmentSession::where('id', $sessionId)
            ->where('assessment_id', $assessment->id)
            ->where('user_id', $studentId)
            ->where('user_type', 'Student')
            ->whereIn('status', ['Started', 'AutoSubmitted'])
            ->first();

        if (!$assessmentSession) {
            session()->forget([
                'active_assessment_session_id',
                'active_assessment_id',
            ]);

            return redirect()
                ->route('student.assessment')
                ->with('error', 'Assessment session is no longer active.');
        }

        $alreadySubmitted = AssessmentResult::where('student_id', $studentId)
            ->where('assessment_id', $assessment->id)
            ->exists();

        if ($alreadySubmitted) {
            session()->forget([
                'active_assessment_session_id',
                'active_assessment_id',
            ]);

            return redirect()
                ->route('student.history')
                ->with('error', 'Assessment already completed.');
        }

        return view('student.student-assessment-taking', compact(
            'assessment',
            'assessmentSession'
        ));
    }

    public function studentProfile()
    {
        $student = Student::find(session('student_id'));

        if (!$student) {
            return redirect()->route('student.login');
        }

        $badgeCount = AssessmentResult::where('student_id', $student->id)
            ->whereNotNull('badge')
            ->count();

        return view('student.student-profile', compact(
            'student',
            'badgeCount'
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

        $results = AssessmentResult::with('assessment')
            ->where('student_id', $studentId)
            ->whereNotNull('badge')
            ->latest()
            ->get();

        $goldCount = $results->where('badge', 'Gold')->count();

        $silverCount = $results->where('badge', 'Silver')->count();

        $bronzeCount = $results->where('badge', 'Bronze')->count();

        $certificateEligible = $results
            ->filter(function ($result) {
                return $result->assessment &&
                    $result->assessment->assessment_category == 'Annual' &&
                    $result->status == 'Completed' &&
                    !is_null($result->evaluated_at);
            })
            ->isNotEmpty();

        $certificates = Certificate::with('course')
            ->where('student_id', $studentId)
            ->latest()
            ->get();

        $uploadedAchievements = StudentAchievement::where(
            'student_id',
            $studentId
        )->latest()->get();

        return view('student.student-badges', compact(

            'results',

            'goldCount',

            'silverCount',

            'bronzeCount',

            'certificateEligible',

            'certificates',

            'uploadedAchievements'

        ));
    }
    public function studentNotifications()
    {
        $student = Student::find(session('student_id'));

        $notifications = Notification::where('institute', $student->institute)
            ->whereIn('target', ['Students', 'Class'])
            ->latest()
            ->get();

        return view('student.student-notifications', compact('notifications'));
    }
    public function disqualifyResult($id)
    {
        $result = AssessmentResult::with(['student', 'assessment'])->findOrFail($id);

        if (session('user_role') == 'Teacher') {
            $teacher = User::findOrFail(session('user_id'));

            if (
                !$result->student ||
                !$this->teacherCanAccessStudent($teacher, $result->student) ||
                !$result->assessment ||
                $result->assessment->teacher_id != $teacher->id
            ) {
                abort(403, 'You can only disqualify results for students assigned to your class.');
            }
        }

        if ($result->assessment && $result->assessment->assessment_category == 'Annual') {
            $this->deleteAnnualCertificatesForStudent($result->student_id);
        }

        $result->delete();

        return redirect()->back()->with('success', 'Student result disqualified successfully');
    }
    private function deleteAnnualCertificatesForStudent($studentId)
    {
        $certificates = Certificate::where('student_id', $studentId)
            ->where('certificate_type', 'Annual')
            ->get();

        if ($certificates->isEmpty()) {
            return;
        }

        $certificateIds = $certificates->pluck('id');
        $certificateCodes = $certificates->pluck('certificate_code')->filter();

        foreach ($certificates as $certificate) {
            foreach (['file_path', 'certificate_file', 'pdf_path', 'download_path'] as $field) {
                $path = $certificate->getAttribute($field);

                if (!$path) {
                    continue;
                }

                foreach (['public', 'local'] as $disk) {
                    if (Storage::disk($disk)->exists($path)) {
                        Storage::disk($disk)->delete($path);
                    }
                }
            }
        }

        CertificateVerificationLog::whereIn('certificate_id', $certificateIds)
            ->when($certificateCodes->isNotEmpty(), function ($query) use ($certificateCodes) {
                $query->orWhereIn('certificate_code', $certificateCodes);
            })
            ->delete();

        Certificate::whereIn('id', $certificateIds)->delete();
    }
    public function verifyCertificate()
    {
        return view('certificate.verify-certificate');
    }

    public function studentContent()
    {
        $student = Student::find(session('student_id'));

        $activeAssessmentSession = AssessmentSession::where('user_id', session('student_id'))
            ->where('user_type', 'Student')
            ->where('status', 'Started')
            ->first();

        if ($activeAssessmentSession) {
            return redirect()
                ->route('student.assessment', [
                    'assessment_id' => $activeAssessmentSession->assessment_id,
                ])
                ->with('error', 'Content is locked while your assessment is in progress.');
        }

        $course = $this->studentAssignedCourse($student);

        $contents = collect();

        if ($course) {
            $contents = Content::where('course_id', $course->id)
                ->where('status', 1)
                ->where('is_released', true)
                ->whereNotNull('student_file_path')
                ->orderBy('lesson_order')
                ->get();
        }

        $totalLessons = $contents->count();

        $completedLessons = LessonProgress::where('student_id', session('student_id'))
            ->whereIn('content_id', $contents->pluck('id'))
            ->where('is_completed', true)
            ->count();

        $progressPercentage = $totalLessons > 0
            ? round(($completedLessons / $totalLessons) * 100)
            : 0;

        return view('student.student-content', compact(
            'contents',
            'totalLessons',
            'completedLessons',
            'progressPercentage'
        ));
    }
    public function verifyCertificateSubmit(Request $request)
    {
        $request->validate([
            'verifier_name' => 'required|string|max:255',
            'verifier_email' => 'required|email|max:255',
            'verification_reason' => 'required|string|max:1000',
            'certificate_code' => 'required|string|max:255',
        ]);

        $certificate = Certificate::where(
            'certificate_code',
            $request->certificate_code
        )->first();

        $revoked = false;
        $inactive = false;
        $verificationStatus = 'failed';

        if ($certificate) {
            if ($certificate->status == 'Revoked') {
                $revoked = true;
                $verificationStatus = 'revoked';
            } elseif ($certificate->status == 'Issued') {
                $verificationStatus = 'verified';
            } else {
                $inactive = true;
                $verificationStatus = 'pending_approval';
            }
        }

        CertificateVerificationLog::create([
            'verifier_name' => $request->verifier_name,
            'verifier_email' => $request->verifier_email,
            'verification_reason' => $request->verification_reason,
            'certificate_code' => $request->certificate_code,
            'verification_status' => $verificationStatus,
            'certificate_id' => $certificate ? $certificate->id : null,
            'ip_address' => $request->ip(),
        ]);

        return view('certificate.verify-certificate', compact(
            'certificate',
            'revoked',
            'inactive'
        ));
    }

    public function reissueCertificate($id)
    {
        $certificate = Certificate::with('student')->findOrFail($id);

        if (
            session('user_role') == 'InstituteAdmin' &&
            $certificate->student->institute != session('user_institute')
        ) {
            abort(403, 'Unauthorized action.');
        }

        $finalScore = $certificate->final_score ?? $certificate->badge_count ?? 0;

        if ($finalScore < 40) {
            return redirect()->back()
                ->with('error', 'Students below 40% are not eligible for certificate reissue.');
        }

        $certificate->update([
            'status' => 'Issued',
            'issued_date' => now(),
            'final_score' => $finalScore,
            'final_grade' => $this->calculateCertificateGrade($finalScore),
            'final_classification' => $this->calculateCertificateClassification($finalScore),
        ]);

        return redirect()->back()
            ->with('success', 'Certificate reissued successfully.');
    }

    private function calculateCertificateGrade($percentage)
    {
        if ($percentage >= 90) {
            return 'A+';
        }

        if ($percentage >= 80) {
            return 'A';
        }

        if ($percentage >= 70) {
            return 'B+';
        }

        if ($percentage >= 60) {
            return 'B';
        }

        if ($percentage >= 50) {
            return 'C+';
        }

        if ($percentage >= 40) {
            return 'C';
        }

        return 'F';
    }

    private function calculateCertificateClassification($percentage)
    {
        if ($percentage >= 90) {
            return 'Outstanding';
        }

        if ($percentage >= 80) {
            return 'Distinction';
        }

        if ($percentage >= 70) {
            return 'First Class';
        }

        if ($percentage >= 60) {
            return 'Second Class';
        }

        if ($percentage >= 50) {
            return 'Pass';
        }

        if ($percentage >= 40) {
            return 'Satisfactory';
        }

        return 'Fail';
    }

    public function exportResults(Request $request)
    {
        $search = $request->search;
        $badge = $request->badge;
        $status = $request->status;
        $sort = $request->sort;

        $results = AssessmentResult::with(['student', 'assessment'])
            ->when(session('user_role') == 'Teacher', function ($query) {
                $teacher = User::findOrFail(session('user_id'));

                $query->whereIn('student_id', $this->teacherAssignedStudentIds($teacher))
                    ->whereHas('assessment', function ($q) use ($teacher) {
                        $q->where('teacher_id', $teacher->id);
                    });
            })
            ->when($search, function ($query, $search) {
                $query->where(function ($searchQuery) use ($search) {
                    $searchQuery->whereHas('student', function ($q) use ($search) {
                        $q->where('name', 'like', "%{$search}%")
                            ->orWhere('student_id', 'like', "%{$search}%");
                    })
                    ->orWhereHas('assessment', function ($q) use ($search) {
                        $q->where('assessment_title', 'like', "%{$search}%");
                    })
                    ->orWhere('badge', 'like', "%{$search}%");
                });
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
            ->when($sort == 'oldest', function ($query) {
                $query->oldest();
            })
            ->when(!$sort || $sort == 'latest', function ($query) {
                $query->latest();
            })
            ->get();

        $filename = 'assessment_results.csv';

        $headers = [
            "Content-Type" => "text/csv",
            "Content-Disposition" => "attachment; filename=$filename",
        ];

        $callback = function () use ($results) {
            $file = fopen('php://output', 'w');

            fputcsv($file, [
                'Student Name',
                'Student ID',
                'Assessment',
                'Category',
                'Assessment Date',
                'Score',
                'Total Marks',
                'Percentage',
                'Badge',
                'Status',
                'Date',
            ]);

            foreach ($results as $result) {
                fputcsv($file, [
                    $result->student->student_name ?? 'Student Deleted',
                    $result->student->student_id ?? 'N/A',
                    $result->assessment->assessment_title ?? 'Assessment Deleted',
                    $result->assessment->assessment_category ?? 'N/A',
                    $result->assessment && $result->assessment->assessment_date
                        ? $result->assessment->assessment_date
                        : 'N/A',
                    $result->score,
                    $result->total_marks,
                    $result->percentage . '%',
                    $result->badge ?? 'No Badge',
                    $result->status,
                    $result->created_at->format('d M Y h:i A'),
                ]);
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }
    public function adminAnalytics()
    {
        $isInstituteAdmin = session('user_role') == 'InstituteAdmin';
        $institute = session('user_institute');

        $studentQuery = Student::query()
            ->when($isInstituteAdmin, function ($query) use ($institute) {
                $query->where('institute', $institute);
            });

        $assessmentQuery = Assessment::query()
            ->when($isInstituteAdmin, function ($query) use ($institute) {
                $query->where('institute', $institute);
            });

        $resultQuery = AssessmentResult::with(['student', 'assessment'])
            ->when($isInstituteAdmin, function ($query) use ($institute) {
                $query->whereHas('student', function ($q) use ($institute) {
                    $q->where('institute', $institute);
                });
            });

        $certificateQuery = Certificate::with('student')
            ->when($isInstituteAdmin, function ($query) use ($institute) {
                $query->whereHas('student', function ($q) use ($institute) {
                    $q->where('institute', $institute);
                });
            });

        $studentCount = $studentQuery->count();
        $assessmentCount = $assessmentQuery->count();
        $certificateCount = $certificateQuery->count();

        $averageScore = (clone $resultQuery)->avg('percentage') ?? 0;

        $goldCount = (clone $resultQuery)->where('badge', 'Gold')->count();
        $silverCount = (clone $resultQuery)->where('badge', 'Silver')->count();
        $bronzeCount = (clone $resultQuery)->where('badge', 'Bronze')->count();

        $recentResults = (clone $resultQuery)
            ->latest()
            ->take(5)
            ->get();

        $attemptedCount = (clone $resultQuery)
            ->distinct('student_id')
            ->count('student_id');

        $passedCount = (clone $resultQuery)
            ->where('percentage', '>=', 50)
            ->count();

        $failedCount = (clone $resultQuery)
            ->where('percentage', '<', 50)
            ->count();

        $notAttemptedCount = max($studentCount - $attemptedCount, 0);

        $topPerformers = (clone $resultQuery)
            ->orderByDesc('percentage')
            ->take(5)
            ->get();

        $assessmentAverages = (clone $resultQuery)
            ->selectRaw('assessment_id, AVG(percentage) as average_percentage')
            ->groupBy('assessment_id')
            ->take(5)
            ->get();

        return view('analytics', compact(
            'studentCount',
            'assessmentCount',
            'certificateCount',
            'averageScore',
            'goldCount',
            'silverCount',
            'bronzeCount',
            'attemptedCount',
            'notAttemptedCount',
            'passedCount',
            'failedCount',
            'topPerformers',
            'recentResults',
            'assessmentAverages'
        ));
    }
    public function activityMonitoring(Request $request)
    {
        $date = $request->date;

        $instituteAdminSessions = UserSession::where('user_type', 'InstituteAdmin')
            ->when($date, function ($query, $date) {
                $query->whereDate('login_time', $date);
            })
            ->latest()
            ->take(20)
            ->get();

        $teacherSessions = UserSession::where('user_type', 'Teacher')
            ->when($date, function ($query, $date) {
                $query->whereDate('login_time', $date);
            })
            ->latest()
            ->take(20)
            ->get();

        $studentSessions = UserSession::where('user_type', 'Student')
            ->when($date, function ($query, $date) {
                $query->whereDate('login_time', $date);
            })
            ->latest()
            ->take(20)
            ->get();

        $instituteAdminLogs = UserActivityLog::where('user_type', 'InstituteAdmin')
            ->when($date, function ($query, $date) {
                $query->whereDate('started_at', $date);
            })
            ->latest()
            ->take(30)
            ->get();

        $teacherLogs = UserActivityLog::where('user_type', 'Teacher')
            ->when($date, function ($query, $date) {
                $query->whereDate('started_at', $date);
            })
            ->latest()
            ->take(30)
            ->get();

        $studentLogs = UserActivityLog::where('user_type', 'Student')
            ->when($date, function ($query, $date) {
                $query->whereDate('started_at', $date);
            })
            ->latest()
            ->take(30)
            ->get();

        $activeInstituteAdmins = UserSession::where('user_type', 'InstituteAdmin')
            ->distinct('user_id')
            ->count('user_id');

        $activeTeachers = UserSession::where('user_type', 'Teacher')
            ->distinct('user_id')
            ->count('user_id');

        $activeStudents = UserSession::where('user_type', 'Student')
            ->distinct('user_id')
            ->count('user_id');

        $onlineUsers = UserSession::whereNull('logout_time')->count();

        $averageSessionDuration = UserSession::avg('total_duration_seconds');

        $mostVisitedSection = UserActivityLog::selectRaw('section_name, COUNT(*) as total')
            ->whereNotNull('section_name')
            ->groupBy('section_name')
            ->orderByDesc('total')
            ->first();

        $sectionDurations = UserActivityLog::selectRaw('section_name, SUM(duration_seconds) as total_duration')
            ->whereNotNull('section_name')
            ->groupBy('section_name')
            ->orderByDesc('total_duration')
            ->get();
        
        $assessmentSessions = AssessmentSession::with(['assessment','student','teacher',])
            ->latest()
            ->take(30)
            ->get();

        return view('activity-monitoring', compact(
            'instituteAdminSessions',
            'teacherSessions',
            'studentSessions',
            'instituteAdminLogs',
            'teacherLogs',
            'studentLogs',
            'activeInstituteAdmins',
            'activeTeachers',
            'activeStudents',
            'onlineUsers',
            'averageSessionDuration',
            'mostVisitedSection',
            'sectionDurations',
            'assessmentSessions',
        ));
    }
    public function exportActivityReport(Request $request)
    {
        $userType = $request->user_type;
        $date = $request->date;

        $logs = UserActivityLog::when($userType, function ($query, $userType) {
                $query->where('user_type', $userType);
            })
            ->when($date, function ($query, $date) {
                $query->whereDate('started_at', $date);
            })
            ->latest()
            ->get();

        $response = new StreamedResponse(function () use ($logs) {

            $handle = fopen('php://output', 'w');

            fputcsv($handle, [
                'User Type',
                'User Name',
                'Section',
                'Route',
                'URL',
                'Visited At',
                'Time Spent'
            ]);

            foreach ($logs as $log) {

                $userName = 'Unknown User';

                if ($log->user_type == 'Teacher') {
                    $userName = $log->teacher->name ?? 'STEM Engineer Deleted';
                }

                elseif ($log->user_type == 'Student') {
                    $userName = $log->student->name ?? 'Student Deleted';
                }

                fputcsv($handle, [
                    $log->user_type,
                    $userName,
                    $log->section_name,
                    $log->route_name,
                    $log->page_url,
                    $log->started_at,
                    gmdate('H:i:s', $log->duration_seconds ?? 0),
                ]);
            }

            fclose($handle);
        });

        $fileName = 'activity-report-' . now()->format('Y-m-d-H-i-s') . '.csv';

        $response->headers->set(
            'Content-Type',
            'text/csv'
        );

        $response->headers->set(
            'Content-Disposition',
            "attachment; filename={$fileName}"
        );

        return $response;
    }
    public function completeLesson($id)
    {
        $studentId = session('student_id');
        $student = Student::findOrFail($studentId);
        $course = $this->studentAssignedCourse($student);
        $content = Content::where('id', $id)
            ->where('status', 1)
            ->where('is_released', true)
            ->whereNotNull('student_file_path')
            ->first();

        if (!$course || !$content || $content->course_id != $course->id) {
            abort(403, 'This lesson is not assigned to your class.');
        }

        LessonProgress::updateOrCreate(

            [
                'student_id' => $studentId,
                'content_id' => $id,
            ],

            [
                'is_completed' => true,
                'completed_at' => now(),
            ]

        );

        return redirect()->back()
            ->with('success', 'Lesson marked as completed');
    }

    private function studentAssignedCourse(Student $student)
    {
        $assignedClass = trim($student->class . ' ' . $student->section);

        return Course::where('institute', $student->institute)
            ->whereRaw(
                "REPLACE(TRIM(assigned_class), '  ', ' ') = ?",
                [$assignedClass]
            )
            ->first();
    }

    private function studentClassName(Student $student)
    {
        return preg_replace('/\s+/', ' ', trim($student->class . ' ' . $student->section));
    }

    private function studentCanAccessAssessment($assessmentId, $studentId)
    {
        $student = Student::find($studentId);

        if (!$student) {
            return false;
        }

        $assessment = Assessment::query()
            ->where('id', $assessmentId)
            ->where('institute', $student->institute)
            ->where('status', 1)
            ->where('question_paper_status', 'Approved')
            ->whereNotNull('file_path')
            ->first();

        if (
            !$assessment ||
            (
                $assessment->assessment_date &&
                $assessment->assessment_date > today()->toDateString()
            )
        ) {
            return false;
        }

        return $this->studentClassName($student) ==
            preg_replace('/\s+/', ' ', trim((string) $assessment->assigned_class));
    }

    public function assessmentMonitoring()
    {
        $sessions = AssessmentSession::with([
                'assessment',
                'student',
                'teacher',
            ])
            ->when(session('user_role') == 'InstituteAdmin', function ($query) {

                $query->whereHas('assessment', function ($q) {

                    $q->where('institute', session('user_institute'));

                });

            })
            ->latest()
            ->get();

        return view('assessment-monitoring', compact('sessions'));
    }

    public function startClassSession($timetableId)
    {
        $teacher = User::findOrFail(session('user_id'));

        $timetable = ClassTimetable::with(['schoolClass', 'content'])
            ->findOrFail($timetableId);

        if (
            !$timetable->schoolClass ||
            $timetable->schoolClass->class_teacher != $teacher->name ||
            $timetable->schoolClass->institute != $teacher->institute
        ) {
            abort(403, 'This class session is assigned to another STEM Engineer.');
        }

        $existingSession = ClassContentSession::where('timetable_id', $timetable->id)
            ->where('stem_engineer_id', session('user_id'))
            ->where('status', 'Started')
            ->first();

        if ($existingSession) {
            return redirect()->back()
                ->with('error', 'A session is already running for this timetable.');
        }

        ClassContentSession::create([
            'timetable_id' => $timetable->id,
            'class_id' => $timetable->class_id,
            'content_id' => $timetable->content_id,
            'stem_engineer_id' => session('user_id'),
            'started_at' => now(),
            'status' => 'Started',
        ]);

        $timetable->update([
            'status' => 'Started',
        ]);

        return redirect()
            ->route('teacher.session.content', $timetable->content_id)
            ->with('success', 'Class session started successfully.');
    }

    public function teacherSessionContent($contentId)
    {
        $teacher = User::findOrFail(session('user_id'));

        $content = Content::findOrFail($contentId);

        $hasAssignedSession = ClassTimetable::where('content_id', $content->id)
            ->whereHas('schoolClass', function ($query) use ($teacher) {
                $query->where('institute', $teacher->institute)
                    ->where('class_teacher', $teacher->name);
            })
            ->exists();

        if (!$hasAssignedSession) {
            abort(403, 'This content session is assigned to another STEM Engineer.');
        }

        return view('teacher.session-content-viewer', compact('content'));
    }


    public function endClassSession($sessionId)
    {
        $teacher = User::findOrFail(session('user_id'));

        $session = ClassContentSession::with('schoolClass')->findOrFail($sessionId);

        if (
            $session->stem_engineer_id != $teacher->id ||
            !$session->schoolClass ||
            $session->schoolClass->class_teacher != $teacher->name ||
            $session->schoolClass->institute != $teacher->institute
        ) {
            abort(403, 'This class session is assigned to another STEM Engineer.');
        }

        if ($session->status == 'Completed') {

            return redirect()->back()
                ->with(
                    'error',
                    'Session already completed.'
                );
        }

        $endedAt = now();

        $duration = strtotime($endedAt) -
                    strtotime($session->started_at);

        $session->update([

            'ended_at' => $endedAt,

            'duration_seconds' => $duration,

            'status' => 'Completed',

        ]);

        ClassTimetable::where(
                'class_id',
                $session->class_id
            )
            ->where(
                'content_id',
                $session->content_id
            )
            ->whereDate(
                'session_date',
                today()
            )
            ->update([
                'status' => 'Completed'
            ]);

        return redirect()->back()
            ->with(
                'success',
                'Class session ended successfully.'
            );

    }

    public function classSessionReport()
    {
        $sessions = ClassContentSession::with([
                'schoolClass',
                'content',
                'stemEngineer',
            ])
            ->when(session('user_role') == 'InstituteAdmin', function ($query) {
                $query->whereHas('schoolClass', function ($q) {
                    $q->where('institute', session('user_institute'));
                });
            })
            ->latest()
            ->get();

        return view('class-session-report', compact('sessions'));
    }

    public function assessmentReviewMonitoring()
    {
        $results = AssessmentResult::with([
            'student',
            'assessment'
        ])
        ->when(session('user_role') == 'InstituteAdmin', function ($query) {

            $query->whereHas('student', function ($q) {

                $q->where(
                    'institute',
                    session('user_institute')
                );

            });

        })
        ->latest()
        ->get();

        return view(
            'assessment-review-monitoring',
            compact('results')
        );
    }

    public function downloadStudentCertificate()
    {
        $student = Student::findOrFail(session('student_id'));

        $certificate = Certificate::where('student_id', $student->id)
            ->where('status', 'Issued')
            ->latest()
            ->firstOrFail();

        $pdf = Pdf::loadView('student.student-certificate', [
            'certificate' => $certificate,
            'student' => $student,
        ])->setPaper('a4', 'landscape');

        return $pdf->stream('certificate-' . $certificate->certificate_code . '.pdf');
    }

    public function coordinatorLogin()
    {
        return view('coordinator.login');
    }

    public function coordinatorLoginSubmit(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required',
        ]);

        $user = User::where('email', $request->email)
            ->where('role', 'Coordinator')
            ->where('status', 1)
            ->first();

        if ($user && Hash::check($request->password, $user->password)) {

            session([
                'user_id' => $user->id,
                'user_name' => $user->name,
                'user_role' => $user->role,
                'user_institute' => $user->institute,
            ]);

            return redirect()->route('coordinator.dashboard');
        }

        return redirect()
            ->back()
            ->with('error', 'Invalid coordinator login credentials.');
    }
}
