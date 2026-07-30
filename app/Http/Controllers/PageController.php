<?php

namespace App\Http\Controllers;
use App\Models\Student;
use Illuminate\Http\Request;
use App\Models\Content;
use App\Models\Assessment;
use App\Models\SchoolClass;
use Illuminate\Support\Facades\Hash;
use App\Models\AssessmentResult;
use App\Models\Certificate;
use App\Models\UserSession;
use App\Models\UserActivityLog;
use App\Models\StudentAchievement;
use App\Models\LessonProgress;  
use App\Models\CertificateVerificationLog;
use App\Models\AssessmentSession;
use App\Models\User;
use App\Models\Course;
use App\Models\ClassContentSession;
use App\Models\TeachingPlan;
use App\Models\TeachingPlanItem;
use App\Models\AiContentSummary;
use App\Models\AiQuiz;
use App\Models\AiQuizAnswer;
use App\Models\AiQuizAttempt;
use App\Models\AiQuizQuestion;
use App\Services\TeachingPlanReleaseService;
use App\Services\Ai\GeminiAiService;
use App\Support\BuildsInstituteSectionPager;
use Barryvdh\DomPDF\Facade\Pdf;
use App\Models\ClassTimetable;
use App\Models\Institute;
use App\Models\IndependentLearner;
use App\Models\TeacherAchievement;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use App\Support\DeletesAssessments;
use App\Support\SyncsCommunityPosts;




class PageController extends Controller
{
    use BuildsInstituteSectionPager, DeletesAssessments, SyncsCommunityPosts;

    private const AI_TEACHER_ATTEMPT_TYPE = 'teacher_prep';
    private const AI_STUDENT_ATTEMPT_TYPE = 'student';

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

    public function adminManagementHub()
    {
        $items = [
            [
                'title' => 'STEM Engineer Management',
                'description' => 'Add, edit, and manage STEM Engineer accounts.',
                'icon' => 'fa-user-gear',
                'route' => route('users'),
            ],
            [
                'title' => 'Student Management',
                'description' => 'Add students, update profiles, and manage learner records.',
                'icon' => 'fa-user-graduate',
                'route' => route('students'),
            ],
            [
                'title' => 'Class Management',
                'description' => 'Create classes, sections, and academic year records.',
                'icon' => 'fa-chalkboard-user',
                'route' => route('classes'),
            ],
            [
                'title' => 'Course Management',
                'description' => 'Manage courses, uploaded content, templates, and lesson order.',
                'icon' => 'fa-book-open',
                'route' => route('courses'),
            ],
            [
                'title' => 'Teaching Plans',
                'description' => 'Create, deploy, sync, and release class-wise teaching plans.',
                'icon' => 'fa-calendar-days',
                'route' => route('teaching-plans'),
            ],
        ];

        if (session('user_role') == 'Admin') {
            $items[] = [
                'title' => 'Institute Management',
                'description' => 'Create and manage institutes directly from the admin panel.',
                'icon' => 'fa-building-columns',
                'route' => route('institutes'),
            ];
        }

        return view('admin-feature-hub', [
            'title' => 'Management',
            'description' => 'Choose the management area you want to work on.',
            'items' => $items,
        ]);
    }

    public function adminReportsHub()
    {
        return view('admin-feature-hub', [
            'title' => 'Reports',
            'description' => 'Open focused LMS reports without the old generic daily report view.',
            'items' => [
                [
                    'title' => 'Daily Session Report',
                    'description' => 'Review sessions conducted on one selected date.',
                    'icon' => 'fa-calendar-check',
                    'route' => route('admin.class-session.report.daily'),
                ],
                [
                    'title' => 'Weekly Session Report',
                    'description' => 'Review sessions across a selected week or date range.',
                    'icon' => 'fa-calendar-week',
                    'route' => route('admin.class-session.report.weekly'),
                ],
                [
                    'title' => 'Weekly Student AI Review',
                    'description' => 'Track weekly student AI review quiz completion, attempts, and pass rates.',
                    'icon' => 'fa-user-graduate',
                    'route' => route('reports.student-ai-review'),
                ],
                [
                    'title' => 'Weekly STEM Engineer Prep',
                    'description' => 'Track weekly STEM Engineer AI prep quiz readiness and clearance.',
                    'icon' => 'fa-clipboard-question',
                    'route' => route('reports.stem-engineer-prep'),
                ],
                [
                    'title' => 'Weekly Student Performance',
                    'description' => 'Review short-term student progress for a selected week.',
                    'icon' => 'fa-chart-line',
                    'route' => route('reports.student-performance.weekly'),
                ],
                [
                    'title' => 'Monthly Student Performance',
                    'description' => 'Review student progress and assessment outcomes by month.',
                    'icon' => 'fa-chart-simple',
                    'route' => route('reports.student-performance.monthly'),
                ],
                [
                    'title' => 'Weekly STEM Engineer Performance',
                    'description' => 'Track weekly sessions, teaching hours, and prep readiness.',
                    'icon' => 'fa-person-chalkboard',
                    'route' => route('reports.stem-engineer-performance.weekly'),
                ],
                [
                    'title' => 'Monthly STEM Engineer Performance',
                    'description' => 'Review STEM Engineer consistency and outcomes by month.',
                    'icon' => 'fa-chart-pie',
                    'route' => route('reports.stem-engineer-performance.monthly'),
                ],
            ],
        ]);
    }

    public function adminApprovalsHub()
    {
        return view('admin-feature-hub', [
            'title' => 'Approvals',
            'description' => 'Review submitted items and approve only what is ready.',
            'items' => [
                [
                    'title' => 'Question Paper',
                    'description' => 'Approve uploaded question papers for assessment use.',
                    'icon' => 'fa-file-circle-check',
                    'route' => route('admin.question-papers'),
                ],
                [
                    'title' => 'Certificate',
                    'description' => 'Review certificate requests before issuing certificates.',
                    'icon' => 'fa-certificate',
                    'route' => route('admin.certificates'),
                ],
                [
                    'title' => 'My Space - STEM Engineers',
                    'description' => 'Review STEM Engineer My Space posts and featured submissions.',
                    'icon' => 'fa-user-tie',
                    'route' => route('admin.my-space.teachers'),
                ],
                [
                    'title' => 'My Space - Students',
                    'description' => 'Review student My Space posts and featured submissions.',
                    'icon' => 'fa-user-graduate',
                    'route' => route('admin.my-space.students'),
                ],
                [
                    'title' => 'Achievements - STEM Engineers',
                    'description' => 'Approve STEM Engineer achievements for profile and blog visibility.',
                    'icon' => 'fa-award',
                    'route' => route('admin.teacher-achievements'),
                ],
                [
                    'title' => 'Achievements - Students',
                    'description' => 'Approve student achievements for profile and blog visibility.',
                    'icon' => 'fa-medal',
                    'route' => route('admin.achievements'),
                ],
            ],
        ]);
    }

    public function adminMonitoringHub()
    {
        $items = [];

        if (session('user_role') == 'Admin') {
            $items[] = [
                'title' => 'Learning Content Monitoring',
                'description' => 'Track how long STEM Engineers and students access learning content.',
                'icon' => 'fa-wave-square',
                'route' => route('admin.activity.monitoring'),
            ];
        }

        $items[] = [
            'title' => 'Assessment',
            'description' => 'Monitor assessments, status, and assessment activity.',
            'icon' => 'fa-list-check',
            'route' => route('admin.assessment.monitoring'),
        ];

        $items[] = [
            'title' => 'Assessment Review',
            'description' => 'Monitor manual assessment review and evaluation status.',
            'icon' => 'fa-magnifying-glass-chart',
            'route' => route('admin.assessment.review.monitoring'),
        ];

        return view('admin-feature-hub', [
            'title' => 'Monitoring',
            'description' => 'Open the monitoring area you need.',
            'items' => $items,
        ]);
    }

    public function students(Request $request)
    {
        $search = $request->search;
        $sectionPager = null;
        $currentInstitute = null;

        if (session('user_role') == 'Admin') {
            ['currentInstitute' => $currentInstitute, 'sectionPager' => $sectionPager] =
                $this->buildInstituteSectionPager($request, 'students');
        }

        $students = Student::when(session('user_role') == 'InstituteAdmin', function ($query) {
                $query->where('institute', session('user_institute'));
            })
            ->when(session('user_role') == 'Admin' && $currentInstitute, function ($query) use ($currentInstitute) {
                $query->where('institute', $currentInstitute);
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
            ->orderBy('institute')
            ->orderBy('class')
            ->orderBy('section')
            ->orderBy('name')
            ->paginate(30)
            ->withQueryString();

        return view('students', compact('students', 'sectionPager'));
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
    public function storeStudent(Request $request)
    {
        $institute = session('user_role') == 'InstituteAdmin'
            ? session('user_institute')
            : $request->institute;

        $request->validate([
            'student_id' => [
                'required',
                'string',
                'max:50',
                Rule::unique('students', 'student_id')->where(fn ($query) => $query->where('institute', $institute)),
            ],
            'name' => 'required|string|max:100',
            'institute' => session('user_role') == 'Admin'
                ? 'required|string|max:100'
                : 'nullable|string|max:100',
            'class' => 'required|string|max:50',
            'section' => 'required|string|max:20',
            'contact' => 'required|string|max:20',
            'password' => 'required|min:6',
        ]);

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
        $student = Student::findOrFail($id);
        $institute = session('user_role') == 'InstituteAdmin'
            ? session('user_institute')
            : $request->institute;

        $request->validate([
            'student_id' => [
                'required',
                'string',
                'max:50',
                Rule::unique('students', 'student_id')
                    ->where(fn ($query) => $query->where('institute', $institute))
                    ->ignore($student->id),
            ],
            'name' => 'required|string|max:100',
            'institute' => 'required|string|max:100',
            'class' => 'required|string|max:50',
            'section' => 'required|string|max:20',
            'contact' => 'required|string|max:20',
            'password' => 'nullable|min:6',
            'status' => 'required|boolean',
        ]);

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
            'institute' => $institute,
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

        DB::transaction(function () use ($student) {
            $this->deleteStudentCompletely($student);
        });

        return redirect()
            ->route('students')
            ->with('success', 'Student and all related records deleted successfully.');
    }

    public function adminCertificates(Request $request)
    {
        $sectionPager = null;
        $currentInstitute = null;

        if (session('user_role') == 'Admin') {
            ['currentInstitute' => $currentInstitute, 'sectionPager' => $sectionPager] =
                $this->buildInstituteSectionPager($request, 'admin.certificates');
        }

        $certificates = Certificate::with(['student', 'course'])
            ->when(session('user_role') == 'InstituteAdmin', function ($query) {
                $query->whereHas('student', function ($q) {
                    $q->where('institute', session('user_institute'));
                });
            })
            ->when(session('user_role') == 'Admin' && $currentInstitute, function ($query) use ($currentInstitute) {
                $query->whereHas('student', function ($q) use ($currentInstitute) {
                    $q->where('institute', $currentInstitute);
                });
            })
            ->latest()
            ->paginate(30)
            ->withQueryString();

        return view('certificates', compact('certificates', 'sectionPager'));
    }

    public function approveCertificate($id)
    {
        $certificate = Certificate::with('student')->findOrFail($id);

        $this->authorizeCertificateApproval($certificate);

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
            'status' => 'approved',
            'issued_date' => now(),
            'approved_by' => session('user_id'),
            'approved_at' => now(),
            'rejection_reason' => null,
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

    private function authorizeCertificateApproval(Certificate $certificate): void
    {
        if (session('user_role') == 'Admin') {
            return;
        }

        if (
            session('user_role') == 'InstituteAdmin' &&
            $certificate->student &&
            $certificate->student->institute == session('user_institute')
        ) {
            return;
        }

        if (session('user_role') == 'Teacher') {
            $teacher = User::find(session('user_id'));

            if (
                $teacher &&
                $certificate->student &&
                $certificate->student->institute == $teacher->institute
            ) {
                return;
            }
        }

        abort(403, 'Unauthorized action.');
    }

    public function rejectCertificate(Request $request, $id)
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
            'status' => 'rejected',
            'approved_by' => session('user_id'),
            'approved_at' => now(),
            'rejection_reason' => $request->rejection_reason,
        ]);

        return redirect()->back()
            ->with('success', 'Certificate request rejected.');
    }

    public function teacherDashboard()
    {
        $teacherName = session('user_name');
        $teacher = User::find(session('user_id'));
        $classes = SchoolClass::where('institute', $teacher->institute)
            ->get();
        $contentCount = TeachingPlanItem::where('status', 'released')
            ->whereHas('plan', function ($query) use ($teacher) {
                $query->where('institute', $teacher->institute)
                    ->where('status', 'active');
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
        $assignedClasses = $classes->count();
        $totalStudents = Student::where('institute', $teacher->institute)->count();

        return view('teacher.teacher-dashboard', compact(
            'teacherName',
            'contentCount',
            'assessmentCount',
            'monthlyAssessmentCount',
            'annualAssessmentCount',
            'assignedClasses',
            'totalStudents',
            'classes',
        ));
    }

    public function teacherSessionsHub()
    {
        return view('teacher.teacher-feature-hub', [
            'title' => 'Sessions',
            'description' => 'Start classes, review pending sessions, and open released learning content.',
            'items' => [
                [
                    'title' => 'My Classes',
                    'description' => 'Start and manage today\'s class sessions.',
                    'icon' => 'fa-chalkboard-user',
                    'route' => route('teacher.classes'),
                ],
                [
                    'title' => 'Pending Sessions',
                    'description' => 'Continue unfinished, partially completed, or lagged sessions.',
                    'icon' => 'fa-clock-rotate-left',
                    'route' => route('teacher.pending-sessions'),
                ],
                [
                    'title' => 'Learning Content',
                    'description' => 'View approved teaching plan content and AI prep materials.',
                    'icon' => 'fa-book-open-reader',
                    'route' => route('teacher.content'),
                ],
            ],
        ]);
    }

    public function teacherAssessmentsHub()
    {
        return view('teacher.teacher-feature-hub', [
            'title' => 'Assessments',
            'description' => 'Create assessments and evaluate submitted answers from one place.',
            'items' => [
                [
                    'title' => 'Assessment Management',
                    'description' => 'Create and manage uploaded-paper assessments.',
                    'icon' => 'fa-file-pen',
                    'route' => route('teacher.assessments'),
                ],
                [
                    'title' => 'Assessment Evaluation',
                    'description' => 'Review student submissions and enter final marks manually.',
                    'icon' => 'fa-clipboard-check',
                    'route' => route('assessment.review'),
                ],
            ],
        ]);
    }

    public function teacherStudentsHub()
    {
        return view('teacher.teacher-feature-hub', [
            'title' => 'Students',
            'description' => 'Review student outcomes, profiles, and approved certificates.',
            'items' => [
                [
                    'title' => 'Student Results',
                    'description' => 'View assessment scores, badges, and performance insights.',
                    'icon' => 'fa-chart-simple',
                    'route' => route('teacher.results'),
                ],
                [
                    'title' => 'Student Details',
                    'description' => 'Open student profile and class-wise learner details.',
                    'icon' => 'fa-address-card',
                    'route' => route('teacher.student.profiles'),
                ],
                [
                    'title' => 'Certificates',
                    'description' => 'Review student certificate status and approvals.',
                    'icon' => 'fa-certificate',
                    'route' => route('teacher.certificates'),
                ],
            ],
        ]);
    }

    public function teacherClasses(Request $request)
    {
        $teacher = User::find(session('user_id'));
        $this->autoEndExpiredClassSessions($teacher);

        $today = now()->format('Y-m-d');
        $classOptions = $this->teacherAssignedClassNames($teacher);
        $selectedClass = $request->input('class');

        $releasedItems = TeachingPlanItem::with([
                'plan',
                'week',
                'course',
                'content.aiSummary',
                'content.courseContent.sourceTemplateContent.aiSummary',
                'courseContent',
            ])
            ->where('status', 'released')
            ->whereHas('plan', function ($query) use ($teacher, $selectedClass) {
                $query->where('institute', $teacher->institute)
                    ->where('status', 'active')
                    ->when($selectedClass, function ($classQuery) use ($selectedClass) {
                        $classQuery->whereRaw(
                            "REPLACE(TRIM(CONCAT(COALESCE(class, ''), ' ', COALESCE(section, ''))), '  ', ' ') = ?",
                            [$selectedClass]
                        );
                    });
            })
            ->orderBy('sort_order')
            ->get();

        $teacherPassedPrepKeys = $this->teacherPassedPrepKeys($teacher);

        $aiTrainingRequiredItemIds = $releasedItems
            ->filter(fn ($item) => $this->teachingPlanItemRequiresAiTraining($item))
            ->pluck('id')
            ->unique()
            ->values();

        $sessions = ClassContentSession::with(['course', 'content', 'teachingPlan', 'teachingPlanWeek', 'teachingPlanItem'])
            ->where('stem_engineer_id', $teacher->id)
            ->where('institute', $teacher->institute)
            ->whereDate('session_date', $today)
            ->when($selectedClass, function ($query) use ($selectedClass) {
                $query->whereRaw(
                    "REPLACE(TRIM(CONCAT(COALESCE(class, ''), ' ', COALESCE(section, ''))), '  ', ' ') = ?",
                    [$selectedClass]
                );
            })
            ->latest()
            ->get();

        $unfinishedSessions = ClassContentSession::with(['course', 'content', 'teachingPlan', 'teachingPlanWeek', 'teachingPlanItem'])
            ->where('stem_engineer_id', $teacher->id)
            ->where('institute', $teacher->institute)
            ->whereIn('status', ['in_progress', 'partially_completed'])
            ->when($selectedClass, function ($query) use ($selectedClass) {
                $query->whereRaw(
                    "REPLACE(TRIM(CONCAT(COALESCE(class, ''), ' ', COALESCE(section, ''))), '  ', ' ') = ?",
                    [$selectedClass]
                );
            })
            ->orderBy('status')
            ->latest('session_date')
            ->latest()
            ->get();

        $activeSessions = $unfinishedSessions->where('status', 'in_progress');
        $sessionCompletionVideoUrl = session('sessionCompletionCelebration')
            ? $this->randomSessionCompletionVideoUrl()
            : null;

        return view(
            'teacher.my-classes',
            compact(
                'releasedItems',
                'sessions',
                'unfinishedSessions',
                'activeSessions',
                'classOptions',
                'selectedClass',
                'teacherPassedPrepKeys',
                'aiTrainingRequiredItemIds',
                'sessionCompletionVideoUrl'
            )
        );
    }

    public function teacherPendingSessions(Request $request)
    {
        $teacher = User::findOrFail(session('user_id'));
        $this->autoEndExpiredClassSessions($teacher);

        $classOptions = $this->teacherAssignedClassNames($teacher);
        $selectedClass = $request->input('class');

        $pendingSessions = ClassContentSession::with([
                'course',
                'content.aiSummary',
                'content.courseContent.sourceTemplateContent.aiSummary',
                'teachingPlan',
                'teachingPlanWeek',
                'teachingPlanItem',
            ])
            ->where('stem_engineer_id', $teacher->id)
            ->where('institute', $teacher->institute)
            ->whereIn('status', ['in_progress', 'partially_completed', 'cancelled'])
            ->when($selectedClass, function ($query) use ($selectedClass) {
                $query->whereRaw(
                    "REPLACE(TRIM(CONCAT(COALESCE(class, ''), ' ', COALESCE(section, ''))), '  ', ' ') = ?",
                    [$selectedClass]
                );
            })
            ->orderByRaw("FIELD(status, 'in_progress', 'partially_completed', 'cancelled')")
            ->latest('session_date')
            ->latest()
            ->get()
            ->reject(function ($session) use ($teacher) {
                if ($session->teachingPlanItem && $session->teachingPlanItem->status === 'completed') {
                    return true;
                }

                if (!$session->teaching_plan_item_id) {
                    return false;
                }

                return ClassContentSession::where('stem_engineer_id', $teacher->id)
                    ->where('institute', $teacher->institute)
                    ->where('teaching_plan_item_id', $session->teaching_plan_item_id)
                    ->where('status', 'completed')
                    ->exists();
            })
            ->values();

        $pendingItemIds = $pendingSessions
            ->pluck('teaching_plan_item_id')
            ->filter()
            ->unique()
            ->values();

        $laggedItems = TeachingPlanItem::with([
                'plan',
                'week',
                'course',
                'content.aiSummary',
                'content.courseContent.sourceTemplateContent.aiSummary',
                'courseContent',
            ])
            ->where('status', 'released')
            ->when($pendingItemIds->isNotEmpty(), function ($query) use ($pendingItemIds) {
                $query->whereNotIn('id', $pendingItemIds);
            })
            ->whereHas('plan', function ($query) use ($teacher, $selectedClass) {
                $query->where('institute', $teacher->institute)
                    ->where('status', 'active')
                    ->when($selectedClass, function ($classQuery) use ($selectedClass) {
                        $classQuery->whereRaw(
                            "REPLACE(TRIM(CONCAT(COALESCE(class, ''), ' ', COALESCE(section, ''))), '  ', ' ') = ?",
                            [$selectedClass]
                        );
                    });
            })
            ->whereHas('week', function ($query) {
                $query->where('status', 'released')
                    ->where('release_reason', 'lagged_content');
            })
            ->orderBy('teaching_plan_week_id')
            ->orderBy('sort_order')
            ->get();

        $teacherPassedPrepKeys = $this->teacherPassedPrepKeys($teacher);

        $aiTrainingRequiredItemIds = $laggedItems
            ->filter(fn ($item) => $this->teachingPlanItemRequiresAiTraining($item))
            ->pluck('id')
            ->unique()
            ->values();

        return view('teacher.pending-sessions', compact(
            'pendingSessions',
            'laggedItems',
            'classOptions',
            'selectedClass',
            'teacherPassedPrepKeys',
            'aiTrainingRequiredItemIds'
        ));
    }

    public function streamSessionCompletionVideo($fileName)
    {
        $safeFileName = basename((string) $fileName);
        $extension = strtolower(pathinfo($safeFileName, PATHINFO_EXTENSION));

        if (!in_array($extension, ['mp4', 'webm', 'ogg'], true)) {
            abort(404);
        }

        $resolvedPath = public_path('videos/session-completion/' . $safeFileName);

        if (!is_file($resolvedPath)) {
            abort(404);
        }

        $mimeType = mime_content_type($resolvedPath) ?: 'video/' . $extension;

        return response()->file($resolvedPath, [
            'Content-Type' => $mimeType,
            'Content-Disposition' => 'inline; filename="' . str_replace('"', '', $safeFileName) . '"',
            'Cache-Control' => 'private, no-store, no-cache, must-revalidate',
            'Pragma' => 'no-cache',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }

    private function randomSessionCompletionVideoUrl(): ?string
    {
        $directory = public_path('videos/session-completion');

        if (!is_dir($directory)) {
            return null;
        }

        $videos = collect(scandir($directory) ?: [])
            ->reject(fn ($fileName) => in_array($fileName, ['.', '..'], true))
            ->filter(function ($path) {
                return in_array(strtolower(pathinfo($path, PATHINFO_EXTENSION)), ['mp4', 'webm', 'ogg'], true);
            })
            ->values();

        if ($videos->isEmpty()) {
            return null;
        }

        return route('teacher.session-completion-video', basename($videos->random()));
    }

    private function autoEndExpiredClassSessions(User $teacher): void
    {
        $maximumSessionSeconds = 50 * 60;

        ClassContentSession::where('stem_engineer_id', $teacher->id)
            ->where('institute', $teacher->institute)
            ->where('status', 'in_progress')
            ->whereNotNull('started_at')
            ->where('started_at', '<=', now()->subSeconds($maximumSessionSeconds))
            ->get()
            ->each(function (ClassContentSession $session) use ($maximumSessionSeconds) {
                $endedAt = \Carbon\Carbon::parse($session->started_at)->addSeconds($maximumSessionSeconds);
                $remarks = trim(($session->remarks ? $session->remarks . "\n" : '') . 'System note: Session automatically ended after reaching the 50 minute maximum window.');

                $session->update([
                    'ended_at' => $endedAt,
                    'end_time' => $endedAt->format('H:i:s'),
                    'duration_seconds' => $maximumSessionSeconds,
                    'status' => 'partially_completed',
                    'delivered_topic' => $session->delivered_topic ?: $session->planned_topic,
                    'delivered_content_id' => $session->content_id,
                    'remarks' => $remarks,
                ]);
            });
    }

    public function teacherContent(Request $request)
    {
        $teacher = User::find(session('user_id'));
        $classOptions = $this->teacherAssignedClassNames($teacher);
        $selectedClass = $request->input('class');

        $teachingItems = TeachingPlanItem::with(['plan', 'week'])
            ->whereIn('status', ['released', 'completed'])
            ->whereHas('plan', function ($query) use ($teacher, $selectedClass) {
                $query->where('institute', $teacher->institute)
                    ->whereIn('status', ['active', 'completed'])
                    ->when($selectedClass, function ($classQuery) use ($selectedClass) {
                        $classQuery->whereRaw(
                            "REPLACE(TRIM(CONCAT(COALESCE(class, ''), ' ', COALESCE(section, ''))), '  ', ' ') = ?",
                            [$selectedClass]
                        );
                    });
            })
            ->whereNotNull('content_id')
            ->get();

        $approvedContentIds = $teachingItems
            ->pluck('content_id')
            ->unique()
            ->values();

        $teachingStatusByContentId = $teachingItems
            ->groupBy('content_id')
            ->map(function ($items) {
                if ($items->contains('status', 'released')) {
                    return 'released';
                }

                if ($items->contains('status', 'completed')) {
                    return 'completed';
                }

                return $items->first()->status;
            });

        $contentClassByContentId = $teachingItems
            ->groupBy('content_id')
            ->map(function ($items) {
                $plan = $items->first()->plan;

                return $plan
                    ? preg_replace('/\s+/', ' ', trim($plan->class . ' ' . $plan->section))
                    : 'Unassigned Class';
            });

        $contentGradeByContentId = $teachingItems
            ->groupBy('content_id')
            ->map(function ($items) {
                return $this->gradeLevelFromClass($items->first()->plan?->class);
            });

        $aiTrainingRequiredContentIds = $teachingItems
            ->filter(fn ($item) => $this->teachingPlanItemRequiresAiTraining($item))
            ->pluck('content_id')
            ->unique()
            ->values();

        $teacherPassedPrepKeys = $this->teacherPassedPrepKeys($teacher);

        $contents = Content::with(['aiSummary', 'courseContent.sourceTemplateContent.aiSummary'])
            ->whereIn('id', $approvedContentIds)
            ->orderBy('lesson_order')
            ->orderBy('content_title')
            ->get();

        $inProgressContentIds = ClassContentSession::where('institute', $teacher->institute)
            ->where('stem_engineer_id', $teacher->id)
            ->where('status', 'in_progress')
            ->whereNotNull('content_id')
            ->pluck('content_id')
            ->unique()
            ->values();

        return view('teacher.teacher-content', compact(
            'contents',
            'teachingStatusByContentId',
            'inProgressContentIds',
            'contentClassByContentId',
            'contentGradeByContentId',
            'aiTrainingRequiredContentIds',
            'teacherPassedPrepKeys',
            'classOptions',
            'selectedClass'
        ));
    }

    public function teacherAiPrep($id)
    {
        $teacher = User::findOrFail(session('user_id'));
        $content = $this->teacherAccessibleContent($teacher, $id);

        if (!$content) {
            abort(403, 'This content is not released for your institute.');
        }

        if (!$this->teacherContentRequiresAiTraining($teacher, $content)) {
            return redirect()
                ->route('teacher.content')
                ->with('success', 'AI prep is not required for this content.');
        }

        $summary = $this->generatedAiSummaryForContentRecord($content);

        if (!$summary) {
            return redirect()
                ->route('teacher.content')
                ->with('error', 'AI prep is not available for this content yet.');
        }

        $gradeLevel = $this->teacherAiGradeForContent($teacher, $content);
        $quiz = $this->aiQuizForContent($content, $summary, 'teacher', $gradeLevel);
        $latestAttempt = AiQuizAttempt::where('ai_quiz_id', $quiz->id)
            ->where('teacher_id', $teacher->id)
            ->where('attempt_type', self::AI_TEACHER_ATTEMPT_TYPE)
            ->latest()
            ->first();

        return view('teacher.ai-prep', compact(
            'content',
            'summary',
            'gradeLevel',
            'latestAttempt'
        ));
    }

    public function teacherAiPrepQuiz($id)
    {
        $teacher = User::findOrFail(session('user_id'));
        $content = $this->teacherAccessibleContent($teacher, $id);

        if (!$content) {
            abort(403, 'This content is not released for your institute.');
        }

        if (!$this->teacherContentRequiresAiTraining($teacher, $content)) {
            return redirect()
                ->route('teacher.content')
                ->with('success', 'AI prep is not required for this content.');
        }

        $summary = $this->generatedAiSummaryForContentRecord($content);

        if (!$summary) {
            return redirect()
                ->route('teacher.content')
                ->with('error', 'AI prep is not available for this content yet.');
        }

        $gradeLevel = $this->teacherAiGradeForContent($teacher, $content);
        $quiz = $this->aiQuizForContent($content, $summary, 'teacher', $gradeLevel);
        $latestAttempt = AiQuizAttempt::where('ai_quiz_id', $quiz->id)
            ->where('teacher_id', $teacher->id)
            ->where('attempt_type', self::AI_TEACHER_ATTEMPT_TYPE)
            ->latest()
            ->first();

        if ($latestAttempt && $latestAttempt->status == 'passed') {
            return redirect()
                ->route('teacher.ai-prep', $content->id)
                ->with('success', 'Prep quiz already cleared. You can now conduct this class.');
        }

        return view('teacher.ai-prep-quiz', compact(
            'content',
            'quiz',
            'gradeLevel',
            'latestAttempt'
        ));
    }

    public function submitTeacherAiPrep(Request $request, $id)
    {
        $teacher = User::findOrFail(session('user_id'));
        $content = $this->teacherAccessibleContent($teacher, $id);

        if (!$content) {
            abort(403, 'This content is not released for your institute.');
        }

        if (!$this->teacherContentRequiresAiTraining($teacher, $content)) {
            return redirect()
                ->route('teacher.content')
                ->with('success', 'AI prep is not required for this content.');
        }

        $summary = $this->generatedAiSummaryForContentRecord($content);

        if (!$summary) {
            return redirect()
                ->route('teacher.content')
                ->with('error', 'AI prep is not available for this content yet.');
        }

        $gradeLevel = $this->teacherAiGradeForContent($teacher, $content);
        $quiz = $this->aiQuizForContent($content, $summary, 'teacher', $gradeLevel);
        $questions = $quiz->questions()->orderBy('question_order')->get();

        $alreadyPassed = AiQuizAttempt::where('ai_quiz_id', $quiz->id)
            ->where('teacher_id', $teacher->id)
            ->where('attempt_type', self::AI_TEACHER_ATTEMPT_TYPE)
            ->where('status', 'passed')
            ->exists();

        if ($alreadyPassed) {
            return redirect()
                ->route('teacher.ai-prep', $content->id)
                ->with('success', 'Prep quiz already cleared. No further attempts are needed.');
        }

        $autoSubmitted = $request->boolean('auto_submitted');

        $request->validate([
            'answers' => [$autoSubmitted ? 'nullable' : 'required', 'array'],
            'answers.*' => ['nullable', 'string', 'max:500'],
            'auto_submitted' => ['nullable', 'boolean'],
        ]);

        $answers = collect($request->input('answers', []))
            ->map(fn ($answer) => trim((string) $answer));

        if (!$autoSubmitted && $answers->filter()->isEmpty()) {
            return redirect()
                ->back()
                ->withInput()
                ->with('error', 'Please answer at least one question before submitting the prep quiz.');
        }

        $attempt = AiQuizAttempt::create([
            'ai_quiz_id' => $quiz->id,
            'content_id' => $this->aiQuizOwnerContent($content)->id,
            'attempt_type' => self::AI_TEACHER_ATTEMPT_TYPE,
            'grade_level' => $gradeLevel,
            'teacher_id' => $teacher->id,
            'status' => 'submitted',
            'started_at' => now(),
            'submitted_at' => now(),
        ]);

        foreach ($questions as $question) {
            AiQuizAnswer::create([
                'ai_quiz_attempt_id' => $attempt->id,
                'ai_quiz_question_id' => $question->id,
                'answer_text' => $answers->get($question->id),
            ]);
        }

        if ($autoSubmitted) {
            $attempt->update([
                'score' => 0,
                'percentage' => 0,
                'status' => 'failed',
                'feedback' => 'Prep quiz was automatically submitted after repeated restricted actions.',
                'evaluated_at' => now(),
            ]);

            return redirect()
                ->route('teacher.dashboard')
                ->with('error', 'Prep quiz was automatically submitted after 3 restricted actions. Please review the content and try again.');
        }

        $evaluation = $this->evaluateMcqQuizAttempt($questions, $answers);
        $percentage = (float) ($evaluation['percentage'] ?? 0);
        $passingPercentage = $this->teacherAiPassingPercentage();
        $status = $percentage >= $passingPercentage ? 'passed' : 'failed';

        $attempt->update([
            'score' => $evaluation['score'] ?? null,
            'percentage' => $percentage,
            'status' => $status,
            'feedback' => $evaluation['feedback'] ?? null,
            'evaluated_at' => now(),
        ]);

        foreach ($evaluation['answer_feedback'] as $questionFeedback) {
            AiQuizAnswer::where('ai_quiz_attempt_id', $attempt->id)
                ->where('ai_quiz_question_id', $questionFeedback['question_id'])
                ->update([
                    'score' => $questionFeedback['score'],
                    'feedback' => $questionFeedback['feedback'],
                ]);
        }

        return redirect()
            ->route('teacher.dashboard')
            ->with($status == 'passed' ? 'success' : 'error', $status == 'passed'
                ? 'Prep quiz passed. Your readiness score has been saved.'
                : 'Prep quiz score is below ' . $passingPercentage . '%. Please review the content and try again.');
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

    public function teacherResults(Request $request)
    {
        $search = $request->search;
        $badge = $request->badge;
        $status = $request->status;
        $sort = $request->sort;
        $selectedClass = $request->input('class');

        $teacher = User::find(session('user_id'));
        $studentIds = $this->teacherAssignedStudentIds($teacher);
        $classOptions = $this->teacherAssignedClassNames($teacher);

        $results = AssessmentResult::with(['assessment', 'student'])
            ->whereIn('student_id', $studentIds)
            ->when($selectedClass, function ($query) use ($selectedClass) {
                $query->whereHas('student', function ($studentQuery) use ($selectedClass) {
                    $studentQuery->whereRaw(
                        "REPLACE(TRIM(CONCAT(COALESCE(class, ''), ' ', COALESCE(section, ''))), '  ', ' ') = ?",
                        [$selectedClass]
                    );
                });
            })
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
            'passPercentage',
            'classOptions',
            'selectedClass'
        ));
    }

    public function generateTeacherResultsAiInsights(Request $request, GeminiAiService $ai)
    {
        $teacher = User::findOrFail(session('user_id'));
        $metrics = $this->teacherResultsAiMetrics($request, $teacher);

        try {
            return redirect()
                ->route('teacher.results', $request->only(['search', 'class', 'badge', 'status', 'sort']))
                ->with('aiInsights', $ai->generateReportInsights('STEM Engineer Student Results', $metrics));
        } catch (\Throwable $exception) {
            return redirect()
                ->route('teacher.results', $request->only(['search', 'class', 'badge', 'status', 'sort']))
                ->with('error', 'AI insights could not be generated: ' . $exception->getMessage());
        }
    }

    public function downloadTeacherResultsAiInsights(Request $request, GeminiAiService $ai)
    {
        $teacher = User::findOrFail(session('user_id'));
        $metrics = $this->teacherResultsAiMetrics($request, $teacher);

        try {
            $insights = $ai->generateReportInsights('STEM Engineer Student Results', $metrics);
        } catch (\Throwable $exception) {
            return redirect()
                ->route('teacher.results', $request->only(['search', 'class', 'badge', 'status', 'sort']))
                ->with('error', 'AI result PDF could not be generated: ' . $exception->getMessage());
        }

        $pdf = Pdf::loadView('pdf.ai-insights-report', [
            'title' => 'AI Generated Student Result Insights',
            'scope' => $metrics['scope'] ?? $teacher->institute,
            'metrics' => $metrics,
            'insights' => $insights,
        ])->setPaper('a4', 'portrait');

        return $pdf->download('ai_student_results_' . now()->format('Ymd_His') . '.pdf');
    }

    private function teacherResultsAiMetrics(Request $request, User $teacher): array
    {
        $studentIds = $this->teacherAssignedStudentIds($teacher);
        $search = $request->input('search');
        $selectedClass = $request->input('class');
        $badge = $request->input('badge');
        $status = $request->input('status');

        $results = AssessmentResult::with(['assessment', 'student'])
            ->whereIn('student_id', $studentIds)
            ->when($selectedClass, function ($query) use ($selectedClass) {
                $query->whereHas('student', function ($studentQuery) use ($selectedClass) {
                    $studentQuery->whereRaw(
                        "REPLACE(TRIM(CONCAT(COALESCE(class, ''), ' ', COALESCE(section, ''))), '  ', ' ') = ?",
                        [$selectedClass]
                    );
                });
            })
            ->whereHas('assessment', fn ($query) => $query->where('teacher_id', $teacher->id))
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
            ->when($badge, fn ($query) => $query->where('badge', $badge))
            ->when($status, fn ($query) => $query->where('status', $status))
            ->get();

        $completedResults = $results->where('status', 'Completed');
        $topResult = $results->sortByDesc('percentage')->first();
        $lowestResult = $results->sortBy('percentage')->first();
        $classBreakdown = $results
            ->groupBy(fn ($result) => $result->student ? trim(($result->student->class ?? '') . ' ' . ($result->student->section ?? '')) : 'Unassigned Class')
            ->map(function ($classResults, $classLabel) {
                return [
                    'class' => $classLabel,
                    'results' => $classResults->count(),
                    'completed' => $classResults->where('status', 'Completed')->count(),
                    'average_percentage' => round($classResults->avg('percentage') ?? 0, 2),
                ];
            })
            ->values()
            ->all();

        return [
            'scope' => $teacher->institute . ' - ' . $teacher->name,
            'stem_engineer' => $teacher->name,
            'institute' => $teacher->institute,
            'search_filter' => $search ?: 'None',
            'class_filter' => $selectedClass ?: 'All Classes',
            'badge_filter' => $badge ?: 'All Badges',
            'status_filter' => $status ?: 'All Statuses',
            'generated_at' => now()->format('Y-m-d H:i:s'),
            'total_results' => $results->count(),
            'completed_results' => $completedResults->count(),
            'pending_results' => $results->where('status', 'Pending Review')->count(),
            'average_percentage' => round($completedResults->avg('percentage') ?? 0, 2),
            'pass_percentage' => $completedResults->count() > 0
                ? round(($completedResults->where('percentage', '>=', 50)->count() / $completedResults->count()) * 100, 2)
                : 0,
            'badge_distribution' => [
                'gold' => $results->where('badge', 'Gold')->count(),
                'silver' => $results->where('badge', 'Silver')->count(),
                'bronze' => $results->where('badge', 'Bronze')->count(),
                'none' => $results->whereNull('badge')->count(),
            ],
            'top_performer' => $topResult && $topResult->student ? $topResult->student->name : 'N/A',
            'lowest_performer' => $lowestResult && $lowestResult->student ? $lowestResult->student->name : 'N/A',
            'class_breakdown' => $classBreakdown,
        ];
    }

    public function teacherCertificates(Request $request)
    {
        $teacher = User::find(session('user_id'));
        $classOptions = $this->teacherAssignedClassNames($teacher);
        $selectedClass = $request->input('class');

        $certificates = Certificate::with(['student', 'course'])
            ->whereIn('student_id', $this->teacherAssignedStudentIds($teacher))
            ->when($selectedClass, function ($query) use ($selectedClass) {
                $query->whereHas('student', function ($studentQuery) use ($selectedClass) {
                    $studentQuery->whereRaw(
                        "REPLACE(TRIM(CONCAT(COALESCE(class, ''), ' ', COALESCE(section, ''))), '  ', ' ') = ?",
                        [$selectedClass]
                    );
                });
            })
            ->latest()
            ->get();

        $totalCertificates = $certificates->count();

        $issuedCertificates = $certificates
            ->whereIn('status', ['Issued', 'approved'])
            ->count();

        $revokedCertificates = $certificates
            ->where('status', 'Revoked')
            ->count();

        return view('teacher.teacher-certificates', compact(
            'certificates',
            'totalCertificates',
            'issuedCertificates',
            'revokedCertificates',
            'classOptions',
            'selectedClass'
        ));
    }
   public function teacherProfile()
    {
        $teacher = User::find(session('user_id'));
        $mySpaceItems = \App\Models\MySpace::where('created_by_type', 'Teacher')
            ->where('created_by_id', $teacher->id)
            ->whereIn('status', ['Approved', 'Featured'])
            ->latest()
            ->get();

        $achievements = TeacherAchievement::where('user_id', $teacher->id)
            ->where('verification_status', 'Approved')
            ->latest()
            ->get();

        return view('teacher.teacher-profile', compact('teacher', 'mySpaceItems', 'achievements'));
    }

    public function teacherAchievements()
    {
        $teacher = User::findOrFail(session('user_id'));

        $achievements = TeacherAchievement::where('user_id', $teacher->id)
            ->latest()
            ->get();

        return view('teacher.teacher-achievements', compact('teacher', 'achievements'));
    }

    public function updateTeacherProfile(Request $request)
    {
        $teacher = User::findOrFail(session('user_id'));

        $request->validate([
            'user_id' => ['required', 'string', 'max:255', Rule::unique('users', 'user_id')->ignore($teacher->id)],
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255',
            'designation' => 'nullable|string|max:255',
            'qualification' => 'required|string|max:255',
            'joined_on' => 'nullable|date',
            'linkedin_url' => 'nullable|url|max:255',
            'profile_image' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:2048',
        ]);

        $updates = [
            'user_id' => $request->user_id,
            'name' => $request->name,
            'email' => $request->email,
            'designation' => $request->designation,
            'qualification' => $request->qualification,
            'joined_on' => $request->joined_on,
            'linkedin_url' => $request->linkedin_url,
        ];

        if ($request->hasFile('profile_image')) {
            $this->deleteStoredFile($teacher->profile_image);
            $updates['profile_image'] = $request->file('profile_image')->store('profile-images', 'public');
        }

        $teacher->update($updates);

        session([
            'user_id_text' => $teacher->user_id,
            'user_name' => $teacher->name,
            'user_email' => $teacher->email,
        ]);

        return redirect()->back()->with('success', 'Profile updated successfully.');
    }

    public function storeTeacherAchievement(Request $request)
    {
        $teacher = User::findOrFail(session('user_id'));

        $request->validate([
            'achievement_type' => 'required|string|max:100',
            'title' => 'required|string|max:255',
            'organizer' => 'nullable|string|max:255',
            'description' => 'nullable|string',
            'achievement_date' => 'nullable|date',
            'position' => 'nullable|string|max:100',
            'certificate_file' => 'nullable|mimes:pdf,jpg,jpeg,png|max:5120',
        ]);

        $filePath = $request->hasFile('certificate_file')
            ? $request->file('certificate_file')->store('teacher-achievements', 'public')
            : null;

        TeacherAchievement::create([
            'user_id' => $teacher->id,
            'achievement_type' => $request->achievement_type,
            'title' => $request->title,
            'organizer' => $request->organizer,
            'description' => $request->description,
            'achievement_date' => $request->achievement_date,
            'position' => $request->position,
            'certificate_file' => $filePath,
            'verification_status' => 'Pending',
        ]);

        return redirect()->back()->with('success', 'Achievement added successfully.');
    }

    public function deleteTeacherAchievement($id)
    {
        $achievement = TeacherAchievement::where('id', $id)
            ->where('user_id', session('user_id'))
            ->firstOrFail();

        $this->deleteStoredFile($achievement->certificate_file);
        $achievement->delete();

        return redirect()->back()->with('success', 'Achievement removed successfully.');
    }

    public function adminTeacherAchievements(Request $request)
    {
        $sectionPager = null;
        $currentInstitute = null;

        if (session('user_role') == 'Admin') {
            ['currentInstitute' => $currentInstitute, 'sectionPager' => $sectionPager] =
                $this->buildInstituteSectionPager($request, 'admin.teacher-achievements');
        }

        $achievements = TeacherAchievement::with('teacher')
            ->when(session('user_role') == 'InstituteAdmin', function ($query) {
                $query->whereHas('teacher', function ($teacherQuery) {
                    $teacherQuery->where('institute', session('user_institute'));
                });
            })
            ->when(session('user_role') == 'Admin' && $currentInstitute, function ($query) use ($currentInstitute) {
                $query->whereHas('teacher', function ($teacherQuery) use ($currentInstitute) {
                    $teacherQuery->where('institute', $currentInstitute);
                });
            })
            ->latest()
            ->paginate(30)
            ->withQueryString();

        return view('admin-teacher-achievements', compact('achievements', 'sectionPager'));
    }

    public function approveTeacherAchievement($id)
    {
        $achievement = TeacherAchievement::with('teacher')->findOrFail($id);
        $this->authorizeTeacherAchievementApproval($achievement);

        $achievement->update([
            'verification_status' => 'Approved',
        ]);

        $this->syncTeacherAchievementToCommunity($achievement->refresh());

        return redirect()->back()
            ->with('success', 'STEM Engineer achievement approved successfully.');
    }

    public function rejectTeacherAchievement($id)
    {
        $achievement = TeacherAchievement::with('teacher')->findOrFail($id);
        $this->authorizeTeacherAchievementApproval($achievement);

        $achievement->update([
            'verification_status' => 'Rejected',
        ]);

        $this->deleteCommunitySource('TeacherAchievement', $achievement->id);

        return redirect()->back()
            ->with('success', 'STEM Engineer achievement rejected successfully.');
    }

    private function authorizeTeacherAchievementApproval(TeacherAchievement $achievement): void
    {
        if (session('user_role') != 'InstituteAdmin') {
            return;
        }

        if (!$achievement->teacher || $achievement->teacher->institute != session('user_institute')) {
            abort(403, 'Unauthorized action.');
        }
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
            ->get()
            ->first(function ($student) use ($request) {
                return Hash::check($request->password, $student->password);
            });

        if ($student) {

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
                abort(403, 'You can only start assessments created by you in your institute.');
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
            ->get()
            ->map(function ($class) {
                return trim($class->class_name . ' ' . $class->section);
            })
            ->values();
    }

    private function teacherAssignedStudentsQuery(User $teacher)
    {
        return Student::where('institute', $teacher->institute);
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
            ->get()
            ->filter(fn ($assessment) => $this->assessmentWindowIsOpen($assessment))
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
        $achievements = StudentAchievement::where('student_id', $student->id)
            ->where('verification_status', 'Approved')
            ->latest()
            ->get();
        $mySpaceItems = \App\Models\MySpace::where('created_by_type', 'Student')
            ->where('created_by_id', $student->id)
            ->whereIn('status', ['Approved', 'Featured'])
            ->latest()
            ->get();

        return view('student.student-profile', compact(
            'student',
            'badgeCount',
            'achievements',
            'mySpaceItems'
        ));
    }

    public function updateStudentProfile(Request $request)
    {
        $student = Student::findOrFail(session('student_id'));

        $request->validate([
            'linkedin_url' => 'nullable|url|max:255',
            'profile_image' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:2048',
        ]);

        $updates = [
            'linkedin_url' => $request->linkedin_url,
        ];

        if ($request->hasFile('profile_image')) {
            $this->deleteStoredFile($student->profile_image);
            $updates['profile_image'] = $request->file('profile_image')->store('profile-images', 'public');
        }

        $student->update($updates);

        return redirect()->back()->with('success', 'Profile updated successfully.');
    }

    public function removeStudentProfileImage()
    {
        $student = Student::findOrFail(session('student_id'));

        $this->deleteStoredFile($student->profile_image);

        $student->update([
            'profile_image' => null,
        ]);

        return redirect()->back()->with('success', 'Profile image removed successfully.');
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
            ->where('status', 'approved')
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
                abort(403, 'You can only disqualify results from your institute and your own assessments.');
            }
        }

        if ($result->assessment && $result->assessment->assessment_category == 'Annual') {
            $this->deleteAnnualCertificatesForStudent($result->student_id);
        }

        $this->deleteAssessmentResultCompletely($result);

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

        $contentIds = $this->studentAvailableContentIds($student);

        $contents = Content::with(['aiSummary', 'courseContent.sourceTemplateContent.aiSummary'])
            ->whereIn('id', $contentIds)
            ->where('status', 1)
            ->orderBy('course_id')
            ->orderBy('lesson_order')
            ->get();

        $aiReviewRequiredContentIds = $this->studentAiReviewRequiredContentIds($student, $contentIds);
        $studentPassedAiReviewContentIds = $this->studentPassedAiReviewContentIds($student, $contents);

        $completedContentIds = LessonProgress::where('student_id', session('student_id'))
            ->whereIn('content_id', $contents->pluck('id'))
            ->where('is_completed', true)
            ->pluck('content_id')
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values();

        $lockedContentIds = $this->studentLockedContentIds($contents, $completedContentIds);

        $totalLessons = $contents->count();

        $completedLessons = $completedContentIds->count();

        $progressPercentage = $totalLessons > 0
            ? round(($completedLessons / $totalLessons) * 100)
            : 0;

        return view('student.student-content', compact(
            'contents',
            'totalLessons',
            'completedLessons',
            'progressPercentage',
            'aiReviewRequiredContentIds',
            'studentPassedAiReviewContentIds',
            'completedContentIds',
            'lockedContentIds'
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

    public function activityMonitoring(Request $request)
    {
        $date = $request->date;
        $viewerType = $request->viewer_type;
        ['currentInstitute' => $currentInstitute, 'sectionPager' => $sectionPager] =
            $this->buildInstituteSectionPager($request, 'admin.activity.monitoring');

        $contentRoutes = ['teacher.content', 'student.content', 'content.preview'];

        $contentLogsQuery = UserActivityLog::with(['teacher', 'student'])
            ->whereIn('user_type', ['Teacher', 'Student'])
            ->whereIn('route_name', $contentRoutes)
            ->when($date, function ($query, $date) {
                $query->whereDate('started_at', $date);
            })
            ->when(in_array($viewerType, ['Teacher', 'Student'], true), function ($query) use ($viewerType) {
                $query->where('user_type', $viewerType);
            })
            ->where(function ($query) use ($currentInstitute) {
                $query->where(function ($teacherQuery) use ($currentInstitute) {
                    $teacherQuery->where('user_type', 'Teacher')
                        ->whereHas('teacher', function ($teacher) use ($currentInstitute) {
                            $teacher->where('institute', $currentInstitute);
                        });
                })
                ->orWhere(function ($studentQuery) use ($currentInstitute) {
                    $studentQuery->where('user_type', 'Student')
                        ->whereHas('student', function ($student) use ($currentInstitute) {
                            $student->where('institute', $currentInstitute);
                        });
                });
            });

        $contentLogs = (clone $contentLogsQuery)
            ->latest('started_at')
            ->paginate(30)
            ->withQueryString();

        $totalAccesses = (clone $contentLogsQuery)->count();
        $teacherAccesses = (clone $contentLogsQuery)->where('user_type', 'Teacher')->count();
        $studentAccesses = (clone $contentLogsQuery)->where('user_type', 'Student')->count();
        $totalDurationSeconds = (int) (clone $contentLogsQuery)->sum('duration_seconds');

        $sectionDurations = (clone $contentLogsQuery)
            ->selectRaw('section_name, SUM(duration_seconds) as total_duration, COUNT(*) as total_accesses')
            ->groupBy('section_name')
            ->orderByDesc('total_duration')
            ->get();

        $userDurations = (clone $contentLogsQuery)
            ->selectRaw('user_type, user_id, SUM(duration_seconds) as total_duration, COUNT(*) as total_accesses')
            ->groupBy('user_type', 'user_id')
            ->orderByDesc('total_duration')
            ->take(10)
            ->get()
            ->map(function ($row) {
                $user = $row->user_type == 'Teacher'
                    ? User::find($row->user_id)
                    : Student::find($row->user_id);

                $row->display_name = $user->name ?? ($row->user_type == 'Teacher' ? 'STEM Engineer Deleted' : 'Student Deleted');
                $row->institute = $user->institute ?? 'N/A';
                $row->class_label = $row->user_type == 'Student'
                    ? trim(($user->class ?? '') . ' ' . ($user->section ?? ''))
                    : null;

                return $row;
            });

        return view('activity-monitoring', compact(
            'contentLogs',
            'currentInstitute',
            'sectionPager',
            'viewerType',
            'totalAccesses',
            'teacherAccesses',
            'studentAccesses',
            'totalDurationSeconds',
            'sectionDurations',
            'userDurations',
        ));
    }

    public function finishCurrentActivityLog(Request $request)
    {
        if (!session('tracking_session_id')) {
            return response()->noContent();
        }

        $contentRoutes = ['teacher.content', 'student.content', 'content.preview'];
        $activityLogId = session('active_activity_log_id');

        $activityLog = UserActivityLog::where('user_session_id', session('tracking_session_id'))
            ->whereNull('ended_at')
            ->whereIn('route_name', $contentRoutes)
            ->when($activityLogId, fn ($query) => $query->where('id', $activityLogId))
            ->latest()
            ->first();

        if ($activityLog) {
            $endedAt = now();

            $activityLog->update([
                'ended_at' => $endedAt,
                'duration_seconds' => max(0, \Carbon\Carbon::parse($activityLog->started_at)->diffInSeconds($endedAt)),
            ]);
        }

        return response()->noContent();
    }
    public function completeLesson($id)
    {
        $studentId = session('student_id');
        $student = Student::findOrFail($studentId);
        $contentIds = $this->studentAvailableContentIds($student);
        $content = Content::with(['aiSummary', 'courseContent.sourceTemplateContent.aiSummary'])
            ->where('id', $id)
            ->where('status', 1)
            ->first();

        if (!$content || !$contentIds->contains((int) $content->id)) {
            abort(403, 'This lesson is not assigned to your class.');
        }

        if ($this->studentContentIsSequenceLocked($student, $content)) {
            return redirect()
                ->route('student.content')
                ->with('error', 'Please clear the previous released content before starting this topic.');
        }

        if ($this->studentContentRequiresAiReview($student, $content) && !$this->generatedAiSummaryForContentRecord($content)) {
            return redirect()->back()
                ->with('error', 'Training assessment is still being prepared for this lesson. Please try again shortly.');
        }

        if ($this->studentContentRequiresAiReview($student, $content) && $this->lessonNeedsAiReview($content, $studentId)) {
            $this->unlockStudentAiReview($studentId, $content->id);

            return redirect()->route('student.content.ai-review', $content->id);
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

    public function studentAiReview($id)
    {
        $studentId = session('student_id');
        $student = Student::findOrFail($studentId);
        $content = $this->studentAccessibleContent($student, $id);

        if (!$content) {
            abort(403, 'This lesson is not assigned to your class.');
        }

        if ($this->studentContentIsSequenceLocked($student, $content)) {
            return redirect()
                ->route('student.content')
                ->with('error', 'Please clear the previous released content before starting this training assessment.');
        }

        if (!$this->studentContentRequiresAiReview($student, $content)) {
            return redirect()
                ->route('student.content')
                ->with('success', 'Training assessment is not required for this lesson.');
        }

        if (!$this->studentAiReviewIsUnlocked($studentId, $content)) {
            return redirect()
                ->route('student.content')
                ->with('error', 'Please mark this topic as complete before starting the training assessment.');
        }

        $summary = $this->generatedAiSummaryForContentRecord($content);

        if (!$summary) {
            return redirect()
                ->route('student.content')
                ->with('error', 'AI review is not available for this content yet.');
        }

        $gradeLevel = $this->studentGradeName($student);
        $quiz = $this->studentQuizForContent($content, $summary, $gradeLevel);
        $latestAttempt = AiQuizAttempt::where('ai_quiz_id', $quiz->id)
            ->where('student_id', $studentId)
            ->latest()
            ->first();

        return view('student.ai-review', compact(
            'content',
            'summary',
            'latestAttempt'
        ));
    }

    public function studentAiReviewQuiz($id)
    {
        $studentId = session('student_id');
        $student = Student::findOrFail($studentId);
        $content = $this->studentAccessibleContent($student, $id);

        if (!$content) {
            abort(403, 'This lesson is not assigned to your class.');
        }

        if ($this->studentContentIsSequenceLocked($student, $content)) {
            return redirect()
                ->route('student.content')
                ->with('error', 'Please clear the previous released content before starting this training assessment.');
        }

        if (!$this->studentContentRequiresAiReview($student, $content)) {
            return redirect()
                ->route('student.content')
                ->with('success', 'Training assessment is not required for this lesson.');
        }

        if (!$this->studentAiReviewIsUnlocked($studentId, $content)) {
            return redirect()
                ->route('student.content')
                ->with('error', 'Please mark this topic as complete before starting the training assessment.');
        }

        $summary = $this->generatedAiSummaryForContentRecord($content);

        if (!$summary) {
            return redirect()
                ->route('student.content')
                ->with('error', 'AI review is not available for this content yet.');
        }

        $gradeLevel = $this->studentGradeName($student);
        $quiz = $this->studentQuizForContent($content, $summary, $gradeLevel);
        $latestAttempt = AiQuizAttempt::where('ai_quiz_id', $quiz->id)
            ->where('student_id', $studentId)
            ->latest()
            ->first();

        if ($latestAttempt && $latestAttempt->status == 'passed') {
            return redirect()
                ->route('student.content.ai-review', $content->id)
                ->with('success', 'Training assessment already cleared. This lesson is complete.');
        }

        return view('student.ai-review-quiz', compact(
            'content',
            'quiz',
            'latestAttempt'
        ));
    }

    public function submitStudentAiReview(Request $request, $id)
    {
        $studentId = session('student_id');
        $student = Student::findOrFail($studentId);
        $content = $this->studentAccessibleContent($student, $id);

        if (!$content) {
            abort(403, 'This lesson is not assigned to your class.');
        }

        if ($this->studentContentIsSequenceLocked($student, $content)) {
            return redirect()
                ->route('student.content')
                ->with('error', 'Please clear the previous released content before submitting this training assessment.');
        }

        if (!$this->studentContentRequiresAiReview($student, $content)) {
            return redirect()
                ->route('student.content')
                ->with('success', 'Training assessment is not required for this lesson.');
        }

        if (!$this->studentAiReviewIsUnlocked($studentId, $content)) {
            return redirect()
                ->route('student.content')
                ->with('error', 'Please mark this topic as complete before submitting the training assessment.');
        }

        $summary = $this->generatedAiSummaryForContentRecord($content);

        if (!$summary) {
            return redirect()
                ->route('student.content')
                ->with('error', 'AI review is not available for this content yet.');
        }

        $gradeLevel = $this->studentGradeName($student);
        $quiz = $this->studentQuizForContent($content, $summary, $gradeLevel);
        $questions = $quiz->questions()->orderBy('question_order')->get();

        $alreadyPassed = AiQuizAttempt::where('ai_quiz_id', $quiz->id)
            ->where('student_id', $studentId)
            ->where('status', 'passed')
            ->exists();

        if ($alreadyPassed) {
            return redirect()
                ->route('student.content.ai-review', $content->id)
                ->with('success', 'Training assessment already cleared. This lesson is complete.');
        }

        $autoSubmitted = $request->boolean('auto_submitted');

        $request->validate([
            'answers' => [$autoSubmitted ? 'nullable' : 'required', 'array'],
            'answers.*' => ['nullable', 'string', 'max:500'],
            'auto_submitted' => ['nullable', 'boolean'],
        ]);

        $answers = collect($request->input('answers', []))
            ->map(fn ($answer) => trim((string) $answer));

        if (!$autoSubmitted && $answers->filter()->isEmpty()) {
            return redirect()
                ->back()
                ->withInput()
                ->with('error', 'Please answer at least one question before submitting the AI review.');
        }

        $attempt = AiQuizAttempt::create([
            'ai_quiz_id' => $quiz->id,
            'content_id' => $this->aiQuizOwnerContent($content)->id,
            'attempt_type' => self::AI_STUDENT_ATTEMPT_TYPE,
            'grade_level' => $gradeLevel,
            'student_id' => $studentId,
            'status' => 'submitted',
            'started_at' => now(),
            'submitted_at' => now(),
        ]);

        foreach ($questions as $question) {
            AiQuizAnswer::create([
                'ai_quiz_attempt_id' => $attempt->id,
                'ai_quiz_question_id' => $question->id,
                'answer_text' => $answers->get($question->id),
            ]);
        }

        if ($autoSubmitted) {
            $attempt->update([
                'score' => 0,
                'percentage' => 0,
                'status' => 'failed',
                'feedback' => 'Training assessment was automatically submitted after repeated restricted actions.',
                'evaluated_at' => now(),
            ]);

            return redirect()
                ->route('student.dashboard')
                ->with('error', 'Training assessment was automatically submitted after 3 restricted actions. Please review the content and try again.');
        }

        $evaluation = $this->evaluateMcqQuizAttempt($questions, $answers);
        $percentage = (float) ($evaluation['percentage'] ?? 0);
        $passingPercentage = $this->studentAiPassingPercentage();
        $status = $percentage >= $passingPercentage ? 'passed' : 'failed';

        $attempt->update([
            'score' => $evaluation['score'] ?? null,
            'percentage' => $percentage,
            'status' => $status,
            'feedback' => $evaluation['feedback'] ?? null,
            'evaluated_at' => now(),
        ]);

        foreach ($evaluation['answer_feedback'] as $questionFeedback) {
            AiQuizAnswer::where('ai_quiz_attempt_id', $attempt->id)
                ->where('ai_quiz_question_id', $questionFeedback['question_id'])
                ->update([
                    'score' => $questionFeedback['score'],
                    'feedback' => $questionFeedback['feedback'],
                ]);
        }

        if ($status == 'passed') {
            LessonProgress::updateOrCreate(
                [
                    'student_id' => $studentId,
                    'content_id' => $content->id,
                ],
                [
                    'is_completed' => true,
                    'completed_at' => now(),
                ]
            );

            return redirect()
                ->route('student.content')
                ->with('success', 'AI review passed. Lesson marked as completed.');
        }

        return redirect()
            ->route('student.content.ai-review.quiz', $content->id)
            ->with('error', 'AI review score is below ' . $passingPercentage . '%. Please review the content and try again.');
    }

    private function lessonNeedsAiReview(Content $content, int $studentId): bool
    {
        $student = Student::find($studentId);
        $summary = $this->generatedAiSummaryForContentRecord($content);

        if (!$summary || !$student) {
            return false;
        }

        $quiz = $this->aiQuizForContent($content, $summary, 'student', $this->studentGradeName($student));

        return !AiQuizAttempt::where('ai_quiz_id', $quiz->id)
            ->where('student_id', $studentId)
            ->where('attempt_type', self::AI_STUDENT_ATTEMPT_TYPE)
            ->where('status', 'passed')
            ->exists();
    }

    private function studentPassedAiReviewContentIds(Student $student, $contents)
    {
        $contents = collect($contents);
        $sourceIdsByContentId = $contents
            ->mapWithKeys(function (Content $content) {
                return [
                    (int) $content->id => (int) $this->aiQuizOwnerContent($content)->id,
                ];
            });

        $passedAttemptContentIds = AiQuizAttempt::where('student_id', $student->id)
            ->where('attempt_type', self::AI_STUDENT_ATTEMPT_TYPE)
            ->where('status', 'passed')
            ->whereIn('content_id', $sourceIdsByContentId->values()->unique()->all())
            ->pluck('content_id')
            ->map(fn ($id) => (int) $id)
            ->unique();

        return $sourceIdsByContentId
            ->filter(fn ($sourceId) => $passedAttemptContentIds->contains((int) $sourceId))
            ->keys()
            ->map(fn ($id) => (int) $id)
            ->values();
    }

    private function studentContentIsSequenceLocked(Student $student, Content $content): bool
    {
        $contentIds = $this->studentAvailableContentIds($student);

        if (!$contentIds->contains((int) $content->id)) {
            return true;
        }

        $contents = Content::whereIn('id', $contentIds)
            ->where('status', 1)
            ->orderBy('course_id')
            ->orderBy('lesson_order')
            ->get(['id', 'course_id', 'lesson_order']);

        $completedContentIds = LessonProgress::where('student_id', $student->id)
            ->whereIn('content_id', $contents->pluck('id'))
            ->where('is_completed', true)
            ->pluck('content_id')
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values();

        return $this->studentLockedContentIds($contents, $completedContentIds)
            ->contains((int) $content->id);
    }

    private function studentLockedContentIds($contents, $completedContentIds)
    {
        $completedContentIds = collect($completedContentIds)
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values();

        return collect($contents)
            ->groupBy('course_id')
            ->flatMap(function ($courseContents) use ($completedContentIds) {
                $lockedIds = collect();
                $previousContent = null;

                foreach ($courseContents->sortBy([
                    ['lesson_order', 'asc'],
                    ['id', 'asc'],
                ]) as $content) {
                    if ($previousContent && !$completedContentIds->contains((int) $previousContent->id)) {
                        $lockedIds->push((int) $content->id);
                    }

                    $previousContent = $content;
                }

                return $lockedIds;
            })
            ->unique()
            ->values();
    }

    private function unlockStudentAiReview(int $studentId, int $contentId): void
    {
        session([
            $this->studentAiReviewUnlockKey($studentId, $contentId) => true,
        ]);
    }

    private function studentAiReviewIsUnlocked(int $studentId, Content $content): bool
    {
        if (session($this->studentAiReviewUnlockKey($studentId, $content->id))) {
            return true;
        }

        $student = Student::find($studentId);
        $summary = $this->generatedAiSummaryForContentRecord($content);

        if (!$student || !$summary) {
            return false;
        }

        $quiz = $this->aiQuizForContent($content, $summary, 'student', $this->studentGradeName($student));

        return AiQuizAttempt::where('ai_quiz_id', $quiz->id)
            ->where('student_id', $studentId)
            ->where('attempt_type', self::AI_STUDENT_ATTEMPT_TYPE)
            ->exists();
    }

    private function studentAiReviewUnlockKey(int $studentId, int $contentId): string
    {
        return 'student_ai_review_unlocked_' . $studentId . '_' . $contentId;
    }

    private function teacherNeedsAiPrep(Content $content, int $teacherId, ?string $gradeLevel = null): bool
    {
        $summary = $this->generatedAiSummaryForContentRecord($content);

        if (!$summary) {
            return false;
        }

        $quiz = $this->aiQuizForContent($content, $summary, 'teacher', $gradeLevel);

        return !AiQuizAttempt::where('ai_quiz_id', $quiz->id)
            ->where('teacher_id', $teacherId)
            ->where('attempt_type', self::AI_TEACHER_ATTEMPT_TYPE)
            ->where('status', 'passed')
            ->exists();
    }

    private function teachingPlanItemRequiresAiTraining(TeachingPlanItem $item): bool
    {
        if (!$item->week || !$item->week->release_date || !$item->plan) {
            return false;
        }

        if ($item->week->release_reason === 'lagged_content') {
            return false;
        }

        $startDate = $this->planAiTrainingStartDate($item->plan);

        return $startDate
            && \Carbon\Carbon::parse($item->week->release_date)->toDateString() >= $startDate;
    }

    private function teachingPlanItemBypassesAiPrepForTeacher(TeachingPlanItem $item, User $teacher): bool
    {
        if ($item->week?->release_reason === 'lagged_content') {
            return true;
        }

        return ClassContentSession::where('teaching_plan_item_id', $item->id)
            ->where('stem_engineer_id', $teacher->id)
            ->where('institute', $teacher->institute)
            ->whereIn('status', ['partially_completed', 'cancelled'])
            ->exists();
    }

    private function teacherPassedPrepKeys(User $teacher)
    {
        return AiQuizAttempt::where('teacher_id', $teacher->id)
            ->where('attempt_type', self::AI_TEACHER_ATTEMPT_TYPE)
            ->where('status', 'passed')
            ->get(['content_id', 'grade_level'])
            ->flatMap(function ($attempt) {
                $gradeLevel = $this->gradeLevelFromClass($attempt->grade_level);

                return collect([
                    $this->aiPrepPassKey((int) $attempt->content_id, $gradeLevel),
                    $this->aiPrepPassKey((int) $attempt->content_id, null),
                ]);
            })
            ->filter()
            ->unique()
            ->values();
    }

    private function aiPrepPassKey(?int $contentId, ?string $gradeLevel): ?string
    {
        if (!$contentId) {
            return null;
        }

        return $contentId . '|' . ($this->gradeLevelFromClass($gradeLevel) ?: 'all');
    }

    private function teacherContentRequiresAiTraining(User $teacher, Content $content): bool
    {
        return TeachingPlanItem::with(['week', 'plan'])
            ->where('content_id', $content->id)
            ->whereHas('week', function ($query) {
                $query->whereNotNull('release_date');
            })
            ->whereHas('plan', function ($query) use ($teacher) {
                $query->where('is_template', false)
                    ->where('institute', $teacher->institute)
                    ->whereNotNull('ai_training_start_date')
                    ->whereIn('status', ['active', 'completed']);
            })
            ->whereIn('status', ['released', 'completed'])
            ->get()
            ->contains(fn ($item) => $this->teachingPlanItemRequiresAiTraining($item));
    }

    private function studentContentRequiresAiReview(Student $student, Content $content): bool
    {
        return $this->studentAiReviewRequiredContentIds($student, collect([$content->id]))
            ->contains((int) $content->id);
    }

    private function studentAiReviewRequiredContentIds(Student $student, $contentIds)
    {
        $contentIds = collect($contentIds)
            ->filter()
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values();

        if ($contentIds->isEmpty()) {
            return collect();
        }

        $assignedClass = $this->studentClassName($student);
        $studentGrade = $this->studentGradeName($student);

        return TeachingPlanItem::with(['week', 'plan'])
            ->whereIn('content_id', $contentIds)
            ->whereHas('week', function ($query) {
                $query->whereNotNull('release_date');
            })
            ->whereHas('plan', function ($query) use ($student, $assignedClass, $studentGrade) {
                $query->where('is_template', false)
                    ->where('institute', $student->institute)
                    ->whereNotNull('ai_training_start_date')
                    ->whereIn('status', ['active', 'completed'])
                    ->where(function ($classQuery) use ($assignedClass, $studentGrade) {
                        $classQuery
                            ->whereRaw(
                                "REPLACE(TRIM(class), '  ', ' ') = ?",
                                [$assignedClass]
                            )
                            ->orWhereRaw(
                                "REPLACE(TRIM(CONCAT(COALESCE(class, ''), ' ', COALESCE(section, ''))), '  ', ' ') = ?",
                                [$assignedClass]
                            )
                            ->orWhere(function ($gradeQuery) use ($studentGrade) {
                                $gradeQuery
                                    ->whereRaw("REPLACE(TRIM(class), '  ', ' ') = ?", [$studentGrade])
                                    ->where(function ($sectionQuery) {
                                        $sectionQuery
                                            ->whereNull('section')
                                            ->orWhereRaw("TRIM(COALESCE(section, '')) = ''")
                                            ->orWhereRaw("LOWER(TRIM(section)) = 'combined'");
                                    });
                            });
                    });
            })
            ->whereIn('status', ['released', 'completed'])
            ->get()
            ->filter(fn ($item) => $this->teachingPlanItemRequiresAiTraining($item))
            ->pluck('content_id')
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values();
    }

    private function planAiTrainingStartDate(TeachingPlan $plan): ?string
    {
        return $plan->ai_training_start_date
            ? \Carbon\Carbon::parse($plan->ai_training_start_date)->toDateString()
            : null;
    }

    private function aiTrainingRolloutStartDate(): string
    {
        return \Carbon\Carbon::parse(config('ai.content.auto_generation_start_date', '2026-07-31'))->toDateString();
    }

    private function generatedAiSummaryForContent(int $contentId): ?AiContentSummary
    {
        return AiContentSummary::where('content_id', $contentId)
            ->where('status', 'generated')
            ->first();
    }

    private function generatedAiSummaryForContentRecord(Content $content): ?AiContentSummary
    {
        $directSummary = $content->aiSummary;

        if ($directSummary && $directSummary->status == 'generated') {
            return $directSummary;
        }

        $sourceSummary = $content->courseContent?->sourceTemplateContent?->aiSummary;

        return $sourceSummary && $sourceSummary->status == 'generated'
            ? $sourceSummary
            : null;
    }

    private function studentAccessibleContent(Student $student, int $contentId): ?Content
    {
        $contentIds = $this->studentAvailableContentIds($student);

        if (!$contentIds->contains((int) $contentId)) {
            return null;
        }

        return Content::with(['aiSummary', 'courseContent.sourceTemplateContent.aiSummary'])
            ->where('id', $contentId)
            ->where('status', 1)
            ->first();
    }

    private function teacherAccessibleContent(User $teacher, int $contentId): ?Content
    {
        $releasedContentIds = TeachingPlanItem::whereIn('status', ['released', 'completed'])
            ->where('content_id', $contentId)
            ->whereHas('plan', function ($query) use ($teacher) {
                $query->where('institute', $teacher->institute)
                    ->whereIn('status', ['active', 'completed']);
            })
            ->pluck('content_id');

        if (!$releasedContentIds->contains((int) $contentId)) {
            return null;
        }

        return Content::with(['aiSummary', 'courseContent.sourceTemplateContent.aiSummary'])
            ->where('id', $contentId)
            ->where('status', 1)
            ->first();
    }

    private function studentQuizForContent(Content $content, AiContentSummary $summary, ?string $gradeLevel = null): AiQuiz
    {
        return $this->aiQuizForContent($content, $summary, 'student', $gradeLevel);
    }

    private function aiQuizForContent(Content $content, AiContentSummary $summary, string $audience, ?string $gradeLevel = null): AiQuiz
    {
        $quizContent = $this->aiQuizOwnerContent($content);
        $gradeLevel = $this->gradeLevelFromClass($gradeLevel);
        $passingRatio = $audience == 'teacher'
            ? $this->teacherAiPassingPercentage() / 100
            : $this->studentAiPassingPercentage() / 100;

        $quiz = AiQuiz::firstOrCreate(
            [
                'content_id' => $quizContent->id,
                'audience' => $audience,
                'grade_level' => $gradeLevel,
                'status' => 'active',
            ],
            [
                'provider' => $summary->provider,
                'model' => $summary->model,
                'title' => ($audience == 'teacher' ? 'AI Prep - ' : 'AI Review - ') . ($gradeLevel ? $gradeLevel . ' - ' : '') . $quizContent->content_title,
                'instructions' => $audience == 'teacher'
                    ? 'Answer these prep questions before teaching this lesson.'
                    : 'Answer these questions after reviewing the completed lesson.',
                'total_marks' => 0,
                'passing_marks' => 0,
            ]
        );

        if ($quiz->questions()->exists()) {
            $this->ensureAiQuizQuestionsAreMcq($quiz, $summary);
            $quiz->refresh();

            if ($quiz->total_marks > 0) {
                $quiz->update([
                    'passing_marks' => (int) ceil($quiz->total_marks * $passingRatio),
                ]);
            }

            return $quiz->load('questions');
        }

        $seeds = $this->mcqSeedsForSummary($summary);

        $totalMarks = 0;

        foreach ($seeds as $index => $seed) {
            $marks = max(1, (int) ($seed['marks'] ?? 1));
            $totalMarks += $marks;

            AiQuizQuestion::create([
                'ai_quiz_id' => $quiz->id,
                'question_order' => $index + 1,
                'question_type' => 'mcq',
                'question_text' => $seed['question'],
                'options' => $seed['options'],
                'expected_answer' => $seed['correct_answer'],
                'marks' => $marks,
            ]);
        }

        $quiz->update([
            'total_marks' => $totalMarks,
            'passing_marks' => (int) ceil($totalMarks * $passingRatio),
        ]);

        return $quiz->load('questions');
    }

    private function ensureAiQuizQuestionsAreMcq(AiQuiz $quiz, AiContentSummary $summary): void
    {
        $questions = $quiz->questions()->get();
        $hasOnlyValidMcq = $questions->isNotEmpty()
            && $questions->every(function ($question) {
                return $question->question_type === 'mcq'
                    && is_array($question->options)
                    && count($question->options) === 4
                    && filled($question->expected_answer)
                    && in_array($question->expected_answer, $question->options, true);
            });

        if ($hasOnlyValidMcq) {
            return;
        }

        $quiz->questions()->delete();
        $totalMarks = 0;

        foreach ($this->mcqSeedsForSummary($summary) as $index => $seed) {
            $marks = max(1, (int) ($seed['marks'] ?? 1));
            $totalMarks += $marks;

            AiQuizQuestion::create([
                'ai_quiz_id' => $quiz->id,
                'question_order' => $index + 1,
                'question_type' => 'mcq',
                'question_text' => $seed['question'],
                'options' => $seed['options'],
                'expected_answer' => $seed['correct_answer'],
                'marks' => $marks,
            ]);
        }

        $quiz->update(['total_marks' => $totalMarks]);
    }

    private function mcqSeedsForSummary(AiContentSummary $summary): array
    {
        $seeds = collect($summary->quiz_seed ?? [])
            ->map(fn ($seed) => $this->normalizeMcqSeed((array) $seed))
            ->filter()
            ->values()
            ->all();

        if (count($seeds) >= 5) {
            return array_slice($seeds, 0, 5);
        }

        return array_slice(array_merge($seeds, $this->fallbackMcqSeedsForSummary($summary)), 0, 5);
    }

    private function normalizeMcqSeed(array $seed): ?array
    {
        $question = trim((string) ($seed['question'] ?? ''));
        $options = array_values(array_filter(array_map(
            fn ($option) => trim((string) $option),
            (array) ($seed['options'] ?? [])
        )));
        $correctAnswer = trim((string) ($seed['correct_answer'] ?? $seed['expected_answer'] ?? ''));

        if ($question === '' || count($options) !== 4 || $correctAnswer === '') {
            return null;
        }

        if (!in_array($correctAnswer, $options, true)) {
            return null;
        }

        return [
            'question' => $question,
            'options' => $options,
            'correct_answer' => $correctAnswer,
            'marks' => max(1, (int) ($seed['marks'] ?? 1)),
        ];
    }

    private function fallbackMcqSeedsForSummary(AiContentSummary $summary): array
    {
        $points = collect($summary->key_points ?? [])
            ->map(fn ($point) => trim((string) $point))
            ->filter()
            ->values();

        if ($points->isEmpty() && filled($summary->summary)) {
            $points = collect(preg_split('/(?<=[.!?])\s+/', strip_tags((string) $summary->summary)))
                ->map(fn ($point) => trim($point))
                ->filter()
                ->take(8)
                ->values();
        }

        $genericDistractors = collect([
            'It is not related to this lesson.',
            'It explains only the certificate workflow.',
            'It is mainly about login permissions.',
            'It describes unrelated administrative setup.',
            'It focuses only on payment settings.',
        ]);

        if ($points->isEmpty()) {
            $points = collect([
                'The lesson explains an important STEM concept.',
                'The lesson connects theory with practical learning.',
                'The lesson supports project-based understanding.',
                'The lesson includes key ideas students should remember.',
                'The lesson is part of the InnovatEdge learning sequence.',
            ]);
        }

        return $points->take(5)->map(function ($point, $index) use ($points, $genericDistractors) {
            $distractors = $points
                ->reject(fn ($candidate) => $candidate === $point)
                ->take(3)
                ->merge($genericDistractors)
                ->unique()
                ->take(3)
                ->values()
                ->all();

            $options = array_values(array_slice(array_merge([$point], $distractors), 0, 4));

            while (count($options) < 4) {
                $options[] = 'None of the above statements match this lesson.';
            }

            return [
                'question' => 'Which statement is an important point from this lesson?',
                'options' => $options,
                'correct_answer' => $point,
                'marks' => 1,
            ];
        })->all();
    }

    private function evaluateMcqQuizAttempt($questions, $answers): array
    {
        $score = 0;
        $totalMarks = max(1, (int) $questions->sum('marks'));
        $feedback = [];

        foreach ($questions as $question) {
            $selected = trim((string) $answers->get($question->id, ''));
            $correct = trim((string) $question->expected_answer);
            $isCorrect = $selected !== '' && hash_equals($correct, $selected);
            $questionScore = $isCorrect ? (int) $question->marks : 0;
            $score += $questionScore;

            $feedback[] = [
                'question_id' => $question->id,
                'question_order' => $question->question_order,
                'score' => $questionScore,
                'feedback' => $isCorrect ? 'Correct answer.' : 'Incorrect answer.',
            ];
        }

        $percentage = round(($score / $totalMarks) * 100, 2);

        return [
            'score' => $score,
            'total_marks' => $totalMarks,
            'percentage' => $percentage,
            'feedback' => 'MCQ quiz evaluated automatically. Score: ' . $percentage . '%.',
            'answer_feedback' => $feedback,
        ];
    }

    private function aiQuizOwnerContent(Content $content): Content
    {
        $sourceContent = $content->courseContent?->sourceTemplateContent;

        if ($sourceContent && ($sourceContent->aiSummary || $sourceContent->hasAiPdfMaterial())) {
            return $sourceContent;
        }

        return $content;
    }

    private function teacherAiPassingPercentage(): float
    {
        return (float) config('ai.content.teacher_passing_percentage', 50);
    }

    private function studentAiPassingPercentage(): float
    {
        return (float) config('ai.content.student_passing_percentage', 60);
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

    private function studentAvailableContentIds(Student $student)
    {
        $assignedClass = $this->studentClassName($student);
        $studentGrade = $this->studentGradeName($student);

        $teachingPlanContentIds = TeachingPlanItem::where('status', 'completed')
            ->whereNotNull('content_id')
            ->whereHas('plan', function ($query) use ($student, $assignedClass, $studentGrade) {
                $query->where('is_template', false)
                    ->where('institute', $student->institute)
                    ->whereIn('status', ['active', 'completed'])
                    ->where(function ($classQuery) use ($assignedClass, $studentGrade) {
                        $classQuery
                            ->whereRaw(
                                "REPLACE(TRIM(class), '  ', ' ') = ?",
                                [$assignedClass]
                            )
                            ->orWhereRaw(
                                "REPLACE(TRIM(CONCAT(COALESCE(class, ''), ' ', COALESCE(section, ''))), '  ', ' ') = ?",
                                [$assignedClass]
                            )
                            ->orWhere(function ($gradeQuery) use ($studentGrade) {
                                $gradeQuery
                                    ->whereRaw("REPLACE(TRIM(class), '  ', ' ') = ?", [$studentGrade])
                                    ->where(function ($sectionQuery) {
                                        $sectionQuery
                                            ->whereNull('section')
                                            ->orWhereRaw("TRIM(COALESCE(section, '')) = ''")
                                            ->orWhereRaw("LOWER(TRIM(section)) = 'combined'");
                                    });
                            });
                    });
            })
            ->whereHas('content', function ($query) {
                $query->where('status', 1);
            })
            ->pluck('content_id')
            ->unique()
            ->values();

        $legacyCourseIds = Course::where('institute', $student->institute)
            ->where(function ($query) use ($assignedClass, $studentGrade) {
                $query
                    ->whereRaw(
                        "REPLACE(TRIM(assigned_class), '  ', ' ') = ?",
                        [$assignedClass]
                    )
                    ->orWhereRaw(
                        "REPLACE(TRIM(assigned_class), '  ', ' ') = ?",
                        [$studentGrade]
                    );
            })
            ->pluck('id');

        $legacyReleasedContentIds = Content::whereIn('course_id', $legacyCourseIds)
            ->where('status', 1)
            ->where('is_released', true)
            ->pluck('id');

        return $teachingPlanContentIds
            ->merge($legacyReleasedContentIds)
            ->unique()
            ->values();
    }

    private function studentClassName(Student $student)
    {
        return preg_replace('/\s+/', ' ', trim($student->class . ' ' . $student->section));
    }

    private function studentGradeName(Student $student): ?string
    {
        return $this->gradeLevelFromClass($student->class);
    }

    private function teacherAiGradeForContent(User $teacher, Content $content): ?string
    {
        $requestedGrade = $this->gradeLevelFromClass(request()->query('grade'));

        if ($requestedGrade) {
            return $requestedGrade;
        }

        $item = TeachingPlanItem::with(['week', 'plan'])
            ->where('content_id', $content->id)
            ->whereIn('status', ['released', 'completed'])
            ->whereHas('plan', function ($query) use ($teacher) {
                $query->where('is_template', false)
                    ->where('institute', $teacher->institute)
                    ->whereNotNull('ai_training_start_date')
                    ->whereIn('status', ['active', 'completed']);
            })
            ->whereHas('week', fn ($query) => $query->whereNotNull('release_date'))
            ->orderBy('teaching_plan_week_id')
            ->orderBy('sort_order')
            ->get()
            ->first(fn ($teachingItem) => $this->teachingPlanItemRequiresAiTraining($teachingItem));

        return $this->gradeLevelFromClass($item?->plan?->class);
    }

    private function gradeLevelFromClass(?string $class): ?string
    {
        $class = preg_replace('/\s+/', ' ', trim((string) $class));

        return $class !== '' ? $class : null;
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
            !$this->assessmentWindowIsOpen($assessment)
        ) {
            return false;
        }

        return $this->studentClassName($student) ==
            preg_replace('/\s+/', ' ', trim((string) $assessment->assigned_class));
    }

    private function assessmentWindowIsOpen(Assessment $assessment): bool
    {
        if ($assessment->assessment_date) {
            $today = today()->toDateString();

            if ($assessment->assessment_date > $today) {
                return false;
            }

            if (($assessment->start_time || $assessment->end_time) && $assessment->assessment_date < $today) {
                return false;
            }
        }

        if ($assessment->start_time && now()->format('H:i:s') < $assessment->start_time) {
            return false;
        }

        if ($assessment->end_time && now()->format('H:i:s') > $assessment->end_time) {
            return false;
        }

        return true;
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

    public function startClassSession(Request $request)
    {
        $request->validate([
            'teaching_plan_item_id' => 'required|exists:teaching_plan_items,id',
            'session_day' => 'required|string|max:20',
            'session_date' => 'required|date',
            'start_time' => 'required',
        ]);

        $teacher = User::findOrFail(session('user_id'));
        $this->autoEndExpiredClassSessions($teacher);

        $item = TeachingPlanItem::with([
                'plan',
                'week',
                'course',
                'content.aiSummary',
                'content.courseContent.sourceTemplateContent.aiSummary',
                'courseContent',
            ])
            ->where('status', 'released')
            ->whereHas('plan', function ($query) use ($teacher) {
                $query->where('institute', $teacher->institute)
                    ->where('status', 'active');
            })
            ->findOrFail($request->teaching_plan_item_id);

        $plan = $item->plan;

        if (!$item->content || !$item->content->file_path || $item->content->status != 1) {
            return redirect()->back()
                ->with('error', 'This Teaching Plan topic is missing its active content file.');
        }

        if (!$item->week || $item->week->status !== 'released') {
            return redirect()->back()
                ->with('error', 'Only currently released Teaching Plan topics can be started.');
        }

        $requiresAiPrep = $this->teachingPlanItemRequiresAiTraining($item)
            && !$this->teachingPlanItemBypassesAiPrepForTeacher($item, $teacher);

        if ($requiresAiPrep && !$this->generatedAiSummaryForContentRecord($item->content)) {
            return redirect()->back()
                ->with('error', 'AI prep is still being prepared for this content. Please try again shortly.');
        }

        if ($requiresAiPrep && $this->teacherNeedsAiPrep($item->content, $teacher->id, $this->gradeLevelFromClass($item->plan?->class))) {
            return redirect()
                ->route('teacher.ai-prep', ['id' => $item->content->id, 'grade' => $this->gradeLevelFromClass($item->plan?->class)])
                ->with('error', 'Please pass the training prep assessment before starting this session.');
        }

        $existingSession = ClassContentSession::where('stem_engineer_id', $teacher->id)
            ->where('status', 'in_progress')
            ->first();

        if ($existingSession) {
            return redirect()->back()
                ->with('error', 'A session is already running.');
        }

        $activeClassSession = ClassContentSession::where('institute', $teacher->institute)
            ->where('class', $plan->class)
            ->where('status', 'in_progress')
            ->where(function ($query) use ($plan) {
                if (filled($plan->section)) {
                    $query->where('section', $plan->section);
                } else {
                    $query->whereNull('section')
                        ->orWhere('section', '');
                }
            })
            ->first();

        if ($activeClassSession) {
            return redirect()->back()
                ->with('error', trim($plan->class . ' ' . $plan->section) . ' already has an active session.');
        }

        $session = ClassContentSession::create([
            'institute' => $teacher->institute,
            'course_id' => $item->course_id,
            'course_content_id' => $item->course_content_id,
            'teaching_plan_id' => $plan->id,
            'teaching_plan_week_id' => $item->teaching_plan_week_id,
            'teaching_plan_item_id' => $item->id,
            'content_id' => $item->content_id,
            'stem_engineer_id' => $teacher->id,
            'class' => $plan->class,
            'section' => $plan->section,
            'session_day' => $request->session_day ?: \Carbon\Carbon::parse($request->session_date)->format('l'),
            'session_date' => $request->session_date,
            'start_time' => $request->start_time,
            'started_at' => now(),
            'status' => 'in_progress',
            'planned_topic' => $item->content->content_title ?? null,
        ]);

        return redirect()
            ->back()
            ->with('success', 'Class session started successfully. The lesson preview will open in a separate window.')
            ->with('previewContentUrl', route('teacher.session.content', $session->content_id));
    }

    public function teacherSessionContent($contentId)
    {
        $teacher = User::findOrFail(session('user_id'));

        $content = Content::findOrFail($contentId);

        $hasAssignedSession = ClassContentSession::where('content_id', $content->id)
            ->where('stem_engineer_id', $teacher->id)
            ->where('institute', $teacher->institute)
            ->exists();

        if (!$hasAssignedSession) {
            abort(403, 'This content session is assigned to another STEM Engineer.');
        }

        return redirect()->route('content.preview', [$content->id, 'teacher']);
    }


    public function endClassSession($sessionId)
    {
        $teacher = User::findOrFail(session('user_id'));

        $session = ClassContentSession::with('teachingPlanItem')->findOrFail($sessionId);

        if (
            $session->stem_engineer_id != $teacher->id ||
            $session->institute != $teacher->institute
        ) {
            abort(403, 'This class session is assigned to another STEM Engineer.');
        }

        if (in_array($session->status, ['completed', 'partially_completed', 'cancelled'])) {

            return redirect()->back()
                ->with(
                    'error',
                    'Session already completed.'
                );
        }

        $endedAt = now();

        $actualDuration = strtotime($endedAt) - strtotime($session->started_at);
        $minimumSessionSeconds = 30 * 60;
        $maximumSessionSeconds = 50 * 60;
        $autoEnded = request()->boolean('auto_ended') && $actualDuration >= ($maximumSessionSeconds - 5);

        $status = $autoEnded ? 'partially_completed' : request('status', 'completed');

        if ($status !== 'cancelled' && $actualDuration < $minimumSessionSeconds) {
            return redirect()->back()
                ->with('error', 'Sessions can be ended as completed or partially completed only after at least 30 minutes. Use Cancelled only if the session did not proceed.');
        }

        $duration = min($actualDuration, $maximumSessionSeconds);
        $remarks = request('remarks');

        if ($autoEnded) {
            $remarks = trim(($remarks ? $remarks . "\n" : '') . 'System note: Session automatically ended after reaching the 50 minute maximum window.');
        } elseif ($actualDuration > $maximumSessionSeconds) {
            $remarks = trim(($remarks ? $remarks . "\n" : '') . 'System note: Session exceeded the 50 minute maximum window. Duration was capped at 50 minutes for reporting.');
        }

        if (
            $status == 'completed' &&
            (
                !$session->teachingPlanItem ||
                $session->teachingPlanItem->status !== 'released'
            )
        ) {
            return redirect()->back()
                ->with('error', 'Only released Teaching Plan content can be completed.');
        }

        $session->update([

            'ended_at' => $endedAt,
            'end_time' => now()->format('H:i:s'),

            'duration_seconds' => $duration,

            'status' => $status,

            'delivered_topic' => request('delivered_topic', $session->planned_topic),

            'delivered_content_id' => $session->content_id,

            'remarks' => $remarks,

        ]);

        if ($status == 'completed' && $session->teachingPlanItem) {
            $itemCompleted = app(TeachingPlanReleaseService::class)
                ->markItemCompleted($session->teachingPlanItem);

            if ($itemCompleted && $session->content_id) {
                $releasedContent = Content::where('id', $session->content_id)
                    ->where('institute', $teacher->institute)
                    ->first();

                if ($releasedContent) {
                    $wasReleased = (bool) $releasedContent->is_released;
                    $releasedContent->update(['is_released' => true]);

                    if (!$wasReleased) {
                        app(\App\Services\LmsNotificationService::class)
                            ->notifyStudentsOfReleasedContent($releasedContent->fresh());
                    }
                }
            }
        }

        return redirect()->back()
            ->with(
                'success',
                'Class session ended successfully.'
            )
            ->with('sessionCompletionCelebration', !$autoEnded && in_array($status, ['completed', 'partially_completed']));

    }

    public function classSessionReport(Request $request)
    {
        $reportType = $request->route('reportType') ?? $request->query('report_type', 'weekly');
        $sectionPager = null;
        $currentInstitute = null;

        if (session('user_role') == 'Admin') {
            ['currentInstitute' => $currentInstitute, 'sectionPager' => $sectionPager] =
                $this->buildInstituteSectionPager(
                    $request,
                    $request->route()?->getName() ?: 'admin.class-session.report.weekly'
                );

            $request->attributes->set('section_institute', $currentInstitute);
        }

        $sessions = $this->classSessionReportQuery($request)
            ->latest()
            ->paginate(30)
            ->withQueryString();

        return view('class-session-report', compact('sessions', 'reportType', 'sectionPager'));
    }

    public function downloadClassSessionReportPdf(Request $request, GeminiAiService $ai)
    {
        $reportType = $request->route('reportType') ?? 'weekly';
        $sectionPager = null;
        $currentInstitute = null;

        if (session('user_role') == 'Admin') {
            ['currentInstitute' => $currentInstitute, 'sectionPager' => $sectionPager] =
                $this->buildInstituteSectionPager(
                    $request,
                    $reportType == 'daily'
                        ? 'admin.class-session.report.daily.download'
                        : 'admin.class-session.report.weekly.download'
                );

            $request->attributes->set('section_institute', $currentInstitute);
        }

        $sessions = $this->classSessionReportQuery($request)
            ->orderBy('institute')
            ->orderBy('class')
            ->orderBy('section')
            ->orderBy('session_date')
            ->get();

        $scope = session('user_role') == 'InstituteAdmin'
            ? session('user_institute')
            : ($currentInstitute ?: 'All Institutes');

        $periodLabel = $reportType == 'daily'
            ? \Carbon\Carbon::parse($request->input('report_date', now()->toDateString()))->format('d M Y')
            : (
                ($request->filled('from_date') || $request->filled('to_date'))
                    ? trim(($request->filled('from_date') ? \Carbon\Carbon::parse($request->from_date)->format('d M Y') : 'Start') . ' - ' . ($request->filled('to_date') ? \Carbon\Carbon::parse($request->to_date)->format('d M Y') : 'Today'))
                    : 'All available sessions'
            );

        $totalSessions = $sessions->count();
        $completedSessions = $sessions->where('status', 'completed')->count();
        $partialSessions = $sessions->where('status', 'partially_completed')->count();
        $cancelledSessions = $sessions->whereIn('status', ['cancelled', 'skipped'])->count();
        $unfinishedSessions = $sessions->filter(fn ($session) => $session->status == 'in_progress' || !$session->ended_at)->count();

        $metrics = [
            'scope' => $scope,
            'period' => $periodLabel,
            'total_sessions' => $totalSessions,
            'completed_sessions' => $completedSessions,
            'partially_completed_sessions' => $partialSessions,
            'cancelled_or_skipped_sessions' => $cancelledSessions,
            'unfinished_sessions' => $unfinishedSessions,
            'completion_rate' => $totalSessions ? round(($completedSessions / $totalSessions) * 100, 2) : 0,
            'teaching_hours' => round(($sessions->sum('duration_seconds') ?? 0) / 3600, 2),
            'unique_classes' => $sessions->map(fn ($session) => trim(($session->class ?? '') . ' ' . ($session->section ?? '')))->filter()->unique()->count(),
            'stem_engineers_involved' => $sessions->pluck('stem_engineer_id')->filter()->unique()->count(),
        ];

        $tableRows = $sessions
            ->take(45)
            ->map(fn ($session) => [
                trim(($session->class ?? $session->schoolClass->class_name ?? 'N/A') . ' ' . ($session->section ?? $session->schoolClass->section ?? '')),
                $session->institute ?? $session->schoolClass->institute ?? 'N/A',
                $session->course->course_title ?? 'N/A',
                $session->planned_topic ?? $session->content->content_title ?? 'No Content',
                $session->stemEngineer->name ?? 'Deleted Engineer',
                $session->session_date ? \Carbon\Carbon::parse($session->session_date)->format('d M Y') : '-',
                gmdate('H:i:s', $session->duration_seconds ?? 0),
                ucwords(str_replace('_', ' ', $session->status)),
            ])
            ->values()
            ->all();

        $visuals = [
            [
                'title' => 'Session Completion',
                'labels' => ['Completed', 'Partial', 'Cancelled/Skipped', 'Unfinished'],
                'values' => [$completedSessions, $partialSessions, $cancelledSessions, $unfinishedSessions],
            ],
            [
                'title' => 'Teaching Delivery',
                'labels' => ['Teaching Hours', 'STEM Engineers', 'Classes'],
                'values' => [$metrics['teaching_hours'], $metrics['stem_engineers_involved'], $metrics['unique_classes']],
            ],
        ];

        $title = $reportType == 'daily'
            ? 'Daily Session Report'
            : 'Weekly Session Report';

        try {
            $insights = $ai->generateReportInsights($title, $metrics);
        } catch (\Throwable $exception) {
            return redirect()
                ->back()
                ->with('error', 'AI session report PDF could not be generated: ' . $exception->getMessage());
        }

        $pdf = Pdf::loadView('pdf.generated-lms-report', [
            'title' => $title,
            'scope' => $scope,
            'periodLabel' => $periodLabel,
            'metrics' => $metrics,
            'tableTitle' => 'Session Execution Data',
            'tableHeaders' => ['Class', 'Institute', 'Course', 'Planned Content', 'STEM Engineer', 'Date', 'Duration', 'Status'],
            'tableRows' => $tableRows,
            'visuals' => $visuals,
            'insights' => $insights,
        ])->setPaper('a4', 'portrait');

        return $pdf->download((string) str($title)->slug('_') . '_' . now()->format('Ymd_His') . '.pdf');
    }

    private function classSessionReportQuery(Request $request)
    {
        $reportType = $request->route('reportType') ?? $request->query('report_type', 'weekly');

        return ClassContentSession::with([
                'schoolClass',
                'content',
                'course',
                'teachingPlan',
                'stemEngineer',
            ])
            ->when($reportType == 'daily' && $request->filled('report_date'), function ($query) use ($request) {
                $query->whereDate('session_date', $request->report_date);
            })
            ->when($request->filled('from_date'), function ($query) use ($request) {
                $query->whereDate('session_date', '>=', $request->from_date);
            })
            ->when($request->filled('to_date'), function ($query) use ($request) {
                $query->whereDate('session_date', '<=', $request->to_date);
            })
            ->when(session('user_role') == 'Admin' && $request->attributes->get('section_institute'), function ($query) use ($request) {
                $query->where('institute', $request->attributes->get('section_institute'));
            })
            ->when(session('user_role') == 'Admin' && $request->filled('institute'), function ($query) use ($request) {
                $query->where('institute', $request->institute);
            })
            ->when(session('user_role') == 'InstituteAdmin', function ($query) {
                $query->where('institute', session('user_institute'));
            });
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
            ->where('status', 'approved')
            ->latest()
            ->firstOrFail();

        $pdf = Pdf::loadView('student.student-certificate', [
            'certificate' => $certificate,
            'student' => $student,
        ])->setPaper('a4', 'landscape');

        return $pdf->stream('certificate-' . $certificate->certificate_code . '.pdf');
    }

}
