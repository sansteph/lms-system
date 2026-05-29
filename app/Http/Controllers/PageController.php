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
use App\Models\Certificate;
use App\Models\UserSession;
use App\Models\UserActivityLog;
use Symfony\Component\HttpFoundation\StreamedResponse;
use App\Models\StudentAchievement;
use App\Models\LessonProgress;  
use App\Models\AccessRequest;
use App\Models\CertificateVerificationLog;

class PageController extends Controller
{
    public function home()
    {
        return view('home');
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

    public function adminCertificates()
    {
        $certificates = Certificate::with('student')
            ->latest()
            ->get();

        return view(
            'certificates',
            compact('certificates')
        );
    }

    public function revokeCertificate($id)
    {
        $certificate = Certificate::findOrFail($id);

        $certificate->update([
            'status' => 'Revoked'
        ]);

        return redirect()->back()->with('success', 'Certificate revoked successfully.');
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
            'totalAssessmentCount',
        ));
    }
    public function studentTakeAssessment(Request $request)
    {
        $studentId = session('student_id');

        $completedLessons = LessonProgress::where(
            'student_id',
            $studentId
        )
        ->where('is_completed', true)
        ->pluck('content_id');

        $availableAssessments = Assessment::whereIn(
            'content_id',
            $completedLessons
        )
        ->where('status', 1)
        ->pluck('id');

        $attemptedAssessmentIds = AssessmentResult::where(
            'student_id',
            $studentId
        )->pluck('assessment_id');

        $assessments = Assessment::with('questions')
            ->whereIn('id', $availableAssessments)
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
                        'Complete the lesson before accessing this assessment.'
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

            $questions = AssessmentQuestion::where(
                'assessment_id',
                $request->assessment_id
            )->get();

            if ($questions->count() == 0) {

                return redirect()
                    ->route('student.assessment')
                    ->with(
                        'error',
                        'This assessment is not ready yet.'
                    );
            }
        }

        return view(
            'student.student-assessment',
            compact(
                'assessments',
                'selectedAssessment',
                'questions'
            )
        );
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

        $certificate = Certificate::where('student_id', $studentId)
            ->latest()
            ->first();

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

            'certificate',

            'uploadedAchievements'

        ));
    }
    public function studentNotifications()
    {
        $notifications = Notification::where('target', 'Student')
            ->latest()
            ->get();

        return view(
            'student.student-notifications',
            compact('notifications')
        );
    }
    public function disqualifyResult($id)
    {
        $result = AssessmentResult::findOrFail($id);

        $result->delete();

        return redirect()->back()->with('success', 'Student result disqualified successfully');
    }
    public function studentCertificate()
    {
        $student = Student::find(session('student_id'));

        $certificate = Certificate::where('student_id', session('student_id'))->first();

        if (!$certificate) {
            return redirect()->route('student.badges')
                ->with('error', 'Certificate is not available yet.');
        }
        if ($certificate->status == 'Revoked') {
            return redirect()
                ->route('student.badges')
                ->with('error', 'Your certificate has been revoked.');
        }

        return view('student.student-certificate', compact('student', 'certificate'));
    }
    public function verifyCertificate()
    {
        return view('certificate.verify-certificate');
    }

    public function studentContent()
    {
        $contents = Content::orderBy('lesson_order')->get();

        $totalLessons = $contents->count();

        $completedLessons = LessonProgress::where(
            'student_id',
            session('student_id')
        )
        ->where('is_completed', true)
        ->count();

        $progressPercentage = 0;

        if ($totalLessons > 0) {

            $progressPercentage = round(
                ($completedLessons / $totalLessons) * 100
            );
        }

        return view(
            'student.student-content',
            compact(
                'contents',
                'totalLessons',
                'completedLessons',
                'progressPercentage'
            )
        );
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
        $verificationStatus = 'failed';

        if ($certificate) {
            if ($certificate->status == 'Revoked') {
                $revoked = true;
                $verificationStatus = 'revoked';
            } else {
                $verificationStatus = 'verified';
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
            'revoked'
        ));
    }

    public function reissueCertificate($id)
    {
        $certificate = Certificate::findOrFail($id);

        $certificate->update([
            'status' => 'Issued',
            'issued_date' => now(),
        ]);

        return redirect()->back()
            ->with('success', 'Certificate reissued successfully.');
    }

    public function exportResults(Request $request)
    {
        $search = $request->search;
        $badge = $request->badge;
        $status = $request->status;
        $sort = $request->sort;

        $results = AssessmentResult::with(['student', 'assessment'])
            ->when($search, function ($query, $search) {
                $query->whereHas('student', function ($q) use ($search) {
                    $q->where('student_name', 'like', "%{$search}%")
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
        $studentCount = Student::count();
        $assessmentCount = Assessment::count();
        $certificateCount = Certificate::count();

        $averageScore = AssessmentResult::avg('percentage') ?? 0;

        $goldCount = AssessmentResult::where('badge', 'Gold')->count();
        $silverCount = AssessmentResult::where('badge', 'Silver')->count();
        $bronzeCount = AssessmentResult::where('badge', 'Bronze')->count();

        $recentResults = AssessmentResult::with(['student', 'assessment'])
            ->latest()
            ->take(5)
            ->get();

        $attemptedCount = AssessmentResult::distinct('student_id')->count('student_id');
        $passedCount = AssessmentResult::where('percentage', '>=', 50)->count();
        $failedCount = AssessmentResult::where('percentage', '<', 50)->count();
        $notAttemptedCount = max($studentCount - $attemptedCount, 0);

        $topPerformers = AssessmentResult::with('student')
            ->orderByDesc('percentage')
            ->take(5)
            ->get();

        $assessmentAverages = AssessmentResult::with('assessment')
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
            'assessmentAverages',
        ));
    }
    public function activityMonitoring(Request $request)
    {
        $userType = $request->user_type;
        $date = $request->date;

        $sessions = UserSession::when($userType, function ($query, $userType) {
                $query->where('user_type', $userType);
            })
            ->when($date, function ($query, $date) {
                $query->whereDate('login_time', $date);
            })
            ->latest()
            ->take(20)
            ->get();

        $activityLogs = UserActivityLog::when($userType, function ($query, $userType) {
                $query->where('user_type', $userType);
            })
            ->when($date, function ($query, $date) {
                $query->whereDate('started_at', $date);
            })
            ->latest()
            ->take(50)
            ->get();

        $activeTeachers = UserSession::where('user_type', 'Teacher')
            ->distinct('user_id')
            ->count('user_id');

        $activeStudents = UserSession::where('user_type', 'Student')
            ->distinct('user_id')
            ->count('user_id');

        $onlineUsers = UserSession::when($userType, function ($query, $userType) {
                $query->where('user_type', $userType);
            })
            ->when($date, function ($query, $date) {
                $query->whereDate('login_time', $date);
            })
            ->whereNull('logout_time')
            ->count();

        $mostVisitedSection = UserActivityLog::when($userType, function ($query, $userType) {
                $query->where('user_type', $userType);
            })
            ->when($date, function ($query, $date) {
                $query->whereDate('started_at', $date);
            })
            ->selectRaw('section_name, COUNT(*) as total')
            ->whereNotNull('section_name')
            ->groupBy('section_name')
            ->orderByDesc('total')
            ->first();

        $averageSessionDuration = UserSession::when($userType, function ($query, $userType) {
                $query->where('user_type', $userType);
            })
            ->when($date, function ($query, $date) {
                $query->whereDate('login_time', $date);
            })
            ->avg('total_duration_seconds');

        $sectionDurations = UserActivityLog::when($userType, function ($query, $userType) {
                $query->where('user_type', $userType);
            })
            ->when($date, function ($query, $date) {
                $query->whereDate('started_at', $date);
            })
            ->selectRaw('section_name, SUM(duration_seconds) as total_duration')
            ->whereNotNull('section_name')
            ->groupBy('section_name')
            ->orderByDesc('total_duration')
            ->get();

        return view('activity-monitoring', compact(
            'sessions',
            'activityLogs',
            'activeTeachers',
            'activeStudents',
            'onlineUsers',
            'mostVisitedSection',
            'averageSessionDuration',
            'sectionDurations'
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
                    $userName = $log->teacher->name ?? 'Teacher Deleted';
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
}
