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
use App\Models\LmsNotification;
use App\Models\User;
use App\Models\Course;
use App\Models\ClassContentSession;
use App\Models\TeachingPlan;
use App\Models\TeachingPlanItem;
use App\Models\AiContentSummary;
use App\Models\AiComponentContentProfile;
use App\Models\AiQuiz;
use App\Models\AiQuizAnswer;
use App\Models\AiQuizAttempt;
use App\Models\AiQuizQuestion;
use App\Services\TeachingPlanReleaseService;
use App\Services\Ai\GeminiAiService;
use App\Services\FirebasePushService;
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
        if (session('user_role')) {
            return redirect()->route(match (session('user_role')) {
                'Manager' => 'manager.dashboard',
                'Principal' => 'principal.dashboard',
                'Teacher' => 'teacher.dashboard',
                default => 'admin.dashboard',
            });
        }

        return view('admin-login', [
            'portalRole' => request('portal'),
        ]);
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
            $items[] = [
                'title' => 'Principal Management',
                'description' => 'Assign one principal account to each institute.',
                'icon' => 'fa-user-tie',
                'route' => route('principals'),
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
                    'title' => 'Monthly Session Report',
                    'description' => 'Review sessions across a selected month.',
                    'icon' => 'fa-calendar-days',
                    'route' => route('admin.class-session.report.monthly'),
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
                    'title' => 'Daily Student Performance',
                    'description' => 'Review student progress for a selected day.',
                    'icon' => 'fa-chart-simple',
                    'route' => route('reports.student-performance.daily'),
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

    public function managerDashboard()
    {
        return view('panel-report-summary-dashboard', [
            'title' => 'Manager Dashboard',
            'description' => 'Global reporting access across institutions and STEM Engineer performance.',
            'scopeLabel' => 'All institutions',
            'summaryItems' => [
                [
                    'label' => 'Institution Reports',
                    'cadence' => 'Daily, Weekly, Monthly',
                    'coverage' => 'Session execution and institution activity across every institute.',
                ],
                [
                    'title' => 'STEM Engineer Performance',
                    'label' => 'STEM Engineer Performance',
                    'cadence' => 'Weekly, Monthly',
                    'coverage' => 'Teaching consistency, conducted sessions, hours, and prep readiness.',
                ],
            ],
            'notes' => [
                'AI report PDFs are available inside the Reports section.',
                'Approvals, notifications, feedback, and password changes remain available from the sidebar.',
            ],
            'quickActions' => [
                [
                    'label' => 'Open Reports',
                    'icon' => 'fa-chart-line',
                    'route' => route('manager.reports.hub'),
                    'tone' => 'primary',
                ],
                [
                    'label' => 'AI PDFs',
                    'icon' => 'fa-file-pdf',
                    'route' => route('manager.reports.student-performance.weekly'),
                    'tone' => 'success',
                ],
                [
                    'label' => 'Approvals',
                    'icon' => 'fa-circle-check',
                    'route' => route('admin.approvals'),
                    'tone' => 'warning',
                ],
                [
                    'label' => 'Feedback',
                    'icon' => 'fa-comment-dots',
                    'route' => route('manager.feedback'),
                    'tone' => 'info',
                ],
            ],
        ]);
    }

    public function managerReportsHub()
    {
        return view('admin-feature-hub', [
            'title' => 'Manager Reports',
            'description' => 'Generate institution-wide reports and AI downloadable PDFs.',
            'items' => [
                [
                    'title' => 'Daily Institution Report',
                    'description' => 'Review all institution sessions for a selected day.',
                    'icon' => 'fa-calendar-day',
                    'route' => route('manager.class-session.report.daily'),
                ],
                [
                    'title' => 'Weekly Institution Report',
                    'description' => 'Review all institution sessions for a selected week.',
                    'icon' => 'fa-calendar-week',
                    'route' => route('manager.class-session.report.weekly'),
                ],
                [
                    'title' => 'Monthly Institution Report',
                    'description' => 'Review all institution sessions for a selected month.',
                    'icon' => 'fa-calendar-days',
                    'route' => route('manager.class-session.report.monthly'),
                ],
                [
                    'title' => 'Weekly STEM Engineer Performance',
                    'description' => 'Evaluate sessions, hours, and prep readiness weekly.',
                    'icon' => 'fa-person-chalkboard',
                    'route' => route('manager.reports.stem-engineer-performance.weekly'),
                ],
                [
                    'title' => 'Monthly STEM Engineer Performance',
                    'description' => 'Evaluate STEM Engineer consistency monthly.',
                    'icon' => 'fa-chart-pie',
                    'route' => route('manager.reports.stem-engineer-performance.monthly'),
                ],
            ],
        ]);
    }

    public function principalDashboard()
    {
        return view('panel-report-summary-dashboard', [
            'title' => 'Principal Dashboard',
            'description' => 'Institute-scoped reporting access for sessions and student performance.',
            'scopeLabel' => session('user_institute') ?: 'Assigned institute',
            'summaryItems' => [
                [
                    'label' => 'Session Reports',
                    'cadence' => 'Daily, Weekly, Monthly',
                    'coverage' => 'Session execution and class activity inside the assigned institute.',
                ],
                [
                    'label' => 'Student Performance',
                    'cadence' => 'Daily, Weekly, Monthly',
                    'coverage' => 'Student progress, assessment activity, and performance trends for the institute.',
                ],
            ],
            'notes' => [
                'Report exports are available inside the Reports section.',
                'Feedback and password changes remain available from the sidebar.',
            ],
            'quickActions' => [
                [
                    'label' => 'Session Reports',
                    'icon' => 'fa-calendar-check',
                    'route' => route('principal.class-session.report.daily'),
                    'tone' => 'primary',
                ],
                [
                    'label' => 'Student Reports',
                    'icon' => 'fa-user-graduate',
                    'route' => route('principal.reports.student-performance.weekly'),
                    'tone' => 'success',
                ],
                [
                    'label' => 'Feedback',
                    'icon' => 'fa-comment-dots',
                    'route' => route('principal.feedback'),
                    'tone' => 'info',
                ],
                [
                    'label' => 'Password',
                    'icon' => 'fa-key',
                    'route' => route('principal.change.password'),
                    'tone' => 'warning',
                ],
            ],
        ]);
    }

    public function principalReportsHub()
    {
        return view('admin-feature-hub', [
            'title' => 'Principal Reports',
            'description' => 'All reports are scoped to your assigned institute.',
            'items' => [
                [
                    'title' => 'Daily Session Report',
                    'description' => 'Review sessions conducted on one selected date.',
                    'icon' => 'fa-calendar-day',
                    'route' => route('principal.class-session.report.daily'),
                ],
                [
                    'title' => 'Weekly Session Report',
                    'description' => 'Review session execution across a selected week.',
                    'icon' => 'fa-calendar-week',
                    'route' => route('principal.class-session.report.weekly'),
                ],
                [
                    'title' => 'Monthly Session Report',
                    'description' => 'Review session execution across a selected month.',
                    'icon' => 'fa-calendar-days',
                    'route' => route('principal.class-session.report.monthly'),
                ],
                [
                    'title' => 'Daily Student Performance',
                    'description' => 'Review student performance for one selected date.',
                    'icon' => 'fa-chart-simple',
                    'route' => route('principal.reports.student-performance.daily'),
                ],
                [
                    'title' => 'Weekly Student Performance',
                    'description' => 'Review student performance for a selected week.',
                    'icon' => 'fa-chart-line',
                    'route' => route('principal.reports.student-performance.weekly'),
                ],
                [
                    'title' => 'Monthly Student Performance',
                    'description' => 'Review student performance for a selected month.',
                    'icon' => 'fa-chart-pie',
                    'route' => route('principal.reports.student-performance.monthly'),
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
        $managedInstitute = session('user_role') == 'InstituteAdmin' ? session('user_institute') : null;
        $selectedInstitute = session('user_role') === 'Admin'
            ? trim((string) $request->input('institute'))
            : $managedInstitute;
        $selectedStudentClass = trim((string) $request->input('student_class')) ?: null;
        $selectedStudentSection = trim((string) $request->input('student_section')) ?: null;
        $hasFilters = $request->filled('institute')
            || $request->filled('student_class')
            || $request->filled('student_section')
            || $request->filled('search');

        $instituteOptions = Institute::where('status', 1)->orderBy('institute_name')->pluck('institute_name');
        $classOptions = Student::when($selectedInstitute, function ($query) use ($selectedInstitute) {
                $query->where('institute', $selectedInstitute);
            })
            ->whereNotNull('class')
            ->where('class', '!=', '')
            ->orderBy('class')
            ->distinct()
            ->pluck('class');
        $sectionOptions = Student::when($selectedInstitute, function ($query) use ($selectedInstitute) {
                $query->where('institute', $selectedInstitute);
            })
            ->when($selectedStudentClass, function ($query) use ($selectedStudentClass) {
                $query->where('class', $selectedStudentClass);
            })
            ->whereNotNull('section')
            ->where('section', '!=', '')
            ->orderBy('section')
            ->distinct()
            ->pluck('section');

        $students = $hasFilters
            ? Student::when(session('user_role') == 'InstituteAdmin', function ($query) {
                    $query->where('institute', session('user_institute'));
                })
            ->when(session('user_role') == 'Admin' && $selectedInstitute, function ($query) use ($selectedInstitute) {
                $query->where('institute', $selectedInstitute);
            })
            ->when($selectedStudentClass, function ($query) use ($selectedStudentClass) {
                $query->where('class', $selectedStudentClass);
            })
            ->when($selectedStudentSection, function ($query) use ($selectedStudentSection) {
                $query->where('section', $selectedStudentSection);
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
            ->withQueryString()
            : collect();

        return view('students', [
            'students' => $students,
            'selectedStudentClassLabel' => $selectedStudentClass,
            'selectedStudentSectionLabel' => $selectedStudentSection,
            'studentManagementContext' => 'admin',
            'managedInstitute' => $managedInstitute,
            'showFilterPlaceholder' => !$hasFilters,
            'instituteOptions' => $instituteOptions,
            'classOptions' => $classOptions,
            'sectionOptions' => $sectionOptions,
            'sectionOptionsByInstituteClass' => $this->studentSectionOptionsByInstituteClass($managedInstitute),
        ]);
    }

    public function teacherStudentManagement(Request $request)
    {
        $teacher = User::findOrFail(session('user_id'));
        $search = $request->search;
        $hasFilters = $request->filled('search')
            || $request->filled('student_class')
            || $request->filled('student_section');
        $selectedStudentClass = trim((string) $request->input('student_class')) ?: null;
        $selectedStudentSection = trim((string) $request->input('student_section')) ?: null;
        $classSectionPager = null;
        $studentSectionPager = null;

        $classOptions = SchoolClass::where('institute', $teacher->institute)
            ->whereNotNull('class_name')
            ->orderBy('class_name')
            ->pluck('class_name')
            ->map(fn ($className) => trim((string) $className))
            ->filter()
            ->unique(fn ($className) => mb_strtolower($className))
            ->values();

        $sectionOptions = collect();
        if ($selectedStudentClass) {
            $sectionOptions = Student::where('institute', $teacher->institute)
                ->where('class', $selectedStudentClass)
                ->whereNotNull('section')
                ->orderBy('section')
                ->pluck('section')
                ->map(fn ($section) => trim((string) $section))
                ->filter()
                ->unique(fn ($section) => mb_strtolower($section))
                ->values();
        }

        $students = collect();

        if ($hasFilters) {
            $students = Student::where('institute', $teacher->institute)
                ->when($selectedStudentClass, function ($query) use ($selectedStudentClass) {
                    $query->where('class', $selectedStudentClass);
                })
                ->when($selectedStudentSection, function ($query) use ($selectedStudentSection) {
                    $query->where('section', $selectedStudentSection);
                })
                ->when($search, function ($query, $search) {
                    $query->where(function ($q) use ($search) {
                        $q->where('name', 'like', "%{$search}%")
                            ->orWhere('student_id', 'like', "%{$search}%")
                            ->orWhere('class', 'like', "%{$search}%")
                            ->orWhere('section', 'like', "%{$search}%");
                    });
                })
                ->orderBy('class')
                ->orderBy('section')
                ->orderBy('name')
                ->paginate(30)
                ->withQueryString();
        }

        return view('students', [
            'students' => $students,
            'sectionPager' => null,
            'classSectionPager' => $classSectionPager,
            'studentSectionPager' => $studentSectionPager,
            'selectedStudentClassLabel' => $selectedStudentClass,
            'selectedStudentSectionLabel' => $selectedStudentSection,
            'studentManagementContext' => 'teacher',
            'managedInstitute' => $teacher->institute,
            'showFilterPlaceholder' => !$hasFilters,
            'classOptions' => $classOptions,
            'sectionOptions' => $sectionOptions,
            'sectionOptionsByInstituteClass' => $this->studentSectionOptionsByInstituteClass($teacher->institute),
        ]);
    }

    private function studentSectionOptionsByInstituteClass(?string $institute = null): array
    {
        $options = [];

        SchoolClass::query()
            ->when($institute, fn ($query) => $query->where('institute', $institute))
            ->whereNotNull('class_name')
            ->whereNotNull('section')
            ->get(['institute', 'class_name', 'section'])
            ->each(function ($row) use (&$options) {
                $instituteKey = (string) $row->institute;
                $classKey = trim((string) $row->class_name);
                $section = trim((string) $row->section);
                if ($classKey !== '' && $section !== '') {
                    $options[$instituteKey][$classKey][] = $section;
                }
            });

        Student::query()
            ->when($institute, fn ($query) => $query->where('institute', $institute))
            ->whereNotNull('class')
            ->whereNotNull('section')
            ->get(['institute', 'class', 'section'])
            ->each(function ($row) use (&$options) {
                $instituteKey = (string) $row->institute;
                $classKey = trim((string) $row->class);
                $section = trim((string) $row->section);
                if ($classKey !== '' && $section !== '') {
                    $options[$instituteKey][$classKey][] = $section;
                }
            });

        foreach ($options as $instituteKey => $classes) {
            foreach ($classes as $classKey => $sections) {
                $options[$instituteKey][$classKey] = collect($sections)
                    ->filter()
                    ->unique()
                    ->sort()
                    ->values()
                    ->all();
            }
        }

        return $options;
    }

    private function buildStudentClassPager(Request $request, ?string $institute, string $routeName): array
    {
        if (!$institute) {
            return [
                'selectedClass' => null,
                'sectionPager' => null,
            ];
        }

        $classOptions = SchoolClass::where('institute', $institute)
            ->whereNotNull('class_name')
            ->orderBy('class_name')
            ->pluck('class_name')
            ->map(fn ($className) => trim((string) $className));

        $studentClassOptions = Student::where('institute', $institute)
            ->whereNotNull('class')
            ->select('class')
            ->distinct()
            ->orderBy('class')
            ->pluck('class')
            ->map(fn ($className) => trim((string) $className));

        $classOptions = $classOptions
            ->merge($studentClassOptions)
            ->filter()
            ->unique(fn ($className) => mb_strtolower($className))
            ->sort()
            ->values();

        if ($classOptions->isEmpty()) {
            return [
                'selectedClass' => null,
                'sectionPager' => null,
            ];
        }

        $requestedClass = $request->input('student_class');
        $requestedIndex = $requestedClass
            ? $classOptions->search($requestedClass)
            : false;

        $lastPage = $classOptions->count();
        $currentPage = $requestedIndex !== false
            ? $requestedIndex + 1
            : min(max((int) $request->input('class_page', 1), 1), $lastPage);

        $selectedClass = $classOptions->get($currentPage - 1);
        $previousClass = $currentPage > 1 ? $classOptions->get($currentPage - 2) : null;
        $nextClass = $currentPage < $lastPage ? $classOptions->get($currentPage) : null;
        $query = $request->except(['class_page', 'student_class', 'student_section_page', 'student_section', 'page']);

        return [
            'selectedClass' => $selectedClass,
            'sectionPager' => [
                'current_label' => 'Class ' . $selectedClass,
                'current_page' => $currentPage,
                'last_page' => $lastPage,
                'previous_label' => $previousClass ? 'Class ' . $previousClass : null,
                'next_label' => $nextClass ? 'Class ' . $nextClass : null,
                'previous_url' => $previousClass
                    ? route($routeName, array_merge($query, [
                        'class_page' => $currentPage - 1,
                        'student_class' => $previousClass,
                    ]))
                    : null,
                'next_url' => $nextClass
                    ? route($routeName, array_merge($query, [
                        'class_page' => $currentPage + 1,
                        'student_class' => $nextClass,
                    ]))
                    : null,
            ],
        ];
    }

    private function buildStudentSectionPager(Request $request, ?string $institute, ?string $className, string $routeName): array
    {
        if (!$institute || !$className) {
            return [
                'selectedSection' => null,
                'sectionPager' => null,
            ];
        }

        $sectionOptions = SchoolClass::where('institute', $institute)
            ->where('class_name', $className)
            ->whereNotNull('section')
            ->orderBy('section')
            ->pluck('section')
            ->map(fn ($section) => trim((string) $section));

        $studentSectionOptions = Student::where('institute', $institute)
            ->where('class', $className)
            ->whereNotNull('section')
            ->select('section')
            ->distinct()
            ->orderBy('section')
            ->pluck('section')
            ->map(fn ($section) => trim((string) $section));

        $sectionOptions = $sectionOptions
            ->merge($studentSectionOptions)
            ->filter()
            ->unique(fn ($section) => mb_strtolower($section))
            ->sort()
            ->values();

        if ($sectionOptions->isEmpty()) {
            return [
                'selectedSection' => null,
                'sectionPager' => null,
            ];
        }

        $requestedSection = $request->input('student_section');
        $requestedIndex = $requestedSection ? $sectionOptions->search($requestedSection) : false;
        $lastPage = $sectionOptions->count();
        $currentPage = $requestedIndex !== false
            ? $requestedIndex + 1
            : min(max((int) $request->input('student_section_page', 1), 1), $lastPage);

        $selectedSection = $sectionOptions->get($currentPage - 1);
        $previousSection = $currentPage > 1 ? $sectionOptions->get($currentPage - 2) : null;
        $nextSection = $currentPage < $lastPage ? $sectionOptions->get($currentPage) : null;
        $query = $request->except(['student_section_page', 'student_section', 'page']);

        return [
            'selectedSection' => $selectedSection,
            'sectionPager' => [
                'current_label' => 'Section ' . $selectedSection,
                'current_page' => $currentPage,
                'last_page' => $lastPage,
                'previous_label' => $previousSection ? 'Section ' . $previousSection : null,
                'next_label' => $nextSection ? 'Section ' . $nextSection : null,
                'previous_url' => $previousSection
                    ? route($routeName, array_merge($query, [
                        'student_section_page' => $currentPage - 1,
                        'student_section' => $previousSection,
                    ]))
                    : null,
                'next_url' => $nextSection
                    ? route($routeName, array_merge($query, [
                        'student_section_page' => $currentPage + 1,
                        'student_section' => $nextSection,
                    ]))
                    : null,
            ],
        ];
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
            : (session('user_role') == 'Teacher'
                ? User::findOrFail(session('user_id'))->institute
                : $request->institute);

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
            'email' => 'nullable|email|max:255',
            'guardian_name' => 'nullable|string|max:255',
            'is_robotics_club_member' => 'required|boolean',
            'password' => 'required|min:6',
        ]);

        $studentData = [
            'student_id' => $request->student_id,
            'name' => $request->name,
            'institute' => $institute,
            'class' => $request->class,
            'section' => $request->section,
            'contact' => $request->contact,
            'email' => $request->email,
            'guardian_name' => $request->guardian_name,
            'is_robotics_club_member' => $request->boolean('is_robotics_club_member'),
            'password' => Hash::make($request->password),
            'status' => 1,
            'profile_completed' => true,
        ];

        Student::create($studentData);

        return redirect()->back()->with('success', 'Student added successfully');
    }

    public function downloadStudentBulkTemplate()
    {
        $managedInstitute = null;

        if (session('user_role') == 'InstituteAdmin') {
            $managedInstitute = session('user_institute');
        } elseif (session('user_role') == 'Teacher') {
            $managedInstitute = User::findOrFail(session('user_id'))->institute;
        }

        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="student_bulk_upload_template.csv"',
        ];

        $callback = function () use ($managedInstitute) {
            $file = fopen('php://output', 'w');

            fputcsv($file, [
                'student_id',
                'name',
                'institute',
                'class',
                'section',
                'contact',
                'email',
                'guardian_name',
                'is_robotics_club_member',
                'password',
                'status',
            ]);

            fputcsv($file, [
                'STU001',
                'Student Name',
                $managedInstitute ?: 'Institute Name',
                'Class 10',
                'A',
                '9876543210',
                'student@example.com',
                'Guardian Name',
                'No',
                'Student@123',
                'Active',
            ]);

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    public function bulkUploadStudents(Request $request)
    {
        $request->validate(['students_csv' => 'required|file|mimes:csv,txt|max:5120']);
        $role = session('user_role');
        abort_unless(in_array($role, ['Admin', 'InstituteAdmin', 'Teacher'], true), 403);
        $institute = $role === 'Admin' ? null : ($role === 'Teacher'
            ? User::findOrFail(session('user_id'))->institute : session('user_institute'));
        abort_if($role !== 'Admin' && blank($institute), 403, 'An institute must be assigned first.');
        $result = app(\App\Services\StudentCsvImportService::class)->import($request->file('students_csv'), $institute);
        return redirect()->back()->with('success', $result['message'])->with('bulk_upload_errors', $result['errors']);
    }

    private function csvBoolean(?string $value): bool
    {
        return in_array(strtolower(trim((string) $value)), ['1', 'yes', 'y', 'true'], true);
    }

    private function csvStatus(?string $value): bool
    {
        return !in_array(strtolower(trim((string) $value)), ['0', 'inactive', 'disabled', 'false', 'no'], true);
    }

    public function updateStudent(Request $request, $id)
    {
        $student = Student::findOrFail($id);
        $institute = session('user_role') == 'InstituteAdmin'
            ? session('user_institute')
            : (session('user_role') == 'Teacher'
                ? User::findOrFail(session('user_id'))->institute
                : $request->institute);

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
            'institute' => session('user_role') == 'Admin'
                ? 'required|string|max:100'
                : 'nullable|string|max:100',
            'class' => 'required|string|max:50',
            'section' => 'required|string|max:20',
            'contact' => 'required|string|max:20',
            'email' => 'nullable|email|max:255',
            'guardian_name' => 'nullable|string|max:255',
            'is_robotics_club_member' => 'required|boolean',
            'password' => 'nullable|min:6',
            'status' => 'required|boolean',
        ]);

        if (
            session('user_role') == 'InstituteAdmin' &&
            $student->institute != session('user_institute')
        ) {
            abort(403, 'Unauthorized action.');
        }

        if (session('user_role') == 'Teacher') {
            $teacher = User::findOrFail(session('user_id'));

            if ($student->institute != $teacher->institute) {
                abort(403, 'Unauthorized action.');
            }
        }

        $studentData = [
            'student_id' => $request->student_id,
            'name' => $request->name,
            'institute' => $institute,
            'class' => $request->class,
            'section' => $request->section,
            'contact' => $request->contact,
            'email' => $request->email,
            'guardian_name' => $request->guardian_name,
            'is_robotics_club_member' => $request->boolean('is_robotics_club_member'),
            'status' => $request->status,
            'profile_completed' => true,
        ];

        $newPassword = (string) $request->input('password', '');
        unset($studentData['password']);
        $student->update($studentData);

        // Keep password persistence explicit because student credentials live in their own table.
        if ($newPassword !== '') {
            $passwordHash = Hash::make($newPassword);
            DB::table('students')->where('id', $student->id)->update([
                'password' => $passwordHash,
                'updated_at' => now(),
            ]);

            $student->refresh();
            if (!Hash::check($newPassword, (string) $student->password)) {
                throw ValidationException::withMessages([
                    'password' => 'The new student password could not be saved. Please try again.',
                ]);
            }
        }

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

        if (session('user_role') == 'Teacher') {
            $teacher = User::findOrFail(session('user_id'));

            if ($student->institute != $teacher->institute) {
                abort(403, 'Unauthorized action.');
            }
        }

        DB::transaction(function () use ($student) {
            $this->deleteStudentCompletely($student);
        });

        return redirect()
            ->route(session('user_role') == 'Teacher' ? 'teacher.student-management' : 'students')
            ->with('success', 'Student and all related records deleted successfully.');
    }

    public function adminCertificates(Request $request)
    {
        $currentInstitute = session('user_role') == 'InstituteAdmin'
            ? session('user_institute')
            : ($request->filled('institute') ? trim((string) $request->input('institute')) : null);
        $selectedStudentClass = $request->filled('student_class')
            ? trim((string) $request->input('student_class'))
            : null;
        $selectedStudentSection = $request->filled('student_section')
            ? trim((string) $request->input('student_section'))
            : null;
        $selectedCertificateStatus = $request->filled('certificate_status')
            ? trim((string) $request->input('certificate_status'))
            : null;
        $hasFilters = session('user_role') == 'InstituteAdmin'
            || $request->filled('institute')
            || $request->filled('student_class')
            || $request->filled('student_section')
            || $request->filled('certificate_status');
        $certificateInstituteOptions = session('user_role') == 'Admin'
            ? Institute::where('status', 1)->orderBy('institute_name')->pluck('institute_name')
            : collect([session('user_institute')]);
        $certificateClassOptions = Student::when($currentInstitute, function ($query) use ($currentInstitute) {
                $query->where('institute', $currentInstitute);
            })
            ->whereNotNull('class')
            ->select('class')
            ->distinct()
            ->orderBy('class')
            ->pluck('class')
            ->map(fn ($className) => trim((string) $className))
            ->filter()
            ->unique()
            ->values();
        $certificateSectionOptions = Student::when($currentInstitute, function ($query) use ($currentInstitute) {
                $query->where('institute', $currentInstitute);
            })
            ->when($selectedStudentClass, function ($query) use ($selectedStudentClass) {
                $query->where('class', $selectedStudentClass);
            })
            ->whereNotNull('section')
            ->select('section')
            ->distinct()
            ->orderBy('section')
            ->pluck('section')
            ->map(fn ($section) => trim((string) $section))
            ->filter()
            ->unique()
            ->values();

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
            ->when($selectedStudentClass, function ($query) use ($selectedStudentClass) {
                $query->whereHas('student', function ($q) use ($selectedStudentClass) {
                    $q->where('class', $selectedStudentClass);
                });
            })
            ->when($selectedStudentSection, function ($query) use ($selectedStudentSection) {
                $query->whereHas('student', function ($q) use ($selectedStudentSection) {
                    $q->where('section', $selectedStudentSection);
                });
            })
            ->when($selectedCertificateStatus, function ($query) use ($selectedCertificateStatus) {
                $query->where('status', $selectedCertificateStatus);
            })
            ->latest()
            ->paginate(30)
            ->withQueryString();

        return view('certificates', compact(
            'certificates',
            'currentInstitute',
            'selectedStudentClass',
            'selectedStudentSection',
            'selectedCertificateStatus',
            'certificateInstituteOptions',
            'certificateClassOptions',
            'certificateSectionOptions',
            'hasFilters'
        ));
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
        $activeClasses = $classes->where('status', 1)->count();
        $totalStudents = Student::where('institute', $teacher->institute)->count();
        $classStudentCounts = Student::where('institute', $teacher->institute)
            ->select('class', 'section', DB::raw('count(*) as total'))
            ->groupBy('class', 'section')
            ->get()
            ->mapWithKeys(fn ($row) => [trim(($row->class ?? '') . ' ' . ($row->section ?? '')) => $row->total]);
        $pendingEvaluationCount = AssessmentResult::where('status', 'Pending Review')
            ->whereHas('assessment', function ($query) use ($teacher) {
                $query->where('teacher_id', $teacher->id)
                    ->where('institute', $teacher->institute);
            })
            ->count();
        $todaySessionCount = ClassContentSession::where('stem_engineer_id', $teacher->id)
            ->whereDate('session_date', now()->toDateString())
            ->count();
        $unfinishedSessionCount = ClassContentSession::where('stem_engineer_id', $teacher->id)
            ->whereIn('status', ['in_progress', 'partially_completed'])
            ->count();

        return view('teacher.teacher-dashboard', compact(
            'teacherName',
            'contentCount',
            'assessmentCount',
            'monthlyAssessmentCount',
            'annualAssessmentCount',
            'assignedClasses',
            'activeClasses',
            'totalStudents',
            'classStudentCounts',
            'pendingEvaluationCount',
            'todaySessionCount',
            'unfinishedSessionCount',
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
                    'title' => 'Student Management',
                    'description' => 'Add, update, bulk upload, and manage institute students.',
                    'icon' => 'fa-users-gear',
                    'route' => route('teacher.student-management'),
                ],
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

        $today = now()->toDateString();
        $currentWeekStart = now()->copy()->startOfWeek()->toDateString();
        $currentWeekEnd = now()->copy()->endOfWeek()->toDateString();
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
            ->whereDoesntHave('sessions', function ($query) {
                $query->where('status', 'completed');
            })
            ->whereNotExists(function ($query) {
                $query->selectRaw('1')
                    ->from('class_content_sessions')
                    ->whereNull('class_content_sessions.teaching_plan_item_id')
                    ->where('class_content_sessions.status', 'completed')
                    ->whereColumn('class_content_sessions.teaching_plan_id', 'teaching_plan_items.teaching_plan_id')
                    ->whereColumn('class_content_sessions.teaching_plan_week_id', 'teaching_plan_items.teaching_plan_week_id')
                    ->whereColumn('class_content_sessions.content_id', 'teaching_plan_items.content_id');
            })
            ->whereHas('week', function ($query) use ($today, $currentWeekStart, $currentWeekEnd) {
                $query->where(function ($weekQuery) use ($today) {
                    $weekQuery->whereDate('week_start_date', '<=', $today)
                        ->whereDate('week_end_date', '>=', $today);
                })->orWhere(function ($weekQuery) use ($currentWeekStart, $currentWeekEnd) {
                    $weekQuery->whereNull('week_start_date')
                        ->whereNull('week_end_date')
                        ->whereBetween('release_date', [$currentWeekStart, $currentWeekEnd]);
                });
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
        $sessions = $this->uniqueCurrentSessionRows($sessions);

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
            ) + ['showFilterPlaceholder' => false]
        );
    }

    public function teacherPendingSessions(Request $request)
    {
        $teacher = User::findOrFail(session('user_id'));
        $this->autoEndExpiredClassSessions($teacher);

        $classOptions = $this->teacherAssignedClassNames($teacher);
        $selectedClass = $request->input('class');
        $hasFilters = filled($selectedClass);

        $pendingSessions = collect();
        $laggedItems = collect();
        $teacherPassedPrepKeys = collect();
        $aiTrainingRequiredItemIds = collect();

        if ($hasFilters) {
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
                ->unique(fn ($session) => $this->sessionCurrentStateKey($session))
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
                        ->where(function ($weekQuery) {
                            $weekQuery->where('release_reason', 'lagged_content')
                                ->orWhereDate('week_end_date', '<', now()->toDateString())
                                ->orWhere(function ($fallback) {
                                    $fallback->whereNull('week_end_date')
                                        ->whereDate('release_date', '<', now()->copy()->startOfWeek()->toDateString());
                                });
                        });
                })
                ->whereDoesntHave('sessions', function ($query) {
                    $query->where('status', 'completed');
                })
                ->whereNotExists(function ($query) {
                    $query->selectRaw('1')
                        ->from('class_content_sessions')
                        ->whereNull('class_content_sessions.teaching_plan_item_id')
                        ->where('class_content_sessions.status', 'completed')
                        ->whereColumn('class_content_sessions.teaching_plan_id', 'teaching_plan_items.teaching_plan_id')
                        ->whereColumn('class_content_sessions.teaching_plan_week_id', 'teaching_plan_items.teaching_plan_week_id')
                        ->whereColumn('class_content_sessions.content_id', 'teaching_plan_items.content_id');
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
        }

        return view('teacher.pending-sessions', compact(
            'pendingSessions',
            'laggedItems',
            'classOptions',
            'selectedClass',
            'teacherPassedPrepKeys',
            'aiTrainingRequiredItemIds'
        ) + ['showFilterPlaceholder' => !$hasFilters]);
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

    public function randomSessionCompletionVideoUrl(bool $mobile = false): ?string
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

        $file = basename($videos->random());
        return $mobile ? \Illuminate\Support\Facades\URL::temporarySignedRoute('mobile.session-completion-video', now()->addMinutes(10), ['fileName' => $file])
            : route('teacher.session-completion-video', $file);
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

    private function uniqueCurrentSessionRows($sessions)
    {
        return $sessions
            ->unique(fn (ClassContentSession $session) => $this->sessionCurrentStateKey($session))
            ->values();
    }

    private function sessionCurrentStateKey(ClassContentSession $session): string
    {
        if ($session->teaching_plan_item_id) {
            return 'plan-item:' . $session->teaching_plan_item_id;
        }

        return 'session:' . $session->id;
    }

    public function teacherContent(Request $request)
    {
        $teacher = User::find(session('user_id'));
        $selectedStudentClass = trim((string) $request->input('student_class', ''));
        $selectedStudentSection = trim((string) $request->input('student_section', ''));
        $hasFilters = filled($selectedStudentClass) || filled($selectedStudentSection);

        $classOptions = SchoolClass::where('institute', $teacher->institute)
            ->whereNotNull('class_name')
            ->orderBy('class_name')
            ->pluck('class_name')
            ->map(fn ($className) => trim((string) $className))
            ->filter()
            ->unique(fn ($className) => mb_strtolower($className))
            ->values();

        $sectionOptions = collect();

        $schoolClassSections = SchoolClass::where('institute', $teacher->institute)
            ->whereNotNull('section')
            ->orderBy('section')
            ->pluck('section')
            ->map(fn ($section) => trim((string) $section));

        $studentSections = Student::where('institute', $teacher->institute)
            ->whereNotNull('section')
            ->orderBy('section')
            ->pluck('section')
            ->map(fn ($section) => trim((string) $section));

        $sectionOptions = $schoolClassSections
            ->merge($studentSections)
            ->filter()
            ->unique(fn ($section) => mb_strtolower($section))
            ->values();

        $teachingItems = collect();
        $approvedContentIds = collect();
        $teachingStatusByContentId = collect();
        $contentClassByContentId = collect();
        $contentSectionByContentId = collect();
        $contentGradeByContentId = collect();
        $aiTrainingRequiredContentIds = collect();
        $teacherPassedPrepKeys = collect();
        $contents = collect();
        $inProgressContentIds = collect();

        if ($hasFilters) {
            $teachingItems = TeachingPlanItem::with(['plan', 'week'])
                ->whereIn('status', ['released', 'completed'])
                ->whereHas('plan', function ($query) use ($teacher, $selectedStudentClass, $selectedStudentSection) {
                    $query->where('institute', $teacher->institute)
                        ->whereIn('status', ['active', 'completed'])
                        ->when($selectedStudentClass, function ($classQuery) use ($selectedStudentClass) {
                            $classQuery->where('class', $selectedStudentClass);
                        })
                        ->when($selectedStudentSection, function ($sectionQuery) use ($selectedStudentSection) {
                            $sectionQuery->where('section', $selectedStudentSection);
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
                        ? trim((string) $plan->class)
                        : 'Unassigned Class';
                });

            $contentSectionByContentId = $teachingItems
                ->groupBy('content_id')
                ->map(function ($items) {
                    $plan = $items->first()->plan;

                    return $plan
                        ? trim((string) $plan->section)
                        : '';
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
        }

        return view('teacher.teacher-content', compact(
            'contents',
            'teachingStatusByContentId',
            'inProgressContentIds',
            'contentClassByContentId',
            'contentSectionByContentId',
            'contentGradeByContentId',
            'aiTrainingRequiredContentIds',
            'teacherPassedPrepKeys',
            'classOptions',
            'sectionOptions',
            'selectedStudentClass',
            'selectedStudentSection'
        ) + ['showFilterPlaceholder' => !$hasFilters]);
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
        $sectionOptions = Student::where('institute', $teacher->institute)
            ->whereNotNull('section')
            ->orderBy('section')
            ->pluck('section')
            ->map(fn ($section) => trim((string) $section))
            ->filter()
            ->unique(fn ($section) => mb_strtolower($section))
            ->values();
        $hasFilters = request()->filled('class')
            || request()->filled('section')
            || request()->filled('status')
            || request()->filled('category')
            || request()->filled('search')
            || request()->filled('assessment_date');

        $classFilter = request()->input('class');
        $sectionFilter = request()->input('section');
        $statusFilter = request()->input('status');
        $categoryFilter = request()->input('category');
        $searchFilter = request()->input('search');
        $dateFilter = request()->input('assessment_date');

        $assessments = collect();

        if ($hasFilters) {
            $assessments = Assessment::where('institute', $teacher->institute)
                ->where('teacher_id', $teacher->id)
                ->whereIn('assigned_class', $assignedClasses)
                ->when($classFilter, fn ($query) => $query->where('assigned_class', 'like', '%' . $classFilter . '%'))
                ->when($sectionFilter, fn ($query) => $query->where('assigned_class', 'like', '%' . $sectionFilter . '%'))
                ->when($statusFilter, fn ($query) => $query->where('status', $statusFilter))
                ->when($categoryFilter, fn ($query) => $query->where('assessment_category', $categoryFilter))
                ->when($dateFilter, fn ($query) => $query->whereDate('assessment_date', $dateFilter))
                ->when($searchFilter, function ($query) use ($searchFilter) {
                    $query->where(function ($innerQuery) use ($searchFilter) {
                        $innerQuery->where('assessment_title', 'like', '%' . $searchFilter . '%')
                            ->orWhere('assigned_class', 'like', '%' . $searchFilter . '%');
                    });
                })
                ->latest()
                ->get();
        }

        return view('teacher.teacher-assessments', compact(
            'assessments',
            'assignedClasses',
            'classFilter',
            'sectionFilter',
            'statusFilter',
            'categoryFilter',
            'searchFilter',
            'dateFilter',
            'sectionOptions'
        ) + ['showFilterPlaceholder' => !$hasFilters]);
    }

    public function teacherResults(Request $request)
    {
        $search = $request->search;
        $badge = $request->badge;
        $status = $request->status;
        $sort = $request->sort;
        $hasFilters = $request->filled('search')
            || $request->filled('badge')
            || $request->filled('status')
            || $request->filled('sort')
            || $request->filled('student_class')
            || $request->filled('student_section')
            || $request->filled('class_page')
            || $request->filled('student_section_page');

        $teacher = User::find(session('user_id'));
        $studentIds = $this->teacherAssignedStudentIds($teacher);
        $classOptions = SchoolClass::where('institute', $teacher->institute)
            ->whereNotNull('class_name')
            ->orderBy('class_name')
            ->pluck('class_name')
            ->map(fn ($className) => trim((string) $className))
            ->filter()
            ->unique(fn ($className) => mb_strtolower($className))
            ->values();
        $sectionOptions = collect();
        ['selectedClass' => $selectedStudentClass, 'sectionPager' => $classSectionPager] =
            $this->buildStudentClassPager($request, $teacher->institute, 'teacher.results');
        ['selectedSection' => $selectedStudentSection, 'sectionPager' => $studentSectionPager] =
            $this->buildStudentSectionPager($request, $teacher->institute, $selectedStudentClass, 'teacher.results');

        if ($selectedStudentClass) {
            $sectionOptions = Student::where('institute', $teacher->institute)
                ->where('class', $selectedStudentClass)
                ->whereNotNull('section')
                ->orderBy('section')
                ->pluck('section')
                ->map(fn ($section) => trim((string) $section))
                ->filter()
                ->unique(fn ($section) => mb_strtolower($section))
                ->values();
        }

        $results = collect();

        if ($hasFilters) {
            $results = AssessmentResult::with(['assessment', 'student'])
                ->whereIn('student_id', $studentIds)
                ->when($selectedStudentClass, function ($query) use ($selectedStudentClass, $selectedStudentSection) {
                    $query->whereHas('student', function ($studentQuery) use ($selectedStudentClass, $selectedStudentSection) {
                        $studentQuery->where('class', $selectedStudentClass);

                        if ($selectedStudentSection) {
                            $studentQuery->where('section', $selectedStudentSection);
                        }
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
        }

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
            'classSectionPager',
            'studentSectionPager',
            'selectedStudentClass',
            'selectedStudentSection',
            'classOptions',
            'sectionOptions',
            'search',
            'badge',
            'status',
            'sort'
        ) + ['showFilterPlaceholder' => !$hasFilters]);
    }

    public function teacherResultsAiPayload(Request $request, GeminiAiService $ai): array
    {
        $teacher = User::findOrFail(session('user_id'));
        abort_unless(in_array($teacher->role, ['Teacher', 'STEM Engineer'], true), 403);
        $metrics = $this->teacherResultsAiMetrics($request, $teacher);
        return ['metrics' => $metrics, 'insights' => $ai->generateReportInsights('STEM Engineer Student Results', $metrics)];
    }

    public function generateTeacherResultsAiInsights(Request $request, GeminiAiService $ai)
    {
        $teacher = User::findOrFail(session('user_id'));
        $metrics = $this->teacherResultsAiMetrics($request, $teacher);

        try {
            return redirect()
                ->route('teacher.results', $request->only(['search', 'class_page', 'student_class', 'student_section_page', 'student_section', 'badge', 'status', 'sort']))
                ->with('aiInsights', $ai->generateReportInsights('STEM Engineer Student Results', $metrics));
        } catch (\Throwable $exception) {
            return redirect()
                ->route('teacher.results', $request->only(['search', 'class_page', 'student_class', 'student_section_page', 'student_section', 'badge', 'status', 'sort']))
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
                ->route('teacher.results', $request->only(['search', 'class_page', 'student_class', 'student_section_page', 'student_section', 'badge', 'status', 'sort']))
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
        $selectedStudentClass = $request->input('student_class');
        $selectedStudentSection = $request->input('student_section');
        $badge = $request->input('badge');
        $status = $request->input('status');

        $results = AssessmentResult::with(['assessment', 'student'])
            ->whereIn('student_id', $studentIds)
            ->when($selectedStudentSection, fn ($query) => $query->whereHas('student', fn ($s) => $s->where('section', $selectedStudentSection)))
            ->when($selectedStudentClass, function ($query) use ($selectedStudentClass, $selectedStudentSection) {
                $query->whereHas('student', function ($studentQuery) use ($selectedStudentClass, $selectedStudentSection) {
                    $studentQuery->where('class', $selectedStudentClass);

                    if ($selectedStudentSection) {
                        $studentQuery->where('section', $selectedStudentSection);
                    }
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
            'class_filter' => $selectedStudentClass ?: 'All Classes',
            'section_filter' => $selectedStudentSection ?: 'All Sections',
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
        $hasFilters = $request->filled('student_class')
            || $request->filled('student_section')
            || $request->filled('class_page')
            || $request->filled('student_section_page');
        $classOptions = SchoolClass::where('institute', $teacher->institute)
            ->whereNotNull('class_name')
            ->orderBy('class_name')
            ->pluck('class_name')
            ->map(fn ($className) => trim((string) $className))
            ->filter()
            ->unique(fn ($className) => mb_strtolower($className))
            ->values();
        ['selectedClass' => $selectedStudentClass, 'sectionPager' => $classSectionPager] =
            $this->buildStudentClassPager($request, $teacher->institute, 'teacher.certificates');
        ['selectedSection' => $selectedStudentSection, 'sectionPager' => $studentSectionPager] =
            $this->buildStudentSectionPager($request, $teacher->institute, $selectedStudentClass, 'teacher.certificates');

        $sectionOptions = collect();
        if ($selectedStudentClass) {
            $sectionOptions = Student::where('institute', $teacher->institute)
                ->where('class', $selectedStudentClass)
                ->whereNotNull('section')
                ->orderBy('section')
                ->pluck('section')
                ->map(fn ($section) => trim((string) $section))
                ->filter()
                ->unique(fn ($section) => mb_strtolower($section))
                ->values();
        }

        $certificates = collect();

        if ($hasFilters) {
            $certificates = Certificate::with(['student', 'course'])
                ->whereIn('student_id', $this->teacherAssignedStudentIds($teacher))
                ->when($selectedStudentClass, function ($query) use ($selectedStudentClass, $selectedStudentSection) {
                    $query->whereHas('student', function ($studentQuery) use ($selectedStudentClass, $selectedStudentSection) {
                        $studentQuery->where('class', $selectedStudentClass);

                        if ($selectedStudentSection) {
                            $studentQuery->where('section', $selectedStudentSection);
                        }
                    });
                })
                ->latest()
                ->get();
        }

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
            'classSectionPager',
            'studentSectionPager',
            'selectedStudentClass',
            'selectedStudentSection',
            'classOptions',
            'sectionOptions'
        ) + ['showFilterPlaceholder' => !$hasFilters]);
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
        $currentInstitute = session('user_role') == 'InstituteAdmin'
            ? session('user_institute')
            : ($request->filled('institute') ? trim((string) $request->input('institute')) : null);
        $selectedAchievementStatus = $request->filled('achievement_status')
            ? trim((string) $request->input('achievement_status'))
            : null;
        $hasFilters = session('user_role') == 'InstituteAdmin'
            || $request->filled('institute')
            || $request->filled('achievement_status');
        $achievementInstituteOptions = session('user_role') == 'Admin'
            ? Institute::where('status', 1)->orderBy('institute_name')->pluck('institute_name')
            : collect([session('user_institute')]);

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
            ->when($selectedAchievementStatus, function ($query) use ($selectedAchievementStatus) {
                $query->where('verification_status', $selectedAchievementStatus);
            })
            ->latest()
            ->paginate(30)
            ->withQueryString();

        return view('admin-teacher-achievements', compact(
            'achievements',
            'currentInstitute',
            'selectedAchievementStatus',
            'achievementInstituteOptions',
            'hasFilters'
        ));
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

        $student = Student::whereRaw('LOWER(TRIM(student_id)) = ?', [strtolower(trim((string) $request->student_id))])
            ->where('status', 1)
            ->get()
            ->first(function ($student) use ($request) {
                return Hash::check($request->password, (string) $student->password)
                    || hash_equals((string) $student->password, (string) $request->password);
            });

        if ($student) {
            if (blank($student->email)) {
                return redirect()->back()->with('error', 'Student guardian email is not configured. Please contact your institute administrator.');
            }

            try {
                $challengeToken = app(\App\Services\MfaChallengeService::class)
                    ->issue($student, 'email', $request->ip(), $request->userAgent());
            } catch (\Throwable $exception) {
                report($exception);
                return redirect()->back()->with('error', 'Student verification is temporarily unavailable. Please try again later.');
            }

            session([
                'student_mfa_challenge_token' => $challengeToken,
                'student_mfa_pending_id' => $student->id,
            ]);

            return redirect()->route('student.mfa');
        }

        return redirect()->back()->with('error', 'Invalid student login details');
    }

    public function studentMfa()
    {
        abort_unless(session('student_mfa_challenge_token') && session('student_mfa_pending_id'), 403);

        $student = Student::findOrFail(session('student_mfa_pending_id'));
        abort_if(blank($student->email), 403);

        return view('student-mfa', [
            'student' => $student,
            'firebaseConfig' => [
                'apiKey' => config('services.firebase.web_api_key'),
                'authDomain' => env('FIREBASE_AUTH_DOMAIN'),
                'projectId' => config('services.firebase.project_id'),
                'messagingSenderId' => env('FIREBASE_MESSAGING_SENDER_ID'),
                'appId' => env('FIREBASE_APP_ID'),
            ],
        ]);
    }

    public function verifyStudentMfa(Request $request)
    {
        $request->validate(['code' => ['required', 'digits:6']]);
        $challengeToken = session('student_mfa_challenge_token');
        $studentId = session('student_mfa_pending_id');
        abort_unless($challengeToken && $studentId, 403);

        $student = Student::findOrFail($studentId);
        try {
            app(\App\Services\MfaChallengeService::class)->verify(
                $challengeToken,
                $request->string('code')->toString(),
                $request->ip(),
                $request->userAgent(),
            );
        } catch (\Illuminate\Validation\ValidationException $exception) {
            return back()->withErrors($exception->errors());
        }

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

            session()->forget(['student_mfa_challenge_token', 'student_mfa_pending_id']);

            return redirect()->route('student.dashboard');
    }

    public function studentDashboard(GeminiAiService $ai)
    {
        $student = Student::find(session('student_id'));

        $studentName = session('student_name');
        $studentCode = session('student_code');
        $studentId = session('student_id');

        $results = AssessmentResult::with('assessment')
            ->where('student_id', $studentId)
            ->latest()
            ->get();

        $badgeCount = $results->whereNotNull('badge')->count();
        $completedResults = $results->where('status', 'Completed');
        $completedAssessmentCount = $completedResults->count();
        $averagePercentage = round((float) ($completedResults->avg('percentage') ?? 0), 1);

        $attemptedAssessmentIds = AssessmentResult::where('student_id', $studentId)
            ->pluck('assessment_id');

        $assignedClass = $this->studentClassName($student);

        $componentOffers = $this->studentComponentAssessmentOffers($student, $ai);
        $this->notifyStudentComponentMasteryOffers($student, $componentOffers);

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

        $assessmentProgress = $totalAssessmentCount > 0
            ? min(100, round(($completedAssessmentCount / $totalAssessmentCount) * 100))
            : 0;

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
            'completedAssessmentCount',
            'averagePercentage',
            'assessmentProgress',
            'pendingAssessmentCount',
            'totalAssessmentCount',
            'upcomingAssessments',
            'student',
            'assignedClass',
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
        $session = $this->ownedWebAssessmentSession($sessionId);
        if ($session->status !== 'Started') {
            return response()->json(['success' => true, 'violation_count' => (int) $session->violation_count, 'auto_submit' => $session->status === 'AutoSubmitted']);
        }

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
        $session = $this->ownedWebAssessmentSession($sessionId);
        abort_unless($session->status === 'Started', 409, 'This assessment session has ended.');

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
    private function ownedWebAssessmentSession($sessionId): AssessmentSession
    {
        $type = session('student_id') ? 'Student' : session('user_role');
        $id = $type === 'Student' ? session('student_id') : session('user_id');
        abort_unless($id && in_array($type, ['Student', 'Teacher'], true), 403);
        return AssessmentSession::where('user_id', $id)->where('user_type', $type)->findOrFail($sessionId);
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

    public function studentComponentMastery(Request $request, GeminiAiService $ai)
    {
        $student = Student::findOrFail(session('student_id'));
        $offers = $this->studentComponentAssessmentOffers($student, $ai);
        $this->notifyStudentComponentMasteryOffers($student, $offers);

        $componentKeys = $offers->pluck('component_key')->filter()->values();
        $assessments = Assessment::query()
            ->where('institute', $student->institute)
            ->whereRaw("REPLACE(TRIM(assigned_class), '  ', ' ') = ?", [$this->studentClassName($student)])
            ->where('assessment_category', 'Component Mastery')
            ->whereIn('component_key', $componentKeys)
            ->where('status', 1)
            ->where('question_paper_status', 'Approved')
            ->whereNotNull('file_path')
            ->latest()
            ->get()
            ->unique('component_key')
            ->keyBy('component_key');

        $results = AssessmentResult::with('assessment')
            ->where('student_id', $student->id)
            ->whereHas('assessment', function ($query) use ($componentKeys) {
                $query->where('assessment_category', 'Component Mastery')
                    ->whereIn('component_key', $componentKeys);
            })
            ->latest()
            ->get()
            ->unique(fn ($result) => $result->assessment?->component_key)
            ->keyBy(fn ($result) => $result->assessment?->component_key);

        $masteryAssessments = $offers->map(function (array $offer) use ($assessments, $results) {
            $assessment = $assessments->get($offer['component_key']);
            $result = $results->get($offer['component_key']);

            if ($result) {
                $status = $result->status === 'Completed'
                    ? ($result->passed ? 'Certificate pending approval' : 'Completed - score below certification grade')
                    : 'Pending AI evaluation';
            } elseif ($assessment) {
                $status = 'Ready to take';
            } else {
                $status = 'Eligible - assessment not prepared';
            }

            return [
                'offer' => $offer,
                'assessment' => $assessment,
                'result' => $result,
                'status' => $status,
            ];
        });

        return view('student.component-mastery', compact('masteryAssessments'));
    }

    public function studentTakeAssessment(Request $request, GeminiAiService $ai)
    {
        $studentId = session('student_id');

        $student = Student::findOrFail($studentId);
        $assignedClass = $this->studentClassName($student);
        $componentAssessmentOffers = $this->studentComponentAssessmentOffers($student, $ai);
        $eligibleComponentKeys = $componentAssessmentOffers
            ->pluck('component_key')
            ->filter()
            ->values();

        $availableAssessments = Assessment::where('status', 1)
            ->where('institute', $student->institute)
            ->where('question_paper_status', 'Approved')
            ->whereNotNull('file_path')
            ->whereDate('assessment_date', '<=', today())
            ->where(function ($query) use ($eligibleComponentKeys) {
                $query->where('assessment_category', '!=', 'Component Mastery')
                    ->orWhereIn('component_key', $eligibleComponentKeys);
            })
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
                'selectedAssessment',
                'componentAssessmentOffers'
            )
        );
    }

    public function generateStudentComponentAssessment(Request $request, string $componentKey, GeminiAiService $ai, ?Student $apiStudent = null)
    {
        $student = $apiStudent ?: Student::findOrFail(session('student_id'));
        $assignedClass = $this->studentClassName($student);
        $offers = $this->studentComponentAssessmentOffers($student, $ai);
        $offer = $offers->firstWhere('component_key', $componentKey);

        if (!$offer) {
            return redirect()
                ->route('student.assessment')
                ->with('error', 'You need to complete at least 5 practical topics for this component before taking the mastery assessment.');
        }

        $attemptedAssessmentIds = AssessmentResult::where('student_id', $student->id)
            ->pluck('assessment_id');

        $existingAssessment = Assessment::where('status', 1)
            ->where('institute', $student->institute)
            ->where('assessment_category', 'Component Mastery')
            ->where('component_key', $componentKey)
            ->where('question_paper_status', 'Approved')
            ->whereRaw("REPLACE(TRIM(assigned_class), '  ', ' ') = ?", [$assignedClass])
            ->whereNotIn('id', $attemptedAssessmentIds)
            ->latest()
            ->first();

        if ($existingAssessment) {
            return redirect()
                ->route('student.assessment', ['assessment_id' => $existingAssessment->id])
                ->with('success', $existingAssessment->component_label . ' mastery assessment is ready.');
        }

        $contents = Content::with(['aiSummary', 'courseContent.sourceTemplateContent.aiSummary'])
            ->whereIn('id', $offer['content_ids'])
            ->where('status', 1)
            ->get();

        $contentContext = $this->componentMasteryContentContext($contents);

        if (mb_strlen($contentContext) < 100) {
            return redirect()
                ->route('student.assessment')
                ->with('error', 'There is not enough readable lesson material to generate this component assessment yet.');
        }

        try {
            $paper = $ai->generateComponentMasteryQuestionPaper([
                'assessment_title' => 'Basics of ' . $offer['component_label'],
                'assessment_category' => 'Component Mastery',
                'component' => $offer['component_label'],
                'assigned_class' => $assignedClass,
                'student_class' => $student->class,
                'total_marks' => 50,
                'duration_minutes' => 60,
                'completed_practical_topics' => $contents->pluck('content_title')->values()->all(),
                'content_text' => $contentContext,
            ]);

            [$filePath, $previewPath] = $this->storeComponentMasteryQuestionPaperPdf($paper, [
                'assessment_title' => $paper['title'] ?? ('Basics of ' . $offer['component_label']),
                'assessment_category' => 'Component Mastery',
                'assigned_class' => $assignedClass,
                'assessment_date' => today()->toDateString(),
                'duration' => 60,
                'total_marks' => 50,
                'teacher_name' => 'AI Assessment Engine',
                'institute' => $student->institute,
                'contents' => $contents,
            ]);
        } catch (\Throwable $exception) {
            report($exception);

            return redirect()
                ->route('student.assessment')
                ->with('error', 'AI could not generate this component assessment right now. Please try again later.');
        }

        $assessment = Assessment::create([
            'institute' => $student->institute,
            'assessment_title' => $paper['title'] ?? ('Basics of ' . $offer['component_label']),
            'assessment_type' => 'Student',
            'assigned_class' => $assignedClass,
            'assessment_category' => 'Component Mastery',
            'component_key' => $componentKey,
            'component_label' => $offer['component_label'],
            'certificate_eligible' => true,
            'assessment_date' => today()->toDateString(),
            'start_time' => null,
            'end_time' => null,
            'total_marks' => 50,
            'duration' => '60',
            'question_paper_type' => 'AI Component Mastery Question Paper',
            'ai_generated' => true,
            'ai_source_content_ids' => $contents->pluck('id')->values()->all(),
            'ai_generation_payload' => json_encode($paper, JSON_UNESCAPED_SLASHES),
            'file_path' => $filePath,
            'question_paper_preview_path' => $previewPath,
            'question_paper_status' => 'Approved',
            'question_paper_reviewed_by' => null,
            'question_paper_reviewed_at' => now(),
            'question_paper_feedback' => null,
            'status' => 1,
            'content_id' => $contents->first()?->id,
            'teacher_id' => null,
        ]);

        return redirect()
            ->route('student.assessment', ['assessment_id' => $assessment->id])
            ->with('success', $assessment->component_label . ' mastery assessment generated successfully.');
    }

    private function studentComponentAssessmentOffers(Student $student, ?GeminiAiService $ai = null)
    {
        $contentIds = $this->studentAvailableContentIds($student);
        $contents = Content::with(['aiSummary', 'courseContent.sourceTemplateContent.aiSummary'])
            ->whereIn('id', $contentIds)
            ->where('status', 1)
            ->get();

        $completedContentIds = $this->studentPassedAiReviewContentIds($student, $contents);
        $completedContents = $contents
            ->whereIn('id', $completedContentIds)
            ->values();

        if ($completedContents->count() < 5) {
            return collect();
        }

        $this->ensureComponentProfilesForContents($completedContents, $ai);

        $profiles = AiComponentContentProfile::whereIn('content_id', $completedContents->pluck('id'))
            ->where('is_practical', true)
            ->where('confidence', '>=', 45)
            ->get()
            ->keyBy('content_id');

        return $completedContents
            ->filter(fn (Content $content) => $profiles->has($content->id))
            ->groupBy(fn (Content $content) => $profiles->get($content->id)->component_key)
            ->map(function ($componentContents, $componentKey) use ($profiles) {
                $profile = $profiles->get($componentContents->first()->id);

                return [
                    'component_key' => $componentKey,
                    'component_label' => $profile->component_label,
                    'completed_count' => $componentContents->count(),
                    'content_ids' => $componentContents->pluck('id')->values()->all(),
                    'content_titles' => $componentContents->pluck('content_title')->values()->all(),
                ];
            })
            ->filter(fn ($offer) => $offer['completed_count'] >= 5)
            ->sortBy('component_label')
            ->values();
    }

    private function notifyStudentComponentMasteryOffers(Student $student, $offers): void
    {
        foreach ($offers as $offer) {
            $notification = LmsNotification::firstOrCreate(
                [
                    'student_id' => $student->id,
                    'component_key' => $offer['component_key'],
                    'notification_type' => 'component_mastery_eligible',
                ],
                [
                    'title' => 'New Component Mastery assessment available',
                    'message' => 'You are eligible for the ' . $offer['component_label'] . ' Component Mastery assessment after completing ' . $offer['completed_count'] . ' practical topics. Open Component Mastery to begin.',
                    'target' => 'students',
                    'institute' => $student->institute,
                    'starts_at' => today()->toDateString(),
                    'expires_at' => null,
                    'status' => 'active',
                    'login_display_limit' => 0,
                    'created_by' => null,
                ]
            );

            if ($notification->wasRecentlyCreated) {
                app(FirebasePushService::class)->sendLmsNotification($notification);
            }
        }
    }

    private function ensureComponentProfilesForContents($contents, ?GeminiAiService $ai = null): void
    {
        $contents = collect($contents)->values();
        $existingIds = AiComponentContentProfile::whereIn('content_id', $contents->pluck('id'))
            ->pluck('content_id')
            ->map(fn ($id) => (int) $id);

        $missing = $contents
            ->reject(fn (Content $content) => $existingIds->contains((int) $content->id))
            ->values();

        if ($missing->isEmpty()) {
            return;
        }

        $items = $missing->map(function (Content $content) {
            $summary = $this->generatedAiSummaryForContentRecord($content);

            return [
                'content_id' => $content->id,
                'title' => $content->content_title,
                'summary' => mb_substr((string) ($summary?->summary ?? $content->description ?? ''), 0, 1200),
                'key_points' => $summary?->key_points ?? [],
                'text_snippet' => mb_substr((string) ($summary?->extracted_text ?? ''), 0, 1600),
            ];
        })->all();

        $classifiedItems = [];

        if ($ai) {
            try {
                $classifiedItems = $ai->classifyComponentContent($items)['items'] ?? [];
            } catch (\Throwable $exception) {
                report($exception);
            }
        }

        $classifiedById = collect($classifiedItems)->keyBy(fn ($item) => (int) ($item['content_id'] ?? 0));

        foreach ($missing as $content) {
            $classification = $classifiedById->get((int) $content->id) ?: $this->fallbackComponentClassification($content);
            $componentLabel = trim((string) ($classification['component_label'] ?? 'General STEM')) ?: 'General STEM';
            $componentKey = trim((string) ($classification['component_key'] ?? Str::slug($componentLabel)));

            AiComponentContentProfile::updateOrCreate(
                ['content_id' => $content->id],
                [
                    'component_key' => $componentKey ?: 'general-stem',
                    'component_label' => $componentLabel,
                    'is_practical' => (bool) ($classification['is_practical'] ?? false),
                    'confidence' => min(100, max(0, (int) ($classification['confidence'] ?? 50))),
                    'evidence' => $classification['evidence'] ?? [],
                    'provider' => config('ai.provider', 'gemini'),
                    'model' => config('ai.gemini.model'),
                    'analyzed_at' => now(),
                ]
            );
        }
    }

    private function fallbackComponentClassification(Content $content): array
    {
        $summary = $this->generatedAiSummaryForContentRecord($content);
        $text = mb_strtolower(implode(' ', array_filter([
            $content->content_title,
            $content->description,
            $summary?->summary,
            implode(' ', $summary?->key_points ?? []),
        ])));

        $components = [
            'arduino' => 'Arduino',
            'sensor' => 'Sensors',
            'sensors' => 'Sensors',
            'microcontroller' => 'Microcontrollers',
            'microprocessor' => 'Microprocessors',
            'motor' => 'Motors',
            'servo' => 'Motors',
            'robot' => 'Robotics',
            'iot' => 'IoT',
            'circuit' => 'Electronics',
            'electronics' => 'Electronics',
            'coding' => 'Coding',
            'programming' => 'Coding',
        ];

        foreach ($components as $needle => $label) {
            if (str_contains($text, $needle)) {
                return [
                    'component_key' => Str::slug($label),
                    'component_label' => $label,
                    'is_practical' => true,
                    'confidence' => 55,
                    'evidence' => [$content->content_title],
                ];
            }
        }

        return [
            'component_key' => 'general-stem',
            'component_label' => 'General STEM',
            'is_practical' => str_contains($text, 'project') || str_contains($text, 'experiment') || str_contains($text, 'build'),
            'confidence' => 35,
            'evidence' => [$content->content_title],
        ];
    }

    private function componentMasteryContentContext($contents): string
    {
        return collect($contents)
            ->map(function (Content $content) {
                $summary = $this->generatedAiSummaryForContentRecord($content);
                $parts = array_filter([
                    'Content: ' . $content->content_title,
                    $content->description,
                    $summary?->summary,
                    implode("\n", $summary?->key_points ?? []),
                    mb_substr((string) ($summary?->extracted_text ?? ''), 0, 5000),
                ]);

                return implode("\n", $parts);
            })
            ->filter()
            ->implode("\n\n---\n\n");
    }

    private function storeComponentMasteryQuestionPaperPdf(array $paper, array $meta): array
    {
        $pdf = Pdf::loadView('pdf.ai-question-paper', [
            'paper' => $paper,
            'meta' => $meta,
        ])->setPaper('a4', 'portrait');

        $fileName = 'component_mastery_' . now()->format('Ymd_His') . '_' . uniqid() . '.pdf';
        $filePath = 'assessment-papers/' . $fileName;

        Storage::disk('local')->put($filePath, $pdf->output());

        return [$filePath, $filePath];
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
        $selectedInstitute = session('user_role') == 'Admin'
            ? trim((string) $request->input('institute', ''))
            : session('user_institute');
        $currentInstitute = $selectedInstitute !== '' ? $selectedInstitute : null;

        $instituteOptions = Institute::where('status', 1)
            ->orderBy('institute_name')
            ->pluck('institute_name')
            ->values();

        $contentLogsQuery = UserActivityLog::with(['teacher', 'student'])
            ->whereIn('user_type', ['Teacher', 'Student'])
            ->learningContent()
            ->when($date, function ($query, $date) {
                $query->whereDate('started_at', $date);
            })
            ->when(in_array($viewerType, ['Teacher', 'Student'], true), function ($query) use ($viewerType) {
                $query->where('user_type', $viewerType);
            })
            ->when($currentInstitute, function ($query) use ($currentInstitute) {
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

        $contentIdsByLogId = $contentLogs->getCollection()
            ->mapWithKeys(function ($log) {
                preg_match('#content-preview/(\d+)/for/#', (string) $log->page_url, $matches);

                return !empty($matches[1])
                    ? [$log->id => (int) $matches[1]]
                    : [];
            });

        $contentContextByLogId = Content::whereIn('id', $contentIdsByLogId->values()->unique())
            ->get(['id', 'content_title', 'assigned_class', 'section', 'institute'])
            ->keyBy('id');

        $contentContextByLogId = $contentIdsByLogId
            ->mapWithKeys(function ($contentId, $logId) use ($contentContextByLogId) {
                $content = $contentContextByLogId->get($contentId);
                $classLabel = $content
                    ? trim((string) $content->assigned_class . ' ' . (string) $content->section)
                    : '';

                return [
                    $logId => [
                        'title' => $content->content_title ?? null,
                        'class_label' => $classLabel,
                        'institute' => $content->institute ?? null,
                    ],
                ];
            });

        $teacherIdsOnPage = $contentLogs->getCollection()
            ->where('user_type', 'Teacher')
            ->pluck('user_id')
            ->filter()
            ->unique()
            ->values();

        $teacherClassLabelsById = User::whereIn('id', $teacherIdsOnPage)
            ->get(['id', 'institute'])
            ->mapWithKeys(function (User $teacher) {
                $classes = $this->teacherAssignedClassNames($teacher)
                    ->filter()
                    ->unique(fn ($className) => mb_strtolower($className))
                    ->values();
                $label = $classes->take(3)->implode(', ');

                if ($classes->count() > 3) {
                    $label .= ' +' . ($classes->count() - 3) . ' more';
                }

                return [$teacher->id => $label ?: 'Unassigned Class'];
            });

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
                $row->institute = $user->institute ?? 'Unassigned Institute';
                $row->class_label = $row->user_type == 'Student'
                    ? trim(($user->class ?? '') . ' ' . ($user->section ?? ''))
                    : null;

                return $row;
            });

        return view('activity-monitoring', compact(
            'contentLogs',
            'currentInstitute',
            'selectedInstitute',
            'instituteOptions',
            'contentContextByLogId',
            'teacherClassLabelsById',
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

        $activityLogId = session('active_activity_log_id');

        $activityLog = UserActivityLog::where('user_session_id', session('tracking_session_id'))
            ->whereNull('ended_at')
            ->learningContent()
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

            return redirect()->route('student.content.ai-review.quiz', $content->id);
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
                ->with('error', 'AI quiz is not available for this content yet.');
        }

        $gradeLevel = $this->studentGradeName($student);
        $quiz = $this->studentQuizForContent($content, $summary, $gradeLevel);
        $latestAttempt = AiQuizAttempt::where('ai_quiz_id', $quiz->id)
            ->where('student_id', $studentId)
            ->where('attempt_type', self::AI_STUDENT_ATTEMPT_TYPE)
            ->latest()
            ->first();

        if ($latestAttempt && $latestAttempt->status == 'passed') {
            return redirect()
                ->route('student.content')
                ->with('success', 'AI quiz already cleared. This lesson is complete.');
        }

        return redirect()->route('student.content.ai-review.quiz', $content->id);
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
                ->with('error', 'AI quiz is not available for this content yet.');
        }

        $gradeLevel = $this->studentGradeName($student);
        $quiz = $this->studentQuizForContent($content, $summary, $gradeLevel);
        $latestAttempt = AiQuizAttempt::where('ai_quiz_id', $quiz->id)
            ->where('student_id', $studentId)
            ->latest()
            ->first();

        if ($latestAttempt && $latestAttempt->status == 'passed') {
            return redirect()
                ->route('student.content')
                ->with('success', 'AI quiz already cleared. This lesson is complete.');
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
                ->with('error', 'AI quiz is not available for this content yet.');
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
                ->route('student.content')
                ->with('success', 'AI quiz already cleared. This lesson is complete.');
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
                ->with('error', 'Please answer at least one question before submitting the AI quiz.');
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
            ->with('success', 'AI quiz passed. Lesson marked as completed.');
        }

        return redirect()
            ->route('student.content.ai-review.quiz', $content->id)
            ->with('error', 'AI quiz score is below ' . $passingPercentage . '%. Please review the content and try again.');
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

        return $startDate && $this->teachingPlanWeekMeetsAiTrainingStart($item->week, $startDate);
    }

    private function teachingPlanWeekMeetsAiTrainingStart($week, string $startDate): bool
    {
        foreach ([$week->release_date ?? null, $week->week_start_date ?? null] as $date) {
            if ($date && \Carbon\Carbon::parse($date)->toDateString() >= $startDate) {
                return true;
            }
        }

        return false;
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

        return TeachingPlanItem::with(['week', 'plan'])
            ->whereIn('content_id', $contentIds)
            ->whereHas('week', function ($query) {
                $query->whereNotNull('release_date');
            })
            ->whereHas('plan', function ($query) use ($student, $assignedClass) {
                $query->where('is_template', false)
                    ->where('institute', $student->institute)
                    ->whereNotNull('ai_training_start_date')
                    ->whereIn('status', ['active', 'completed'])
                    ->where(function ($classQuery) use ($assignedClass) {
                        $classQuery
                            ->whereRaw(
                                "REPLACE(TRIM(class), '  ', ' ') = ?",
                                [$assignedClass]
                            )
                            ->orWhereRaw(
                                "REPLACE(TRIM(CONCAT(COALESCE(class, ''), ' ', COALESCE(section, ''))), '  ', ' ') = ?",
                                [$assignedClass]
                            );
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
                'title' => ($audience == 'teacher' ? 'AI Prep - ' : 'AI Quiz - ') . ($gradeLevel ? $gradeLevel . ' - ' : '') . $quizContent->content_title,
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

        $teachingPlanContentIds = TeachingPlanItem::where('status', 'completed')
            ->whereNotNull('content_id')
            ->whereHas('plan', function ($query) use ($student, $assignedClass) {
                $query->where('is_template', false)
                    ->where('institute', $student->institute)
                    ->whereIn('status', ['active', 'completed'])
                    ->where(function ($classQuery) use ($assignedClass) {
                        $classQuery
                            ->whereRaw(
                                "REPLACE(TRIM(class), '  ', ' ') = ?",
                                [$assignedClass]
                            )
                            ->orWhereRaw(
                                "REPLACE(TRIM(CONCAT(COALESCE(class, ''), ' ', COALESCE(section, ''))), '  ', ' ') = ?",
                                [$assignedClass]
                            );
                    });
            })
            ->whereHas('content', function ($query) {
                $query->where('status', 1);
            })
            ->pluck('content_id')
            ->unique()
            ->values();

        $legacyCourseIds = Course::where('institute', $student->institute)
            ->whereRaw(
                "REPLACE(TRIM(assigned_class), '  ', ' ') = ?",
                [$assignedClass]
            )
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

        $classMatches = $this->studentClassName($student) ==
            preg_replace('/\s+/', ' ', trim((string) $assessment->assigned_class));

        if (!$classMatches) {
            return false;
        }

        if ($assessment->assessment_category === 'Component Mastery') {
            return $this->studentComponentAssessmentOffers($student)
                ->pluck('component_key')
                ->contains($assessment->component_key);
        }

        return true;
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

    public function assessmentMonitoring(Request $request)
    {
        $selectedStudentClass = trim((string) $request->input('student_class', '')) ?: null;
        $selectedStudentSection = trim((string) $request->input('student_section', '')) ?: null;
        $statusFilter = trim((string) $request->input('status', ''));
        $dateFilter = trim((string) $request->input('date', ''));
        $searchFilter = trim((string) $request->input('search', ''));
        $selectedInstitute = session('user_role') == 'InstituteAdmin'
            ? session('user_institute')
            : trim((string) $request->input('institute', ''));

        $instituteOptions = Institute::where('status', 1)
            ->orderBy('institute_name')
            ->pluck('institute_name')
            ->values();

        $currentInstitute = $selectedInstitute !== '' ? $selectedInstitute : null;

        $schoolClassOptions = SchoolClass::when($currentInstitute, function ($query) use ($currentInstitute) {
                $query->where('institute', $currentInstitute);
            })
            ->whereNotNull('class_name')
            ->orderBy('class_name')
            ->pluck('class_name')
            ->map(fn ($className) => trim((string) $className));

        $studentClassOptions = Student::when($currentInstitute, function ($query) use ($currentInstitute) {
                $query->where('institute', $currentInstitute);
            })
            ->whereNotNull('class')
            ->orderBy('class')
            ->distinct()
            ->pluck('class')
            ->map(fn ($className) => trim((string) $className));

        $classOptions = $schoolClassOptions
            ->merge($studentClassOptions)
            ->filter()
            ->unique(fn ($className) => mb_strtolower($className))
            ->sort()
            ->values();

        $sectionOptions = collect();
        if ($selectedStudentClass) {
            $sectionOptions = Student::when($currentInstitute, function ($query) use ($currentInstitute) {
                    $query->where('institute', $currentInstitute);
                })
                ->where('class', $selectedStudentClass)
                ->whereNotNull('section')
                ->orderBy('section')
                ->pluck('section')
                ->map(fn ($section) => trim((string) $section))
                ->filter()
                ->unique(fn ($section) => mb_strtolower($section))
                ->values();
        }

        $sessions = AssessmentSession::with([
                'assessment',
                'student',
                'teacher',
            ])
            ->when($currentInstitute, function ($query) use ($currentInstitute) {
                $query->whereHas('assessment', function ($q) use ($currentInstitute) {
                    $q->where('institute', $currentInstitute);
                });
            })
            ->when($currentInstitute && $selectedStudentClass, function ($query) use ($currentInstitute, $selectedStudentClass, $selectedStudentSection) {
                $query->whereHas('student', function ($q) use ($currentInstitute, $selectedStudentClass, $selectedStudentSection) {
                    $q->where('institute', $currentInstitute)
                        ->where('class', $selectedStudentClass);

                    if ($selectedStudentSection) {
                        $q->where('section', $selectedStudentSection);
                    }
                });
            })
            ->when(in_array($statusFilter, ['Started', 'Submitted', 'AutoSubmitted'], true), function ($query) use ($statusFilter) {
                $query->where('status', $statusFilter);
            })
            ->when($dateFilter !== '', function ($query) use ($dateFilter) {
                $query->whereDate('started_at', $dateFilter);
            })
            ->when($searchFilter !== '', function ($query) use ($searchFilter) {
                $query->where(function ($searchQuery) use ($searchFilter) {
                    $searchQuery->whereHas('assessment', function ($assessmentQuery) use ($searchFilter) {
                        $assessmentQuery->where('assessment_title', 'like', '%' . $searchFilter . '%');
                    })
                    ->orWhereHas('student', function ($studentQuery) use ($searchFilter) {
                        $studentQuery->where('name', 'like', '%' . $searchFilter . '%')
                            ->orWhere('student_id', 'like', '%' . $searchFilter . '%');
                    })
                    ->orWhereHas('teacher', function ($teacherQuery) use ($searchFilter) {
                        $teacherQuery->where('name', 'like', '%' . $searchFilter . '%');
                    });
                });
            })
            ->latest()
            ->paginate(30)
            ->withQueryString();

        return view('assessment-monitoring', compact(
            'sessions',
            'currentInstitute',
            'selectedInstitute',
            'selectedStudentClass',
            'selectedStudentSection',
            'statusFilter',
            'dateFilter',
            'searchFilter',
            'instituteOptions',
            'classOptions',
            'sectionOptions'
        ));
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
            ->whereDoesntHave('sessions', function ($query) {
                $query->where('status', 'completed');
            })
            ->whereNotExists(function ($query) {
                $query->selectRaw('1')
                    ->from('class_content_sessions')
                    ->whereNull('class_content_sessions.teaching_plan_item_id')
                    ->where('class_content_sessions.status', 'completed')
                    ->whereColumn('class_content_sessions.teaching_plan_id', 'teaching_plan_items.teaching_plan_id')
                    ->whereColumn('class_content_sessions.teaching_plan_week_id', 'teaching_plan_items.teaching_plan_week_id')
                    ->whereColumn('class_content_sessions.content_id', 'teaching_plan_items.content_id');
            })
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
        $reportType = $this->resolveClassSessionReportType($request);
        $sectionPager = null;
        $currentInstitute = null;
        $selectedReportInstitute = null;
        $reportInstituteOptions = collect();
        $hasFilters = $request->filled('report_date')
            || $request->filled('institute')
            || $request->filled('report_month')
            || $request->filled('from_date')
            || $request->filled('to_date');

        if (in_array(session('user_role'), ['Admin', 'Manager'], true)) {
            $selectedReportInstitute = $request->filled('institute')
                ? trim((string) $request->input('institute'))
                : null;

            $reportInstituteOptions = Institute::where('status', 1)
                ->orderBy('institute_name')
                ->pluck('institute_name');

            ['currentInstitute' => $currentInstitute, 'sectionPager' => $sectionPager] =
                $this->buildInstituteSectionPager(
                    $request,
                    $request->route()?->getName() ?: 'admin.class-session.report.weekly'
                );

        }

        $sessions = $this->classSessionReportQuery($request)
            ->latest()
            ->paginate(30)
            ->withQueryString();

        return view('class-session-report', compact(
            'sessions',
            'reportType',
            'sectionPager',
            'hasFilters',
            'selectedReportInstitute',
            'reportInstituteOptions'
        ));
    }

    public function downloadClassSessionReportPdf(Request $request, GeminiAiService $ai)
    {
        $reportType = $this->resolveClassSessionReportType($request);
        $sectionPager = null;
        $currentInstitute = null;

        if (in_array(session('user_role'), ['Admin', 'Manager'], true)) {
            $routeName = (string) ($request->route()?->getName() ?: 'admin.class-session.report.weekly');
            $routePrefix = str_starts_with($routeName, 'manager.') ? 'manager' : 'admin';
            ['currentInstitute' => $currentInstitute, 'sectionPager' => $sectionPager] =
                $this->buildInstituteSectionPager(
                    $request,
                    $routePrefix . '.class-session.report.' . $reportType . '.download'
                );

        }

        $sessions = $this->classSessionReportQuery($request)
            ->orderBy('institute')
            ->orderBy('class')
            ->orderBy('section')
            ->orderBy('session_date')
            ->get();

        $scope = in_array(session('user_role'), ['InstituteAdmin', 'Principal'], true)
            ? session('user_institute')
            : (
                $request->filled('institute')
                    ? trim((string) $request->input('institute'))
                    : ($currentInstitute ?: 'All Institutes')
            );

        $periodLabel = match ($reportType) {
            'daily' => \Carbon\Carbon::parse($request->input('report_date', now()->toDateString()))->format('d M Y'),
            'monthly' => \Carbon\Carbon::parse($request->input('report_month', now()->format('Y-m')) . '-01')->format('F Y'),
            default => ($request->filled('from_date') || $request->filled('to_date'))
                ? trim(($request->filled('from_date') ? \Carbon\Carbon::parse($request->from_date)->format('d M Y') : 'Start') . ' - ' . ($request->filled('to_date') ? \Carbon\Carbon::parse($request->to_date)->format('d M Y') : 'Today'))
                : 'All available sessions',
        };

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

        $title = match ($reportType) {
            'daily' => 'Daily Session Report',
            'monthly' => 'Monthly Session Report',
            default => 'Weekly Session Report',
        };

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
        $reportType = $this->resolveClassSessionReportType($request);
        $dailyReportDate = $reportType == 'daily'
            ? \Carbon\Carbon::parse($request->input('report_date', now()->toDateString()))->toDateString()
            : null;
        $monthlyReportStart = $reportType == 'monthly'
            ? \Carbon\Carbon::parse($request->input('report_month', now()->format('Y-m')) . '-01')->startOfMonth()->toDateString()
            : null;
        $monthlyReportEnd = $reportType == 'monthly'
            ? \Carbon\Carbon::parse($request->input('report_month', now()->format('Y-m')) . '-01')->endOfMonth()->toDateString()
            : null;

        return ClassContentSession::with([
                'schoolClass',
                'content',
                'course',
                'teachingPlan',
                'stemEngineer',
            ])
            ->when(in_array(session('user_role'), ['Admin', 'Manager'], true) && $request->filled('institute'), function ($query) use ($request) {
                $query->where('institute', $request->input('institute'));
            })
            ->when($reportType == 'daily', function ($query) use ($dailyReportDate) {
                $query->whereDate('session_date', $dailyReportDate);
            })
            ->when($reportType == 'monthly', function ($query) use ($monthlyReportStart, $monthlyReportEnd) {
                $query->whereDate('session_date', '>=', $monthlyReportStart)
                    ->whereDate('session_date', '<=', $monthlyReportEnd);
            })
            ->when($reportType == 'weekly' && $request->filled('from_date'), function ($query) use ($request) {
                $query->whereDate('session_date', '>=', $request->from_date);
            })
            ->when($reportType == 'weekly' && $request->filled('to_date'), function ($query) use ($request) {
                $query->whereDate('session_date', '<=', $request->to_date);
            })
            ->when(in_array(session('user_role'), ['Admin', 'Manager'], true) && $request->filled('institute'), function ($query) use ($request) {
                $query->where('institute', $request->institute);
            })
            ->when(in_array(session('user_role'), ['InstituteAdmin', 'Principal'], true), function ($query) {
                $query->where('institute', session('user_institute'));
            });
    }

    private function resolveClassSessionReportType(Request $request): string
    {
        if (str_contains((string) $request->route()?->getName(), '.daily')) {
            return 'daily';
        }

        if (str_contains((string) $request->route()?->getName(), '.monthly')) {
            return 'monthly';
        }

        return in_array(($request->route('reportType') ?? $request->query('report_type')), ['daily', 'monthly'], true)
            ? ($request->route('reportType') ?? $request->query('report_type'))
            : 'weekly';
    }

    public function assessmentReviewMonitoring(Request $request)
    {
        $instituteOptions = Institute::where('status', 1)
            ->orderBy('institute_name')
            ->pluck('institute_name')
            ->values();

        $selectedInstitute = session('user_role') == 'InstituteAdmin'
            ? session('user_institute')
            : $request->input('institute');

        $selectedStudentClass = $request->input('student_class');
        $selectedStudentSection = $request->input('student_section');
        $statusFilter = $request->input('status');
        $searchFilter = $request->input('search');

        $reviewClassOptions = collect();
        $reviewSectionOptions = collect();

        if ($selectedInstitute) {
            $reviewClassOptions = SchoolClass::where('institute', $selectedInstitute)
                ->orderBy('class_name')
                ->pluck('class_name')
                ->map(fn ($className) => trim((string) $className))
                ->filter()
                ->unique(fn ($className) => mb_strtolower($className))
                ->values();
        }

        if ($selectedInstitute && $selectedStudentClass) {
            $reviewSectionOptions = Student::where('institute', $selectedInstitute)
                ->where('class', $selectedStudentClass)
                ->whereNotNull('section')
                ->orderBy('section')
                ->pluck('section')
                ->map(fn ($section) => trim((string) $section))
                ->filter()
                ->unique(fn ($section) => mb_strtolower($section))
                ->values();
        }

        $hasFilters = $request->filled('institute')
            || $request->filled('student_class')
            || $request->filled('student_section')
            || $request->filled('status')
            || $request->filled('search')
            || session('user_role') == 'InstituteAdmin';

        $results = collect();

        if ($hasFilters && $selectedInstitute) {
            $results = AssessmentResult::with([
                'student',
                'assessment',
            ])
                ->whereHas('student', function ($query) use ($selectedInstitute, $selectedStudentClass, $selectedStudentSection, $searchFilter) {
                    $query->where('institute', $selectedInstitute)
                        ->when($selectedStudentClass, fn ($innerQuery) => $innerQuery->where('class', $selectedStudentClass))
                        ->when($selectedStudentSection, fn ($innerQuery) => $innerQuery->where('section', $selectedStudentSection))
                        ->when($searchFilter, function ($innerQuery) use ($searchFilter) {
                            $innerQuery->where(function ($searchQuery) use ($searchFilter) {
                                $searchQuery->where('name', 'like', '%' . $searchFilter . '%')
                                    ->orWhere('student_id', 'like', '%' . $searchFilter . '%');
                            });
                        });
                })
                ->when($statusFilter, fn ($query) => $query->where('status', $statusFilter))
                ->latest()
                ->paginate(30)
                ->withQueryString();
        }

        return view(
            'assessment-review-monitoring',
            compact(
                'results',
                'instituteOptions',
                'selectedInstitute',
                'selectedStudentClass',
                'selectedStudentSection',
                'statusFilter',
                'searchFilter',
                'reviewClassOptions',
                'reviewSectionOptions'
            ) + ['showFilterPlaceholder' => ! $hasFilters || ! $selectedInstitute]
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
