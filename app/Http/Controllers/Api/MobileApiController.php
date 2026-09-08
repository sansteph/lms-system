<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Controllers\ReportController;
use App\Models\Assessment;
use App\Models\AssessmentSession;
use App\Models\AssessmentResult;
use App\Models\AiContentSummary;
use App\Models\AiQuiz;
use App\Models\AiQuizAnswer;
use App\Models\AiQuizAttempt;
use App\Models\AiComponentContentProfile;
use App\Models\AiQuizQuestion;
use App\Models\Certificate;
use App\Models\ClassContentSession;
use App\Models\Content;
use App\Models\Course;
use App\Models\CourseEnrollment;
use App\Models\Institute;
use App\Models\IndependentLearner;
use App\Models\LmsNotification;
use App\Models\LessonProgress;
use App\Models\MobilePushToken;
use App\Models\MySpace;
use App\Models\PendingPasswordChange;
use App\Models\SchoolClass;
use App\Models\Student;
use App\Models\StudentAchievement;
use App\Models\TeachingPlan;
use App\Models\TeachingPlanItem;
use App\Models\TeachingPlanWeek;
use App\Models\User;
use App\Models\UserActivityLog;
use App\Models\UserSession;
use App\Models\TeacherAchievement;
use App\Services\FirebasePushService;
use App\Services\TeachingPlanReleaseService;
use App\Services\TeachingPlanBuilderService;
use App\Services\Ai\GeminiAiService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;
use Illuminate\Validation\ValidationException;
use Throwable;

class MobileApiController extends Controller
{
    private const AI_STUDENT_ATTEMPT_TYPE = 'student';

    private function teacherAiPassingPercentage(): float
    {
        return (float) config('ai.content.teacher_passing_percentage', 50);
    }

    private function studentAiPassingPercentage(): float
    {
        return (float) config('ai.content.student_passing_percentage', 60);
    }

    private function studentAccessibleContent(Student $student, int $contentId): ?Content
    {
        if (!app(\App\Services\MobileContentAccess::class)->availableIds($student)->contains($contentId)) {
            return null;
        }
        return Content::with(['aiSummary', 'courseContent.sourceTemplateContent.aiSummary'])
            ->where('status', 1)->find($contentId);
    }

    public function login(Request $request)
    {
        $validated = $request->validate([
            'email' => ['required', 'string'],
            'password' => ['required', 'string'],
            'role' => ['nullable', 'string'],
        ]);

        $role = trim((string) ($validated['role'] ?? ''));
        $email = trim((string) $validated['email']);
        $password = (string) $validated['password'];

        $account = $this->resolveAccount($email, $password, $role);

        if (!$account) {
            return response()->json([
                'success' => false,
                'message' => 'The provided credentials do not match our records.',
            ], 401);
        }

        [$tokenable, $responseRole, $institute, $avatar, $userId, $name] = $account;

        if ($responseRole === 'Student') {
            if (blank($tokenable->email)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Student guardian email verification is not configured.',
                ], 422);
            }

            try {
                $challengeToken = app(\App\Services\MfaChallengeService::class)
                    ->issue($tokenable, 'email', $request->ip(), $request->userAgent());
            } catch (\Throwable $exception) {
                report($exception);
                return response()->json([
                    'success' => false,
                    'message' => 'Student verification is temporarily unavailable.',
                ], 503);
            }

            return response()->json([
                'success' => false,
                'mfa_required' => true,
                'mfa_provider' => 'email',
                'challenge_token' => $challengeToken,
                'user_id' => $userId,
                'name' => $name,
                'role' => $responseRole,
                'institute' => $institute ?? '',
                'email_hint' => $this->emailHint((string) $tokenable->email),
            ], 202);
        }

        if ($tokenable instanceof User && $tokenable->mfa_enabled) {
            try {
                $challengeToken = app(\App\Services\MfaChallengeService::class)
                    ->issue($tokenable, 'email', $request->ip(), $request->userAgent());
            } catch (\Throwable $exception) {
                report($exception);
                return response()->json([
                    'success' => false,
                    'message' => 'Verification is temporarily unavailable.',
                ], 503);
            }

            return response()->json([
                'success' => false,
                'mfa_required' => true,
                'mfa_provider' => 'email',
                'challenge_token' => $challengeToken,
                'user_id' => $userId,
                'name' => $name,
                'role' => $responseRole,
                'institute' => $institute ?? '',
                'email_hint' => $this->emailHint((string) $tokenable->email),
            ], 202);
        }

        $token = $tokenable->createToken('flutter-mobile-app')->plainTextToken;

        return response()->json([
            'success' => true,
            'message' => 'Login successful',
            'token' => $token,
            'user_id' => $userId,
            'name' => $name,
            'email' => $tokenable->email ?? $email,
            'role' => $responseRole,
            'institute' => $institute ?? '',
            'avatar' => $this->publicStorageUrl($avatar),
            'login_notifications' => app(\App\Services\LmsNotificationService::class)->mobileLoginNotifications($tokenable),
            ]);
    }

    public function verifyStudentMfa(Request $request)
    {
        $validated = $request->validate([
            'challenge_token' => ['required', 'string'],
            'code' => ['required', 'digits:6'],
        ]);

        $challenge = \App\Models\MfaChallenge::where('challenge_token_hash', hash('sha256', $validated['challenge_token']))->first();
        if (!$challenge || $challenge->account_type !== Student::class) {
            return response()->json(['success' => false, 'message' => 'The verification challenge is invalid or expired.'], 401);
        }

        $student = Student::find($challenge->account_id);
        if (!$student || !$student->status) {
            return response()->json(['success' => false, 'message' => 'The verification challenge is invalid or expired.'], 401);
        }

        try {
            app(\App\Services\MfaChallengeService::class)->verify(
                $validated['challenge_token'],
                $validated['code'],
                $request->ip(),
                $request->userAgent(),
            );
        } catch (ValidationException $exception) {
            return response()->json(['success' => false, 'message' => 'The verification code is invalid or expired.'], 401);
        }

        $token = $student->createToken('flutter-mobile-app')->plainTextToken;

        return response()->json([
            'success' => true,
            'message' => 'Login successful',
            'token' => $token,
            'user_id' => $student->id,
            'name' => $student->name,
            'email' => $student->email ?? '',
            'role' => 'Student',
            'institute' => $student->institute ?? '',
            'avatar' => $this->publicStorageUrl($student->profile_image ?? null),
            'login_notifications' => app(\App\Services\LmsNotificationService::class)->mobileLoginNotifications($student),
        ]);
    }

    public function verifyMobileMfa(Request $request)
    {
        $validated = $request->validate([
            'challenge_token' => ['required', 'string'],
            'code' => ['required', 'digits:6'],
        ]);

        $challenge = \App\Models\MfaChallenge::where(
            'challenge_token_hash',
            hash('sha256', $validated['challenge_token'])
        )->first();
        if (!$challenge || !in_array($challenge->account_type, [Student::class, User::class], true)) {
            return response()->json(['success' => false, 'message' => 'The verification challenge is invalid or expired.'], 401);
        }

        $account = $challenge->account_type::find($challenge->account_id);
        if (!$account || !($account instanceof Student || ($account instanceof User && $account->status))) {
            return response()->json(['success' => false, 'message' => 'The verification challenge is invalid or expired.'], 401);
        }

        try {
            app(\App\Services\MfaChallengeService::class)->verify(
                $validated['challenge_token'],
                $validated['code'],
                $request->ip(),
                $request->userAgent(),
            );
        } catch (ValidationException $exception) {
            return response()->json(['success' => false, 'message' => 'The verification code is invalid or expired.'], 401);
        }

        $role = $account instanceof Student ? 'Student' : $this->displayRoleFor($account);
        return response()->json([
            'success' => true,
            'message' => 'Login successful',
            'token' => $account->createToken('flutter-mobile-app')->plainTextToken,
            'user_id' => $account->id,
            'name' => $account->name,
            'email' => $account->email ?? '',
            'role' => $role,
            'institute' => $account->institute ?? '',
            'avatar' => $this->publicStorageUrl($account instanceof Student ? ($account->profile_image ?? null) : null),
            'login_notifications' => app(\App\Services\LmsNotificationService::class)->mobileLoginNotifications($account),
        ]);
    }

    public function mobileMfaStatus(Request $request)
    {
        $account = $request->user();
        abort_unless($account instanceof User, 403);

        return response()->json([
            'enabled' => (bool) $account->mfa_enabled,
            'channel' => 'email',
            'email_hint' => $this->emailHint((string) $account->email),
        ]);
    }

    public function beginMobileMfaSetup(Request $request)
    {
        $account = $request->user();
        abort_unless($account instanceof User, 403);
        $request->validate(['current_password' => ['required', 'string']]);
        if (!Hash::check($request->current_password, $account->password)) {
            return response()->json(['message' => 'The current password is incorrect.'], 422);
        }

        try {
            $token = app(\App\Services\MfaChallengeService::class)
                ->issue($account, 'email', $request->ip(), $request->userAgent());
        } catch (ValidationException $exception) {
            return response()->json(['message' => 'We could not send a verification code right now. Please try again later.'], 503);
        }

        return response()->json([
            'message' => 'A verification code was sent to your registered email.',
            'challenge_token' => $token,
            'email_hint' => $this->emailHint((string) $account->email),
        ], 202);
    }

    public function verifyMobileMfaSetup(Request $request)
    {
        $account = $request->user();
        abort_unless($account instanceof User, 403);
        $validated = $request->validate([
            'challenge_token' => ['required', 'string'],
            'code' => ['required', 'digits:6'],
        ]);
        try {
            app(\App\Services\MfaChallengeService::class)->verify(
                $validated['challenge_token'], $validated['code'], $request->ip(), $request->userAgent()
            );
        } catch (ValidationException $exception) {
            return response()->json(['message' => 'The verification code is invalid or expired.'], 422);
        }
        $account->update(['mfa_enabled' => true]);
        \App\Models\MfaAuditLog::create([
            'account_type' => User::class,
            'account_id' => $account->id,
            'event' => 'enabled',
            'channel' => 'email',
            'successful' => true,
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ]);
        return response()->json(['message' => 'Two-factor authentication is now enabled.', 'enabled' => true]);
    }

    public function disableMobileMfa(Request $request)
    {
        $account = $request->user();
        abort_unless($account instanceof User, 403);
        $request->validate(['current_password' => ['required', 'string']]);
        if (!Hash::check($request->current_password, $account->password)) {
            return response()->json(['message' => 'The current password is incorrect.'], 422);
        }
        $account->update(['mfa_enabled' => false]);
        \App\Models\MfaAuditLog::create([
            'account_type' => User::class,
            'account_id' => $account->id,
            'event' => 'disabled',
            'channel' => 'email',
            'successful' => true,
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ]);
        return response()->json(['message' => 'Two-factor authentication has been disabled.', 'enabled' => false]);
    }

    public function forgotPassword(Request $request)
    {
        $validated = $request->validate([
            'email' => ['required', 'email'],
            'role' => ['required', 'string'],
        ]);

        $role = trim($validated['role']);
        if (!in_array($role, ['Admin', 'STEM Engineer', 'Manager', 'Principal'], true)) {
            return response()->json([
                'success' => false,
                'message' => 'Student accounts cannot use password reset here.',
            ], 403);
        }

        $userRole = match ($role) {
            'STEM Engineer' => ['Teacher', 'STEM Engineer'],
            'Manager' => ['Manager'],
            'Principal' => ['Principal'],
            default => ['Admin', 'InstituteAdmin'],
        };
        $user = User::where('email', $validated['email'])
            ->whereIn('role', $userRole)
            ->where('status', 1)
            ->first();

        if (!$user) {
            return response()->json([
                'success' => true,
                'message' => 'If an active account exists for that email, a reset link has been sent.',
            ]);
        }

        return $this->sendPasswordResetLink($user);
    }

    public function registerHybridLearner(Request $request)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'unique:independent_learners,email'],
            'phone' => ['nullable', 'string', 'max:20'],
            'password' => ['required', 'string', 'min:6', 'confirmed'],
        ]);

        IndependentLearner::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'phone' => $validated['phone'] ?? null,
            'password' => Hash::make($validated['password']),
            'status' => true,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Registration successful. Please sign in.',
        ], 201);
    }

    public function changePassword(Request $request)
    {
        $account = $request->user();
        $role = $this->displayRoleFor($account);

        if (!in_array($role, ['Admin', 'STEM Engineer', 'Manager', 'Principal'], true)) {
            return response()->json([
                'success' => false,
                'message' => 'Student accounts cannot change password from this screen.',
            ], 403);
        }

        $validated = $request->validate([
            'current_password' => ['required', 'string'],
            'new_password' => ['required', 'string', 'min:6', 'confirmed'],
        ]);

        $user = User::findOrFail($account->id);

        if (!Hash::check($validated['current_password'], (string) $user->password)) {
            return response()->json([
                'success' => false,
                'message' => 'Current password is incorrect.',
            ], 422);
        }

        return $this->sendPasswordChangeConfirmation($user, $validated['new_password']);
    }

    public function profile(Request $request)
    {
        $account = $request->user();

        return response()->json([
            'success' => true,
            'user_id' => $account->id,
            'name' => $account->name ?? '',
            'email' => $account->email ?? '',
            'role' => $this->displayRoleFor($account),
            'institute' => $account->institute ?? '',
            'avatar' => $this->publicStorageUrl($account->profile_image ?? $account->avatar ?? null),
        ]);
    }

    public function engineerProfile(Request $request)
    {
        $profile = $this->profile($request);
        $data = $profile->getData(true);

        $data['title'] = 'Profile';
        $data['summary'] = 'View and manage STEM Engineer account details.';
        $data['sections'] = [
            [
                'title' => 'Account',
                'subtitle' => 'Name, email, role and institute access.',
            ],
            [
                'title' => 'Security',
                'subtitle' => 'Use change password to update login credentials.',
            ],
        ];
        $teacher = $request->user();
        $data['profile_fields'] = [
            'user_id' => $teacher->user_id ?? '',
            'designation' => $teacher->designation ?? '',
            'qualification' => $teacher->qualification ?? '',
            'joined_on' => $teacher->joined_on ? Carbon::parse($teacher->joined_on)->toDateString() : null,
            'linkedin_url' => $teacher->linkedin_url ?? '',
        ];

        return response()->json($data);
    }

    public function updateEngineerProfile(Request $request)
    {
        $teacher = $request->user();
        abort_unless($teacher instanceof User && in_array($teacher->role, ['Teacher', 'STEM Engineer'], true), 403, 'STEM Engineer access is required.');

        $validated = $request->validate([
            'user_id' => ['required', 'string', 'max:255', 'unique:users,user_id,' . $teacher->id],
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email,' . $teacher->id],
            'designation' => ['nullable', 'string', 'max:255'],
            'qualification' => ['required', 'string', 'max:255'],
            'joined_on' => ['nullable', 'date'],
            'linkedin_url' => ['nullable', 'url', 'max:255'],
        ]);

        $teacher->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'Profile updated successfully.',
        ]);
    }

    public function engineerNotifications(Request $request)
    {
        $account = $request->user();
        $institute = $account->institute ?? null;
        $today = now()->toDateString();

        $notifications = LmsNotification::query()
            ->where('status', 'active')
            ->whereIn('target', ['all', 'teachers'])
            ->where(function ($query) use ($institute) {
                $query->whereNull('institute')
                    ->when($institute, fn ($scope) => $scope->orWhere('institute', $institute));
            })
            ->where(function ($query) use ($today) {
                $query->whereNull('starts_at')
                    ->orWhereDate('starts_at', '<=', $today);
            })
            ->where(function ($query) use ($today) {
                $query->whereNull('expires_at')
                    ->orWhereDate('expires_at', '>=', $today);
            })
            ->latest()
            ->get()
            ->map(function (LmsNotification $notification) {
                return [
                    'id' => $notification->id,
                    'title' => $notification->title,
                    'message' => $notification->message,
                    'status' => $notification->status,
                    'target' => $notification->target,
                    'institute' => $notification->institute,
                    'created_at' => optional($notification->created_at)->toDateTimeString(),
                ];
            })
            ->values();

        return response()->json([
            'title' => 'Notifications',
            'description' => 'Live notifications for STEM Engineers.',
            'notifications' => $notifications,
        ]);
    }

    public function hybridLearnerCourses(Request $request)
    {
        $learner = $this->requireHybridLearner($request);
        $enrolledCourseIds = CourseEnrollment::where('learner_id', $learner->id)->pluck('course_id');

        $courses = Course::query()
            ->where('is_active', 1)
            ->whereIn('availability_type', ['Independent', 'Both'])
            ->latest()
            ->get()
            ->map(function (Course $course) use ($enrolledCourseIds) {
                return [
                    'id' => $course->id,
                    'title' => $course->course_title ?? 'Course',
                    'subtitle' => $course->description ?? $course->target ?? '',
                    'status' => $enrolledCourseIds->contains($course->id) ? 'Enrolled' : 'Available',
                    'can_enroll' => !$enrolledCourseIds->contains($course->id),
                ];
            })
            ->values();

        return response()->json([
            'title' => 'Courses',
            'description' => 'Browse self-paced courses available for Hybrid Learners.',
            'courses' => $courses,
        ]);
    }

    public function hybridLearnerEnroll(Request $request, int $courseId)
    {
        $learner = $this->requireHybridLearner($request);
        $course = Course::query()
            ->where('is_active', 1)
            ->whereIn('availability_type', ['Independent', 'Both'])
            ->findOrFail($courseId);

        $enrollment = CourseEnrollment::firstOrCreate(
            [
                'learner_id' => $learner->id,
                'course_id' => $course->id,
            ],
            [
                'payment_status' => 'Pending',
                'enrolled_at' => now(),
            ]
        );

        return response()->json([
            'success' => true,
            'message' => $enrollment->wasRecentlyCreated
                ? 'Course enrolled successfully.'
                : 'You are already enrolled in this course.',
            'enrollment_id' => $enrollment->id,
        ]);
    }

    public function hybridLearnerEnrollments(Request $request)
    {
        $learner = $this->requireHybridLearner($request);

        $enrollments = CourseEnrollment::with('course')
            ->where('learner_id', $learner->id)
            ->latest()
            ->get()
            ->map(function (CourseEnrollment $enrollment) {
                $course = $enrollment->course;

                return [
                    'id' => $enrollment->id,
                    'course_id' => $course?->id,
                    'title' => $course?->course_title ?? 'Course',
                    'subtitle' => trim(($enrollment->payment_status ?? 'Pending') . ' · ' . ($enrollment->enrolled_at ? Carbon::parse($enrollment->enrolled_at)->format('Y-m-d') : '')),
                    'status' => $enrollment->is_completed ? 'Completed' : 'In Progress',
                    'progress' => $this->hybridCourseProgress($enrollment),
                ];
            })
            ->values();

        return response()->json([
            'title' => 'My Enrollments',
            'description' => 'Continue enrolled courses and track completion.',
            'enrollments' => $enrollments,
        ]);
    }

    public function hybridLearnerCourseLessons(Request $request, int $courseId)
    {
        $learner = $this->requireHybridLearner($request);
        $enrollment = CourseEnrollment::where('learner_id', $learner->id)
            ->where('course_id', $courseId)
            ->firstOrFail();

        $lessons = Content::query()
            ->where('course_id', $courseId)
            ->where('status', 1)
            ->where('is_released', true)
            ->where(function ($query) {
                $query->whereNotNull('student_file_path')
                    ->orWhereNotNull('file_path');
            })
            ->orderBy('lesson_order')
            ->get();

        $completedIds = LessonProgress::where('independent_learner_id', $learner->id)
            ->whereIn('content_id', $lessons->pluck('id'))
            ->where('is_completed', true)
            ->pluck('content_id');

        return response()->json([
            'title' => $enrollment->course?->course_title ?? 'Course Lessons',
            'description' => 'Released lessons for this Hybrid Learner course.',
            'progress' => $this->hybridCourseProgress($enrollment),
            'lessons' => $lessons
                ->map(function (Content $content) use ($completedIds) {
                    return [
                        'id' => $content->id,
                        'title' => $content->content_title ?? 'Lesson',
                        'summary' => $content->description ?? $content->summary ?? '',
                        'status' => $completedIds->contains($content->id) ? 'Completed' : 'Available',
                        'file_url' => $content->student_file_path ?? $content->file_path ?? '',
                    ];
                })
                ->values(),
        ]);
    }

    public function completeHybridLearnerLesson(Request $request, int $contentId)
    {
        $learner = $this->requireHybridLearner($request);
        $content = Content::findOrFail($contentId);

        $enrollment = CourseEnrollment::where('learner_id', $learner->id)
            ->where('course_id', $content->course_id)
            ->firstOrFail();

        LessonProgress::updateOrCreate(
            [
                'independent_learner_id' => $learner->id,
                'content_id' => $content->id,
            ],
            [
                'is_completed' => true,
                'completed_at' => now(),
            ]
        );

        $this->syncHybridLearnerCourseCompletion($learner, $enrollment);

        return response()->json([
            'success' => true,
            'message' => 'Lesson marked as completed.',
        ]);
    }

    public function hybridLearnerCertificates(Request $request)
    {
        $learner = $this->requireHybridLearner($request);

        $certificates = Certificate::with('course')
            ->where('independent_learner_id', $learner->id)
            ->where('certificate_type', 'Independent')
            ->latest()
            ->get()
            ->map(function (Certificate $certificate) {
                return [
                    'id' => $certificate->id,
                    'title' => $certificate->course?->course_title ?? 'Certificate',
                    'subtitle' => trim(($certificate->certificate_code ?? '') . ' · ' . ($certificate->issued_date ?? '')),
                    'status' => $certificate->status ?? 'Issued',
                ];
            })
            ->values();

        return response()->json([
            'title' => 'Certificates',
            'description' => 'Issued certificates for completed Hybrid Learner courses.',
            'certificates' => $certificates,
        ]);
    }

    public function studentProfile(Request $request)
    {
        $account = $request->user();
        abort_unless($account instanceof Student, 403);
        $student = $account;

        return response()->json([
            'success' => true,
            'title' => 'Profile',
            'summary' => 'View your student account details.',
            'name' => $student->name ?? $account->name ?? '',
            'email' => $student->email ?? $account->email ?? '',
            'role' => 'Student',
            'institute' => $student->institute ?? $account->institute ?? '',
            'avatar' => $this->publicStorageUrl($student->profile_image ?? $account->profile_image ?? $account->avatar ?? null),
            'profile_fields' => [
                'linkedin_url' => $student->linkedin_url ?? '',
            ],
            'sections' => [
                ['title' => 'Account', 'subtitle' => 'Student profile and account details.'],
                ['title' => 'Security', 'subtitle' => 'Use forgot password if you need access help.'],
            ],
        ]);
    }

    public function updateStudentProfile(Request $request)
    {
        $account = $request->user();
        abort_unless($account instanceof Student, 403);
        $student = $account;

        $validated = $request->validate([
            'linkedin_url' => ['nullable', 'url', 'max:255'],
        ]);
        $student->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'Profile updated successfully.',
        ]);
    }

    public function studentNotifications(Request $request)
    {
        $account = $request->user();
        $student = Student::query()
            ->where('email', $account->email)
            ->orWhere('user_id', $account->id)
            ->first();

        $institute = $student->institute ?? $account->institute ?? null;
        $today = now()->toDateString();

        $notifications = LmsNotification::query()
            ->where('status', 'active')
            ->whereIn('target', ['all', 'students'])
            ->where(function ($query) use ($institute) {
                $query->whereNull('institute')
                    ->when($institute, fn ($scope) => $scope->orWhere('institute', $institute));
            })
            ->where(function ($query) use ($today) {
                $query->whereNull('starts_at')
                    ->orWhereDate('starts_at', '<=', $today);
            })
            ->where(function ($query) use ($today) {
                $query->whereNull('expires_at')
                    ->orWhereDate('expires_at', '>=', $today);
            })
            ->latest()
            ->get()
            ->map(function (LmsNotification $notification) {
                return [
                    'id' => $notification->id,
                    'title' => $notification->title,
                    'message' => $notification->message,
                    'status' => $notification->status,
                    'target' => $notification->target,
                    'institute' => $notification->institute,
                    'created_at' => optional($notification->created_at)->toDateTimeString(),
                ];
            })
            ->values();

        return response()->json([
            'title' => 'Notifications',
            'description' => 'Live notifications for students.',
            'notifications' => $notifications,
        ]);
    }

    public function studentMySpace(Request $request)
    {
        $account = $request->user();
        abort_unless($account instanceof Student, 403);

        $posts = $this->legacyPage(MySpace::query()
            ->where('created_by_type', 'Student')
            ->where('created_by_id', $account->id)
            ->latest(), 'posts')
            ->map(function (MySpace $post) {
                return [
                    'id' => $post->id,
                    'title' => $post->title ?? 'My Space post',
                    'subtitle' => trim(($post->type ? $post->type . ' · ' : '') . ($post->status ?? '')),
                    'status' => $post->status ?? '',
                    'created_at' => optional($post->created_at)->toDateTimeString(),
                ];
            })
            ->values();

        return $this->legacyResponse([
            'title' => 'My Space',
            'description' => 'Review student My Space submissions.',
            'posts' => $posts,
        ]);
    }

    public function engineerMySpace(Request $request)
    {
        $account = $request->user();
        $institute = $account->institute ?? null;

        $posts = $this->legacyPage(MySpace::query()
            ->where('created_by_type', 'Teacher')
            ->where('created_by_id', $account->id)
            ->latest(), 'posts')
            ->map(function (MySpace $post) {
                return [
                    'id' => $post->id,
                    'title' => $post->title ?? $post->subject ?? 'My Space post',
                    'subtitle' => trim(($post->type ? $post->type . ' · ' : '') . ($post->status ?? '')),
                    'status' => $post->status ?? '',
                    'created_at' => optional($post->created_at)->toDateTimeString(),
                ];
            })
            ->values();

        return $this->legacyResponse([
            'title' => 'My Space',
            'description' => 'Review STEM Engineer My Space submissions.',
            'posts' => $posts,
        ]);
    }

    public function engineerAchievements(Request $request)
    {
        $account = $request->user();

        $achievements = $this->legacyPage(TeacherAchievement::query()
            ->where('user_id', $account->id)
            ->latest(), 'achievements')
            ->map(function (TeacherAchievement $achievement) {
                return [
                    'id' => $achievement->id,
                    'title' => $achievement->title,
                    'subtitle' => $achievement->description ?? '',
                    'status' => $achievement->verification_status ?? 'Pending',
                    'created_at' => optional($achievement->created_at)->toDateTimeString(),
                ];
            })
            ->values();

        return $this->legacyResponse([
            'title' => 'Achievements',
            'description' => 'View STEM Engineer achievements and submissions.',
            'achievements' => $achievements,
        ]);
    }

    public function aiChatAsk(Request $request, GeminiAiService $geminiAiService)
    {
        abort_if(
            $request->user() instanceof Student,
            403,
            'Students can use the AI chatbot only from the home page.'
        );

        $validated = $request->validate([
            'message' => ['required', 'string', 'max:1200'],
        ]);

        $account = $request->user();
        $role = $this->displayRoleFor($account);
        $contentIds = app(\App\Services\MobileContentAccess::class)->unlockedIds($account);

        $contextItems = AiContentSummary::with('content')
            ->whereIn('content_id', $contentIds)
            ->where('status', 'generated')
            ->latest('generated_at')
            ->take(12)
            ->get()
            ->map(fn (AiContentSummary $summary) => [
                'title' => $summary->content?->content_title ?? 'Lesson',
                'summary' => mb_substr((string) $summary->summary, 0, 1400),
                'key_points' => array_slice($summary->key_points ?? [], 0, 6),
            ])
            ->values()
            ->all();

        try {
            return response()->json($geminiAiService->answerChatQuestion(
                $validated['message'],
                $contextItems,
                $role,
            ));
        } catch (Throwable $exception) {
            report($exception);

            return response()->json([
                'message' => 'AI assistant is temporarily unavailable. Please try again shortly.',
            ], 503);
        }
    }

    public function aiReportInsights(Request $request, GeminiAiService $geminiAiService)
    {
        abort_unless(in_array($this->displayRoleFor($request->user()), ['Admin', 'InstituteAdmin', 'STEM Engineer'], true), 403);

        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'metrics' => ['required', 'array', 'max:100'],
        ]);

        try {
            return response()->json($geminiAiService->generateReportInsights(
                $validated['title'],
                $validated['metrics'],
            ));
        } catch (Throwable $exception) {
            report($exception);
            return response()->json(['message' => 'AI report insights are temporarily unavailable.'], 503);
        }
    }

    public function aiAssessmentQuestionPaper(Request $request, GeminiAiService $geminiAiService)
    {
        abort_unless($this->displayRoleFor($request->user()) === 'STEM Engineer', 403);

        $validated = $request->validate([
            'assessment_title' => ['required', 'string', 'max:255'],
            'assessment_category' => ['required', 'in:Monthly,Annual'],
            'assigned_class' => ['required', 'string', 'max:255'],
            'total_marks' => ['required', 'integer', 'min:1', 'max:500'],
            'duration_minutes' => ['required', 'string', 'max:50'],
            'content_titles' => ['required', 'array', 'min:1', 'max:50'],
            'content_text' => ['required', 'string', 'max:24000'],
        ]);

        try {
            return response()->json($geminiAiService->generateAssessmentQuestionPaper($validated));
        } catch (Throwable $exception) {
            report($exception);
            return response()->json(['message' => 'AI question paper could not be generated right now.'], 503);
        }
    }

    public function submitFeedback(Request $request)
    {
        $account = $request->user();
        $role = $this->displayRoleFor($account);

        if (!in_array($role, ['STEM Engineer', 'Student'], true)) {
            return response()->json([
                'success' => false,
                'message' => 'Feedback is only available for STEM Engineers and students.',
            ], 403);
        }

        return app(\App\Services\MobileWebContext::class)->run($request, fn () =>
            $role === 'Student'
                ? app(\App\Http\Controllers\FeedbackController::class)->studentStore($request)
                : app(\App\Http\Controllers\FeedbackController::class)->teacherStore($request));
    }

    public function logout(Request $request)
    {
        MobilePushToken::query()
            ->where('pushable_type', get_class($request->user()))
            ->where('pushable_id', $request->user()->getKey())
            ->delete();

        $request->user()->currentAccessToken()?->delete();

        return response()->json([
            'success' => true,
            'message' => 'Logged out successfully',
        ]);
    }

    public function storePushToken(Request $request)
    {
        $account = $request->user();
        $validated = $request->validate([
            'fcm_token' => ['required', 'string', 'max:512'],
            'platform' => ['nullable', 'string', 'max:32'],
            'role' => ['nullable', 'string', 'max:64'],
            'device_name' => ['nullable', 'string', 'max:255'],
        ]);

        $token = MobilePushToken::updateOrCreate(
            ['fcm_token' => $validated['fcm_token']],
            [
                'pushable_type' => get_class($account),
                'pushable_id' => $account->getKey(),
                'role' => $this->displayRoleFor($account),
                'platform' => $validated['platform'] ?? null,
                'device_name' => $validated['device_name'] ?? null,
                'last_seen_at' => now(),
            ],
        );

        return response()->json([
            'success' => true,
            'message' => 'Push token registered.',
            'id' => $token->id,
        ]);
    }

    public function deletePushToken(Request $request)
    {
        $account = $request->user();
        $validated = $request->validate([
            'fcm_token' => ['nullable', 'string', 'max:512'],
        ]);

        MobilePushToken::query()
            ->where('pushable_type', get_class($account))
            ->where('pushable_id', $account->getKey())
            ->when($validated['fcm_token'] ?? null, fn ($query, $token) => $query->where('fcm_token', $token))
            ->delete();

        return response()->json([
            'success' => true,
            'message' => 'Push token removed.',
        ]);
    }

    public function dashboardSummary(Request $request)
    {
        $account = $request->user();
        $role = $this->displayRoleFor($account);

        return response()->json($this->buildDashboardSummary($account, $role));
    }

    public function adminWorkspace(Request $request)
    {
        $account = $this->requireAdmin($request);
        $role = $this->displayRoleFor($account);

        return response()->json([
            'management' => $this->adminManagementItems($account, $role),
            'reports' => $this->adminReportItems(),
            'approvals' => $this->adminApprovalItems($account),
            'monitoring' => $this->adminMonitoringItems($account, $role),
            'hybrid_learners' => $account->role === 'Admin' ? $this->adminIndependentLearnerItems() : [],
        ]);
    }

    public function adminReports(Request $request, ReportController $reports)
    {
        $account = $this->requireAdmin($request);
        $reportMode = $this->mobileReportMode($request);

        // Institute administrators are never allowed to widen the report scope.
        $institute = $account->role === 'InstituteAdmin'
            ? $account->institute
            : trim((string) $request->input('institute', ''));

        return response()->json(
            $reports->mobileReportPayload($request, $reportMode, $institute !== '' ? $institute : null)
        );
    }

    public function submitPanelFeedback(Request $request)
    {
        $account = $request->user();
        abort_unless($account instanceof User && in_array($account->role, ['Manager', 'Principal'], true), 403);
        $validated = $request->validate([
            'category' => ['required', 'string', 'in:General,Learning Content,Assessment,Session,Technical Issue,Other'],
            'subject' => ['required', 'string', 'max:150'],
            'message' => ['required', 'string', 'max:5000'],
        ]);

        try {
            Mail::send('emails.feedback-submitted', [
                'senderType' => $account->role,
                'senderDetails' => [
                    'Name' => $account->name,
                    'ID' => $account->user_id,
                    'Email' => $account->email ?: 'Not provided',
                    'Phone' => $account->phone ?: 'Not provided',
                    'Institute' => $account->institute ?: 'All Institutes',
                ],
                'category' => $validated['category'],
                'feedbackSubject' => $validated['subject'],
                'feedbackMessage' => $validated['message'],
                'submittedAt' => now()->format('d M Y, h:i A'),
            ], function ($message) use ($validated) {
                $message->to('tinkedgemain@gmail.com')
                    ->cc(['support@tinkedge.com', 'shah@tinkedge.com'])
                    ->subject('InnovatEdge Feedback: ' . Str::limit($validated['subject'], 110));
            });
        } catch (Throwable $exception) {
            report($exception);
            return response()->json(['success' => false, 'message' => 'Feedback could not be sent right now.'], 503);
        }

        return response()->json(['success' => true, 'message' => 'Feedback submitted successfully.']);
    }

    public function adminReportExportUrl(Request $request)
    {
        $account = $this->requireAdmin($request);
        $reportMode = $this->mobileReportMode($request);
        $filters = $request->only([
            'institute',
            'student_class',
            'student_section',
            'report_date',
            'from_date',
            'to_date',
            'report_month',
        ]);

        $url = URL::temporarySignedRoute(
            'mobile.admin.report-export',
            now()->addMinutes(5),
            array_merge([
                'accountId' => $account->id,
                'reportMode' => $reportMode,
            ], $filters)
        );

        return response()->json([
            'url' => $url,
            'expires_in_seconds' => 300,
        ]);
    }

    public function managerReports(Request $request, ReportController $reports)
    {
        $this->requireRole($request, ['Manager']);
        $reportMode = $this->mobileReportMode($request);
        abort_unless(in_array($reportMode, [
            'daily-session', 'weekly-session', 'monthly-session',
            'weekly-stem-engineer-performance', 'monthly-stem-engineer-performance',
        ], true), 422, 'Unsupported manager report type.');

        return response()->json($reports->mobileReportPayload($request, $reportMode, null));
    }

    public function principalReports(Request $request, ReportController $reports)
    {
        $account = $this->requireRole($request, ['Principal']);
        $reportMode = $this->mobileReportMode($request);
        abort_unless(in_array($reportMode, [
            'daily-session', 'weekly-session', 'monthly-session',
            'daily-student-performance', 'weekly-student-performance', 'monthly-student-performance',
        ], true), 422, 'Unsupported principal report type.');

        return response()->json($reports->mobileReportPayload($request, $reportMode, $account->institute));
    }

    public function panelReportExportUrl(Request $request)
    {
        $account = $this->requireRole($request, ['Manager', 'Principal']);
        $reportMode = $this->mobileReportMode($request);
        $allowed = $account->role === 'Manager'
            ? ['daily-session', 'weekly-session', 'monthly-session', 'weekly-stem-engineer-performance', 'monthly-stem-engineer-performance']
            : ['daily-session', 'weekly-session', 'monthly-session', 'daily-student-performance', 'weekly-student-performance', 'monthly-student-performance'];
        abort_unless(in_array($reportMode, $allowed, true), 422, 'Unsupported report type.');

        return response()->json([
            'url' => URL::temporarySignedRoute('mobile.admin.report-export', now()->addMinutes(5), array_merge([
                'accountId' => $account->id,
                'reportMode' => $reportMode,
            ], $request->only(['institute', 'student_class', 'student_section', 'report_date', 'from_date', 'to_date', 'report_month']))),
            'expires_in_seconds' => 300,
        ]);
    }

    public function serveAdminReportExport(
        Request $request,
        int $accountId,
        string $reportMode,
        ReportController $reports,
        GeminiAiService $ai
    ) {
        $account = User::findOrFail($accountId);
        abort_unless(in_array($account->role, ['Admin', 'InstituteAdmin', 'Manager', 'Principal'], true), 403, 'Report access is required.');
        abort_unless(in_array($reportMode, $this->mobileReportModes(), true), 422, 'Unsupported report type.');

        $institute = in_array($account->role, ['InstituteAdmin', 'Principal'], true)
            ? $account->institute
            : trim((string) $request->input('institute', ''));
        $payload = $reports->mobileReportPayload($request, $reportMode, $institute !== '' ? $institute : null);

        try {
            $insights = $ai->generateReportInsights($payload['title'], $payload['metrics']);
        } catch (Throwable $exception) {
            $insights = [
                'summary' => 'Live LMS metrics are included. AI insights are temporarily unavailable.',
                'recommendations' => ['Review the report metrics and follow up on incomplete activity.'],
                'generated_at' => now()->format('d M Y h:i A'),
            ];
        }

        return Pdf::loadView('pdf.generated-lms-report', [
            'title' => $payload['title'],
            'scope' => $payload['scope'],
            'periodLabel' => $payload['period_label'],
            'metrics' => $payload['metrics'],
            'tableTitle' => $payload['table_title'],
            'tableHeaders' => $payload['table_headers'],
            'tableRows' => $payload['table_rows'],
            'visuals' => $payload['visuals'],
            'insights' => $insights,
        ])->setPaper('a4', 'portrait')->download($payload['file_name']);
    }

    public function adminMonitoring(Request $request)
    {
        $account = $this->requireAdmin($request);

        $items = [];

        if ($account->role === 'Admin') {
            $items[] = [
                'title' => 'Learning Content Monitoring',
                'subtitle' => 'Track how long STEM Engineers and students access learning content.',
            ];
        }

        $items[] = [
            'title' => 'Assessment Monitoring',
            'subtitle' => 'Monitor assessments, status, and assessment activity.',
        ];

        $items[] = [
            'title' => 'Assessment Review Monitoring',
            'subtitle' => 'Monitor manual assessment review and evaluation status.',
        ];

        return response()->json([
            'items' => $items,
        ]);
    }

    public function adminMonitoringDetails(Request $request, string $type)
    {
        $account = $this->requireAdmin($request);
        $institute = $account->role === 'InstituteAdmin'
            ? $account->institute
            : trim((string) $request->input('institute', ''));
        $institute = $institute !== '' ? $institute : null;

        return match ($type) {
            'activity' => $this->adminActivityMonitoringDetails($request, $account, $institute),
            'assessments' => $this->adminAssessmentMonitoringDetails($request, $institute),
            'reviews' => $this->adminAssessmentReviewMonitoringDetails($request, $institute),
            default => response()->json(['message' => 'Monitoring section not found.'], 404),
        };
    }

    private function adminActivityMonitoringDetails(Request $request, User $account, ?string $institute)
    {
        abort_if($account->role !== 'Admin', 403, 'Learning content monitoring is available for Super Admin only.');

        $viewerType = trim((string) $request->input('viewer_type', ''));
        $date = trim((string) $request->input('date', ''));

        $query = UserActivityLog::with(['teacher', 'student'])
            ->whereIn('user_type', ['Teacher', 'Student'])
            ->learningContent()
            ->when($date !== '', fn ($builder) => $builder->whereDate('started_at', $date))
            ->when(in_array($viewerType, ['Teacher', 'Student'], true), fn ($builder) => $builder->where('user_type', $viewerType))
            ->when($institute, function ($builder) use ($institute) {
                $builder->where(function ($scope) use ($institute) {
                    $scope->where(function ($teacherQuery) use ($institute) {
                        $teacherQuery->where('user_type', 'Teacher')
                            ->whereHas('teacher', fn ($teacher) => $teacher->where('institute', $institute));
                    })->orWhere(function ($studentQuery) use ($institute) {
                        $studentQuery->where('user_type', 'Student')
                            ->whereHas('student', fn ($student) => $student->where('institute', $institute));
                    });
                });
            });

        $page = (clone $query)->latest('started_at')->orderByDesc('id')->paginate(50);
        $contentIdsByLogId = $page->getCollection()
            ->mapWithKeys(function (UserActivityLog $log) {
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
        $teacherIdsOnPage = $page->getCollection()
            ->where('user_type', 'Teacher')
            ->pluck('user_id')
            ->filter()
            ->unique()
            ->values();
        $teachersById = User::whereIn('id', $teacherIdsOnPage)
            ->get(['id', 'institute'])
            ->keyBy('id');
        $classesByInstitute = SchoolClass::whereIn('institute', $teachersById->pluck('institute')->filter()->unique())
            ->orderBy('class_name')
            ->orderBy('section')
            ->get()
            ->groupBy('institute')
            ->map(function ($classes) {
                return $classes
                    ->map(fn (SchoolClass $class) => trim((string) $class->class_name . ' ' . (string) $class->section))
                    ->filter()
                    ->unique(fn ($className) => mb_strtolower($className))
                    ->values();
            });
        $teacherClassLabelsById = $teachersById->mapWithKeys(function (User $teacher) use ($classesByInstitute) {
            $classes = $classesByInstitute->get($teacher->institute, collect());
            $label = $classes->take(3)->implode(', ');

            if ($classes->count() > 3) {
                $label .= ' +' . ($classes->count() - 3) . ' more';
            }

            return [$teacher->id => $label ?: 'Unassigned Class'];
        });

        return response()->json([
            'pagination' => $this->pageMetadata($page),
            'title' => 'Learning Content Monitoring',
            'subtitle' => 'Track how long STEM Engineers and students access learning content.',
            'filters' => $this->adminMonitoringFilters($account),
            'summary' => [
                ['label' => 'Total accesses', 'value' => (string) (clone $query)->count()],
                ['label' => 'STEM Engineer accesses', 'value' => (string) (clone $query)->where('user_type', 'Teacher')->count()],
                ['label' => 'Student accesses', 'value' => (string) (clone $query)->where('user_type', 'Student')->count()],
                ['label' => 'Total minutes', 'value' => (string) round(((int) (clone $query)->sum('duration_seconds')) / 60)],
            ],
            'items' => $page->getCollection()
                ->map(function (UserActivityLog $log) use ($contentContextByLogId, $teacherClassLabelsById) {
                    $user = $log->user_type === 'Teacher' ? $log->teacher : $log->student;
                    $startedAt = $log->started_at ? Carbon::parse($log->started_at)->format('Y-m-d H:i') : '';
                    $contentContext = $contentContextByLogId->get($log->id, []);
                    $institute = $user?->institute ?: ($contentContext['institute'] ?? 'Unassigned Institute');
                    $classLabel = $log->user_type === 'Student'
                        ? trim((string) ($user?->class ?? '') . ' ' . (string) ($user?->section ?? ''))
                        : trim((string) ($contentContext['class_label'] ?? ''));

                    if ($classLabel === '' && $log->user_type === 'Teacher') {
                        $classLabel = $teacherClassLabelsById->get($log->user_id, '');
                    }

                    return [
                        'title' => $user?->name ?: ($log->user_type === 'Teacher' ? 'STEM Engineer Deleted' : 'Student Deleted'),
                        'subtitle' => trim(($classLabel !== '' ? $classLabel : 'Unassigned Class') . ' · ' . $institute),
                        'status' => $log->activity_status,
                        'meta' => trim($startedAt . ' · ' . round(((int) $log->duration_seconds) / 60) . ' min'),
                    ];
                })
                ->values(),
        ]);
    }

    private function adminAssessmentMonitoringDetails(Request $request, ?string $institute)
    {
        $studentClass = trim((string) $request->input('student_class', ''));
        $studentSection = trim((string) $request->input('student_section', ''));
        $status = trim((string) $request->input('status', ''));
        $date = trim((string) $request->input('date', ''));
        $search = trim((string) $request->input('search', ''));

        $query = AssessmentSession::with(['assessment', 'student', 'teacher'])
            ->when($institute, fn ($builder) => $builder->whereHas('assessment', fn ($assessment) => $assessment->where('institute', $institute)))
            ->when($studentClass !== '' || $studentSection !== '', function ($builder) use ($institute, $studentClass, $studentSection) {
                $builder->where('user_type', 'Student');
                $builder->whereHas('student', function ($student) use ($institute, $studentClass, $studentSection) {
                    $student->when($institute, fn ($inner) => $inner->where('institute', $institute))
                        ->when($studentClass !== '', fn ($inner) => $inner->where('class', $studentClass))
                        ->when($studentSection !== '', fn ($inner) => $inner->where('section', $studentSection));
                });
            })
            ->when(in_array($status, ['Started', 'Submitted', 'AutoSubmitted'], true), fn ($builder) => $builder->where('status', $status))
            ->when($date !== '', fn ($builder) => $builder->whereDate('started_at', $date))
            ->when($search !== '', function ($builder) use ($search) {
                $builder->where(function ($searchQuery) use ($search) {
                    $searchQuery->whereHas('assessment', fn ($assessment) => $assessment->where('assessment_title', 'like', '%' . $search . '%'))
                        ->orWhereHas('student', function ($student) use ($search) {
                            $student->where('name', 'like', '%' . $search . '%')
                                ->orWhere('student_id', 'like', '%' . $search . '%');
                        })
                        ->orWhereHas('teacher', fn ($teacher) => $teacher->where('name', 'like', '%' . $search . '%'));
                });
            });

        $page = (clone $query)->latest()->orderByDesc('id')->paginate(50);
        return response()->json([
            'pagination' => $this->pageMetadata($page),
            'title' => 'Assessment Monitoring',
            'subtitle' => 'Monitor assessments, status, and assessment activity.',
            'filters' => $this->adminMonitoringFilters($this->requireAdmin($request), $institute) + [
                'statuses' => ['Started', 'Submitted', 'AutoSubmitted'],
            ],
            'summary' => [
                ['label' => 'Sessions', 'value' => (string) (clone $query)->count()],
                ['label' => 'Submitted', 'value' => (string) (clone $query)->whereIn('status', ['Submitted', 'AutoSubmitted'])->count()],
                ['label' => 'In progress', 'value' => (string) (clone $query)->where('status', 'Started')->count()],
            ],
            'items' => $page->getCollection()
                ->map(function (AssessmentSession $session) {
                    $user = $session->user_type === 'Teacher' ? $session->teacher : $session->student;
                    $startedAt = $session->started_at ? Carbon::parse($session->started_at)->format('Y-m-d H:i') : '';
                    $institute = $user?->institute ?: ($session->assessment?->institute ?: 'Unassigned Institute');
                    $classLabel = $session->user_type === 'Student'
                        ? trim((string) ($user?->class ?? '') . ' ' . (string) ($user?->section ?? ''))
                        : '';

                    if ($classLabel === '') {
                        $classLabel = trim((string) ($session->assessment?->assigned_class ?? ''));
                    }

                    return [
                        'title' => $session->assessment?->assessment_title ?: 'Assessment',
                        'subtitle' => trim(($user?->name ?: 'User unavailable') . ' · ' . ($classLabel !== '' ? $classLabel : 'Unassigned Class') . ' · ' . $institute),
                        'status' => $session->status ?: 'Pending',
                        'meta' => trim($startedAt . ' · Violations: ' . (int) $session->violation_count),
                    ];
                })
                ->values(),
        ]);
    }

    private function adminAssessmentReviewMonitoringDetails(Request $request, ?string $institute)
    {
        $studentClass = trim((string) $request->input('student_class', ''));
        $studentSection = trim((string) $request->input('student_section', ''));
        $status = trim((string) $request->input('status', ''));
        $search = trim((string) $request->input('search', ''));

        $query = AssessmentResult::with(['assessment', 'student', 'evaluator'])
            ->when($institute || $studentClass !== '' || $studentSection !== '' || $search !== '', function ($builder) use ($institute, $studentClass, $studentSection, $search) {
                $builder->whereHas('student', function ($student) use ($institute, $studentClass, $studentSection, $search) {
                    $student->when($institute, fn ($inner) => $inner->where('institute', $institute))
                        ->when($studentClass !== '', fn ($inner) => $inner->where('class', $studentClass))
                        ->when($studentSection !== '', fn ($inner) => $inner->where('section', $studentSection))
                        ->when($search !== '', function ($inner) use ($search) {
                            $inner->where(function ($searchQuery) use ($search) {
                                $searchQuery->where('name', 'like', '%' . $search . '%')
                                    ->orWhere('student_id', 'like', '%' . $search . '%');
                            });
                        });
                });
            })
            ->when($status !== '', fn ($builder) => $builder->where('status', $status));

        $page = (clone $query)->latest()->orderByDesc('id')->paginate(50);
        return response()->json([
            'pagination' => $this->pageMetadata($page),
            'title' => 'Assessment Review Monitoring',
            'subtitle' => 'Monitor manual assessment review and evaluation status.',
            'filters' => $this->adminMonitoringFilters($this->requireAdmin($request), $institute),
            'summary' => [
                ['label' => 'Results', 'value' => (string) (clone $query)->count()],
                ['label' => 'Evaluated', 'value' => (string) (clone $query)->whereNotNull('evaluated_at')->count()],
                ['label' => 'Pending review', 'value' => (string) (clone $query)->whereNull('evaluated_at')->count()],
            ],
            'items' => $page->getCollection()
                ->map(function (AssessmentResult $result) {
                    $institute = $result->student?->institute ?: ($result->assessment?->institute ?: 'Unassigned Institute');
                    $classLabel = trim((string) ($result->student?->class ?? '') . ' ' . (string) ($result->student?->section ?? ''));

                    if ($classLabel === '') {
                        $classLabel = trim((string) ($result->assessment?->assigned_class ?? ''));
                    }

                    return [
                        'title' => $result->student?->name ?: 'Student unavailable',
                        'subtitle' => trim(($result->assessment?->assessment_title ?: 'Assessment') . ' · ' . ($classLabel !== '' ? $classLabel : 'Unassigned Class') . ' · ' . $institute),
                        'status' => $result->status ?: ($result->evaluated_at ? 'Evaluated' : 'Pending'),
                        'meta' => trim(($result->percentage !== null ? round((float) $result->percentage, 1) . '%' : 'Score unavailable') . ' · ' . ($result->evaluator?->name ?: 'Evaluator unavailable')),
                    ];
                })
                ->values(),
        ]);
    }

    private function adminMonitoringFilters(User $account, ?string $selectedInstitute = null): array
    {
        $institutes = $account->role === 'InstituteAdmin'
            ? collect([$account->institute])
            : Institute::orderBy('institute_name')->pluck('institute_name');

        $classes = SchoolClass::query()
            ->when($selectedInstitute, fn ($builder) => $builder->where('institute', $selectedInstitute))
            ->orderBy('class_name')
            ->orderBy('section')
            ->get()
            ->map(fn (SchoolClass $class) => [
                'class_name' => $class->class_name,
                'section' => $class->section,
                'institute' => $class->institute,
            ])
            ->values();

        return [
            'institutes' => $institutes->filter()->values(),
            'classes' => $classes,
        ];
    }

    private function pageMetadata(\Illuminate\Pagination\LengthAwarePaginator $page): array
    {
        return ['page' => $page->currentPage(), 'last_page' => $page->lastPage(), 'total' => $page->total()];
    }

    private array $legacyPagination = [];

    private function legacyPage(\Illuminate\Database\Eloquent\Builder $query, string $key): \Illuminate\Support\Collection
    {
        $parameter = $key.'_page';
        $page = $query->orderBy($query->getModel()->getQualifiedKeyName())
            ->paginate(25, ['*'], $parameter);
        $this->legacyPagination[$key] = $this->pageMetadata($page) + ['parameter' => $parameter];
        return collect($page->items());
    }

    private function legacyResponse(array $data)
    {
        $pagination = $this->legacyPagination;
        $this->legacyPagination = [];
        return response()->json($data + ['pagination' => $pagination]);
    }

    public function adminApprovals(Request $request)
    {
        $account = $request->user();
        abort_unless($account instanceof User && in_array($account->role, ['Admin', 'InstituteAdmin', 'Manager'], true), 403, 'Approval access is required.');
        $institute = $account->role === 'InstituteAdmin'
            ? $account->institute
            : trim((string) $request->input('institute', ''));
        $institute = $institute !== '' ? $institute : null;

        return $this->legacyResponse([
            'question_papers' => $this->legacyPage(Assessment::query()
                ->whereRaw('LOWER(question_paper_status) IN (?, ?)', ['pending', 'pending approval'])
                ->when($institute, fn ($query) => $query->where('institute', $institute))
                ->latest(), 'question_papers')
                ->map(fn (Assessment $assessment) => [
                    'id' => $assessment->id,
                    'type' => 'question-paper',
                    'title' => $assessment->assessment_title,
                    'subtitle' => trim(($assessment->assigned_class ?: 'Class n/a') . ' · ' . ($assessment->institute ?: 'Institute n/a')),
                    'status' => $assessment->question_paper_status,
                ])->values(),
            'certificates' => $this->legacyPage(Certificate::query()
                ->whereRaw('LOWER(status) = ?', ['pending'])
                ->with('student')
                ->when($institute, fn ($query) => $query->whereHas('student', fn ($studentQuery) => $studentQuery->where('institute', $institute)))
                ->latest(), 'certificates')
                ->map(fn (Certificate $certificate) => [
                    'id' => $certificate->id,
                    'type' => 'certificate',
                    'title' => $certificate->student?->name ?: 'Certificate request',
                    'subtitle' => trim(($certificate->certificate_code ?: 'Code pending') . ' · ' . ($certificate->student?->institute ?: 'Institute n/a')),
                    'status' => $certificate->status,
                ])->values(),
            'posts' => $this->legacyPage(MySpace::query()
                ->whereIn('status', ['Submitted', 'Pending'])
                ->when($institute, fn ($q) => $q->where(fn ($q) => $q
                    ->where(fn ($q) => $q->where('created_by_type', 'Teacher')->whereIn('created_by_id', User::where('institute', $institute)->select('id')))
                    ->orWhere(fn ($q) => $q->where('created_by_type', 'Student')->whereIn('created_by_id', Student::where('institute', $institute)->select('id')))))
                ->latest(), 'posts')
                ->map(fn (MySpace $item) => [
                    'id' => $item->id,
                    'type' => 'my-space',
                    'title' => $item->title,
                    'subtitle' => trim(($item->created_by_type ?: 'Submitter') . ' · ' . ($item->type ?: 'Post')),
                    'status' => $item->status,
                ])->values(),
        ]);
    }

    public function panelNotifications(Request $request)
    {
        $account = $request->user();
        abort_unless($account instanceof User && in_array($account->role, ['Manager', 'Principal'], true), 403);

        $notifications = LmsNotification::query()
            ->where('status', 'active')
            ->whereIn('target', ['all', strtolower($account->role) . 's'])
            ->when($account->role === 'Principal', fn ($q) => $q->where(function ($scope) use ($account) {
                $scope->whereNull('institute')->orWhere('institute', $account->institute);
            }))
            ->latest()
            ->limit(100)
            ->get()
            ->map(fn (LmsNotification $notification) => [
                'id' => $notification->id,
                'title' => $notification->title,
                'message' => $notification->message,
                'target' => $notification->target,
                'created_at' => optional($notification->created_at)->toDateTimeString(),
            ])->values();

        return response()->json(['title' => 'Notifications', 'notifications' => $notifications]);
    }

    public function adminIndependentLearners(Request $request)
    {
        $this->requireAdmin($request);

        $search = trim((string) $request->input('search'));

        $learners = IndependentLearner::query()
            ->when($search, function ($query) use ($search) {
                $query->where(function ($inner) use ($search) {
                    $inner->where('name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%")
                        ->orWhere('phone', 'like', "%{$search}%");
                });
            })
            ->withCount(['enrollments', 'certificates'])
            ->orderBy('name')
            ->get()
            ->map(function (IndependentLearner $learner) {
                return [
                    'id' => $learner->id,
                    'title' => $learner->name ?: 'Hybrid Learner',
                    'subtitle' => trim(($learner->email ?: 'Email n/a') . ' · ' . ($learner->phone ?: 'Phone n/a')),
                    'status' => $learner->status ? 'Active' : 'Inactive',
                    'registered_on' => optional($learner->created_at)?->format('d M Y') ?? '',
                    'enrollments_count' => (int) ($learner->enrollments_count ?? 0),
                    'certificates_count' => (int) ($learner->certificates_count ?? 0),
                    'email' => $learner->email ?? '',
                    'phone' => $learner->phone ?? '',
                ];
            })
            ->values();

        return response()->json([
            'learners' => $learners,
        ]);
    }

    public function adminNotifications(Request $request)
    {
        $account = $this->requireAdmin($request);
        $request->validate(['from_date' => 'nullable|date', 'to_date' => 'nullable|date|after_or_equal:from_date']);

        $query = LmsNotification::query()
            ->when($account->role === 'InstituteAdmin', function ($builder) use ($account) {
                $builder->where(function ($scope) use ($account) {
                    $scope->whereNull('institute')
                        ->orWhere('institute', $account->institute);
                });
            });

        $query->when($request->filled('from_date'), function ($builder) use ($request) {
            $builder->whereDate('created_at', '>=', $request->input('from_date'));
        });

        $query->when($request->filled('to_date'), function ($builder) use ($request) {
            $builder->whereDate('created_at', '<=', $request->input('to_date'));
        });

        $page = $query->latest()->orderByDesc('id')->paginate(50);
        return response()->json([
            'pagination' => $this->pageMetadata($page),
            'notifications' => $page->getCollection()
                ->map(function (LmsNotification $notification) {
                    return [
                        'id' => $notification->id,
                        'title' => $notification->title,
                        'message' => $notification->message,
                        'target' => $notification->target,
                        'institute' => $notification->institute ?: 'All Institutes',
                        'created_at' => $notification->created_at?->format('d M Y') ?? '',
                        'starts_at' => $notification->starts_at?->format('d M Y') ?? '',
                        'expires_at' => $notification->expires_at?->format('d M Y') ?? '',
                        'status' => $notification->status,
                    ];
                })
                ->values(),
            'institutes' => $account->role === 'Admin'
                ? Institute::where('status', 1)->orderBy('institute_name')->get()->map(fn (Institute $institute) => [
                    'title' => $institute->institute_name,
                    'value' => $institute->institute_name,
                ])->values()
                : [],
        ]);
    }

    public function storeAdminNotification(Request $request)
    {
        $account = $this->requireAdmin($request);

        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'message' => ['required', 'string', 'max:3000'],
            'target' => ['required', 'in:all,teachers,students'],
            'institute' => [$account->role === 'Admin' ? 'nullable' : 'prohibited', 'nullable', 'string', 'max:255', 'exists:institutes,institute_name'],
            'starts_at' => ['nullable', 'date'],
            'expires_at' => ['nullable', 'date', 'after_or_equal:starts_at'],
            'status' => ['required', 'in:active,draft,archived'],
        ]);

        if ($account->role === 'InstituteAdmin') {
            $validated['institute'] = $account->institute;
        }

        $validated['created_by'] = $account->id;

        $notification = LmsNotification::create($validated);
        app(FirebasePushService::class)->sendLmsNotification($notification);

        return response()->json([
            'success' => true,
            'message' => 'Notification created successfully.',
            'notification' => [
                'id' => $notification->id,
                'title' => $notification->title,
                'message' => $notification->message,
                'target' => $notification->target,
                'institute' => $notification->institute ?: 'All Institutes',
                'created_at' => $notification->created_at?->format('d M Y') ?? '',
                'starts_at' => $notification->starts_at?->format('d M Y') ?? '',
                'expires_at' => $notification->expires_at?->format('d M Y') ?? '',
                'status' => $notification->status,
            ],
        ]);
    }

    public function deleteAdminNotification(Request $request, int $id)
    {
        $account = $this->requireAdmin($request);
        $notification = LmsNotification::findOrFail($id);

        if ($account->role === 'InstituteAdmin' && $notification->institute !== $account->institute) {
            abort(403, 'You cannot delete this notification.');
        }

        $notification->delete();

        return response()->json([
            'success' => true,
            'message' => 'Notification deleted successfully.',
        ]);
    }

    public function updateAdminApproval(Request $request, string $type, int $id, string $decision)
    {
        $account = $this->requireAdmin($request);
        $decision = strtolower($decision);

        abort_unless(in_array($decision, ['approve', 'reject'], true), 422, 'Unsupported approval decision.');
        $status = $decision === 'approve' ? 'Approved' : 'Rejected';

        if ($type === 'question-paper') {
            $record = Assessment::findOrFail($id);
            $this->ensureAdminInstituteAccess($account, $record->institute);
            $record->update([
                'question_paper_status' => $status,
                'question_paper_reviewed_by' => $account->id,
                'question_paper_reviewed_at' => now(),
                'question_paper_feedback' => $decision === 'reject'
                    ? (trim((string) $request->input('reason')) ?: null)
                    : null,
            ]);
        } elseif ($type === 'certificate') {
            $record = Certificate::with('student')->findOrFail($id);
            $this->ensureAdminInstituteAccess($account, $record->student?->institute);
            $request->merge(['rejection_reason' => $request->input('reason')]);
            return app(\App\Services\MobileWebContext::class)->run($request, fn () => $decision === 'approve'
                ? app(\App\Http\Controllers\PageController::class)->approveCertificate($id)
                : app(\App\Http\Controllers\PageController::class)->rejectCertificate($request, $id));
        } elseif ($type === 'my-space') {
            $record = MySpace::findOrFail($id);
            $this->ensureAdminInstituteAccess($account, $record->submitter()?->institute);
            $record->update(['status' => $status]);
        } else {
            abort(404, 'Approval type not found.');
        }

        return response()->json([
            'success' => true,
            'message' => "Item {$status} successfully.",
        ]);
    }

    public function managementFilters(Request $request, string $area)
    {
        $this->requireAdmin($request);
        return response()->json(['filters' => app(\App\Services\MobileManagementFilters::class)->fields($request, $area)]);
    }

    public function adminInstitutes(Request $request)
    {
        $account = $this->requireRole($request, ['Admin', 'InstituteAdmin', 'Manager']);

        return response()->json([
            'institutes' => Institute::query()
                ->tap(fn ($query) => app(\App\Services\MobileManagementFilters::class)->apply($query, $request, 'institutes'))
                ->when(
                    $account->role === 'InstituteAdmin',
                    fn ($query) => $query->where('institute_name', $account->institute)
                )
                ->orderBy('institute_name')
                ->get()
                ->map(function (Institute $institute) {
                    return [
                        'id' => $institute->id,
                        'institute_id' => $institute->institute_id,
                        'title' => $institute->institute_name,
                        'subtitle' => trim(
                            'ID: ' . $institute->institute_id
                            . ($institute->location ? ' · ' . $institute->location : '')
                            . ($institute->email ? ' · ' . $institute->email : '')
                            . ($institute->phone ? ' · ' . $institute->phone : '')
                        ),
                        'status' => $institute->status ? 'Active' : 'Inactive',
                    ];
                })
                ->values(),
        ]);
    }

    public function adminInstitute(Request $request, int $id)
    {
        $account = $this->requireAdmin($request);
        $institute = Institute::findOrFail($id);
        $this->ensureAdminInstituteAccess($account, $institute->institute_name);
        $adminUser = User::where('role', 'InstituteAdmin')
            ->where('institute', $institute->institute_name)
            ->orderBy('id')
            ->first();

        return response()->json([
            'institute' => [
                'id' => $institute->id,
                'institute_id' => $institute->institute_id,
                'title' => $institute->institute_name,
                'subtitle' => trim(
                    'ID: ' . $institute->institute_id
                    . ($institute->location ? ' · ' . $institute->location : '')
                    . ($institute->email ? ' · ' . $institute->email : '')
                    . ($institute->phone ? ' · ' . $institute->phone : '')
                ),
                'location' => $institute->location,
                'contact_person' => $institute->contact_person,
                'email' => $institute->email,
                'phone' => $institute->phone,
                'admin_name' => $adminUser?->name ?? '',
                'admin_email' => $adminUser?->email ?? '',
                'status' => $institute->status ? 'Active' : 'Inactive',
            ],
        ]);
    }

    public function storeAdminInstitute(Request $request)
    {
        $account = $this->requireAdmin($request);
        abort_if($account->role !== 'Admin', 403, 'Only Super Admin can create institutes.');

        $validated = $request->validate([
            'institute_id' => ['required', 'string', 'max:50', 'unique:institutes,institute_id'],
            'institute_name' => ['required', 'string', 'max:150', 'unique:institutes,institute_name'],
            'location' => ['required', 'string', 'max:100'],
            'contact_person' => ['required', 'string', 'max:100'],
            'email' => ['required', 'email'],
            'phone' => ['required', 'string', 'max:20'],
            'status' => ['required', 'boolean'],
            'admin_name' => ['nullable', 'string', 'max:100'],
            'admin_email' => ['nullable', 'email', 'max:255'],
            'admin_password' => ['nullable', 'string', 'min:6'],
        ]);

        $institute = DB::transaction(function () use ($validated, $request, $account) {
            $institute = Institute::create([
                'institute_id' => $validated['institute_id'],
                'institute_name' => $validated['institute_name'],
                'location' => $validated['location'],
                'contact_person' => $validated['contact_person'],
                'email' => $validated['email'],
                'phone' => $validated['phone'],
                'status' => (bool) $validated['status'],
            ]);

            if (!empty($validated['admin_password']) && !empty($validated['admin_email'])) {
                do {
                    $adminUserId = 'ADM' . random_int(100000, 999999);
                } while (User::where('user_id', $adminUserId)->exists());

                User::create([
                    'user_id' => $adminUserId,
                    'name' => $validated['admin_name'] ?: $validated['contact_person'],
                    'email' => $validated['admin_email'],
                    'phone' => $validated['phone'],
                    'institute' => $validated['institute_name'],
                    'role' => 'InstituteAdmin',
                    'password' => Hash::make($validated['admin_password']),
                    'status' => (bool) $validated['status'],
                    'password_changed_at' => now(),
                ]);
            }

            return $institute;
        });

        return response()->json([
            'success' => true,
            'message' => 'Institute added successfully.',
            'institute' => [
                'id' => $institute->id,
                'institute_id' => $institute->institute_id,
                'title' => $institute->institute_name,
                'subtitle' => trim(
                    'ID: ' . $institute->institute_id
                    . ($institute->location ? ' · ' . $institute->location : '')
                    . ($institute->email ? ' · ' . $institute->email : '')
                    . ($institute->phone ? ' · ' . $institute->phone : '')
                ),
                'status' => $institute->status ? 'Active' : 'Inactive',
            ],
        ]);
    }

    public function updateAdminInstitute(Request $request, int $id)
    {
        $account = $this->requireAdmin($request);
        abort_if($account->role !== 'Admin', 403, 'Only Super Admin can edit institutes.');
        $institute = Institute::findOrFail($id);

        $validated = $request->validate([
            'institute_id' => ['required', 'string', 'max:50', 'unique:institutes,institute_id,' . $id],
            'institute_name' => ['required', 'string', 'max:150', 'unique:institutes,institute_name,' . $id],
            'location' => ['required', 'string', 'max:100'],
            'contact_person' => ['required', 'string', 'max:100'],
            'email' => ['required', 'email'],
            'phone' => ['required', 'string', 'max:20'],
            'status' => ['required', 'boolean'],
        ]);

        $institute->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'Institute updated successfully.',
        ]);
    }

    public function deleteAdminInstitute(Request $request, int $id)
    {
        $account = $this->requireAdmin($request);
        abort_if($account->role !== 'Admin', 403, 'Only Super Admin can delete institutes.');
        return app(\App\Services\MobileWebContext::class)->run($request,
            fn () => app(\App\Http\Controllers\InstituteController::class)->delete($id));
    }

    public function adminClasses(Request $request)
    {
        $account = $this->requireAdmin($request);
        return response()->json([
            'classes' => SchoolClass::query()
                ->tap(fn ($query) => app(\App\Services\MobileManagementFilters::class)->apply($query, $request, 'classes'))
                ->when($account->role === 'InstituteAdmin', fn ($query) => $query->where('institute', $account->institute))
                ->orderBy('institute')
                ->orderBy('class_name')
                ->orderBy('section')
                ->get()
                ->map(function (SchoolClass $class) {
                    return [
                        'id' => $class->id,
                        'title' => trim($class->class_name . ($class->section ? ' ' . $class->section : '')),
                        'subtitle' => trim('Institute: ' . $class->institute . ' · ' . ($class->academic_year ?? 'Academic year n/a')),
                        'institute' => $class->institute,
                        'class_name' => $class->class_name,
                        'section' => $class->section,
                        'academic_year' => $class->academic_year,
                        'status' => $class->status ? 'Active' : 'Inactive',
                    ];
                })
                ->values(),
        ]);
    }

    public function adminClass(Request $request, int $id)
    {
        $account = $this->requireAdmin($request);
        $class = SchoolClass::findOrFail($id);
        $this->ensureAdminInstituteAccess($account, $class->institute);

        return response()->json([
            'class' => [
                'id' => $class->id,
                'title' => trim($class->class_name . ($class->section ? ' ' . $class->section : '')),
                'subtitle' => trim('Institute: ' . $class->institute . ' · ' . ($class->academic_year ?? 'Academic year n/a')),
                'class_name' => $class->class_name,
                'section' => $class->section,
                'academic_year' => $class->academic_year,
                'institute' => $class->institute,
                'status' => $class->status ? 'Active' : 'Inactive',
            ],
        ]);
    }

    public function adminTeachers(Request $request)
    {
        $account = $this->requireAdmin($request);
        $page = User::query()->where('role', 'Teacher')
            ->tap(fn ($query) => app(\App\Services\MobileManagementFilters::class)->apply($query, $request, 'teachers'))
            ->when($account->role === 'InstituteAdmin', fn ($query) => $query->where('institute', $account->institute))
            ->orderBy('institute')->orderBy('name')->orderBy('id')->paginate(50);
        return response()->json([
            'pagination' => $this->pageMetadata($page),
            'teachers' => $page->getCollection()
                ->map(function (User $teacher) {
                    return [
                        'id' => $teacher->id,
                        'user_id' => $teacher->user_id,
                        'title' => $teacher->name ?: 'STEM Engineer',
                        'subtitle' => trim(($teacher->user_id ? $teacher->user_id . ' · ' : '') . ($teacher->qualification ?: 'Qualification n/a') . ($teacher->institute ? ' · ' . $teacher->institute : '')),
                        'status' => $teacher->status ? 'Active' : 'Inactive',
                    ];
                })
                ->values(),
        ]);
    }

    public function adminTeacher(Request $request, int $id)
    {
        $account = $this->requireAdmin($request);
        $teacher = User::where('role', 'Teacher')->findOrFail($id);
        $this->ensureAdminInstituteAccess($account, $teacher->institute);

        return response()->json([
            'teacher' => [
                'id' => $teacher->id,
                'user_id' => $teacher->user_id,
                'title' => $teacher->name ?: 'STEM Engineer',
                'subtitle' => trim(($teacher->user_id ? $teacher->user_id . ' · ' : '') . ($teacher->qualification ?: 'Qualification n/a') . ($teacher->institute ? ' · ' . $teacher->institute : '')),
                'email' => $teacher->email,
                'qualification' => $teacher->qualification,
                'institute' => $teacher->institute,
                'status' => $teacher->status ? 'Active' : 'Inactive',
            ],
        ]);
    }

    public function storeAdminTeacher(Request $request)
    {
        $account = $this->requireAdmin($request);

        $validated = $request->validate([
            'user_id' => ['required', 'string', 'max:50', 'unique:users,user_id'],
            'name' => ['required', 'string', 'max:100'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'qualification' => ['required', 'string', 'max:255'],
            'password' => ['required', 'string', 'min:6'],
            'status' => ['required', 'boolean'],
            'institute' => [$account->role === 'Admin' ? 'required' : 'nullable', 'string', 'max:255', 'exists:institutes,institute_name'],
        ]);

        $teacher = User::create([
            'user_id' => $validated['user_id'],
            'name' => $validated['name'],
            'email' => $validated['email'],
            'qualification' => $validated['qualification'],
            'institute' => $account->role === 'InstituteAdmin' ? $account->institute : $validated['institute'],
            'role' => 'Teacher',
            'password' => Hash::make($validated['password']),
            'status' => (bool) $validated['status'],
        ]);

        return response()->json([
            'success' => true,
            'message' => 'STEM Engineer added successfully.',
            'teacher' => [
                'id' => $teacher->id,
                'user_id' => $teacher->user_id,
                'title' => $teacher->name,
                'subtitle' => trim(($teacher->user_id ? $teacher->user_id . ' · ' : '') . ($teacher->qualification ?: 'Qualification n/a') . ($teacher->institute ? ' · ' . $teacher->institute : '')),
                'status' => $teacher->status ? 'Active' : 'Inactive',
            ],
        ]);
    }

    public function updateAdminTeacher(Request $request, int $id)
    {
        $account = $this->requireAdmin($request);
        $teacher = User::where('role', 'Teacher')->findOrFail($id);

        $this->ensureAdminInstituteAccess($account, $teacher->institute);

        $validated = $request->validate([
            'user_id' => ['required', 'string', 'max:50', 'unique:users,user_id,' . $id],
            'name' => ['required', 'string', 'max:100'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email,' . $id],
            'qualification' => ['required', 'string', 'max:255'],
            'status' => ['required', 'boolean'],
            'institute' => [$account->role === 'Admin' ? 'required' : 'nullable', 'string', 'max:255', 'exists:institutes,institute_name'],
        ]);

        $teacher->update([
            'user_id' => $validated['user_id'],
            'name' => $validated['name'],
            'email' => $validated['email'],
            'qualification' => $validated['qualification'],
            'institute' => $account->role === 'InstituteAdmin' ? $account->institute : $validated['institute'],
            'status' => (bool) $validated['status'],
        ]);

        return response()->json([
            'success' => true,
            'message' => 'STEM Engineer updated successfully.',
        ]);
    }

    public function deleteAdminTeacher(Request $request, int $id)
    {
        $account = $this->requireAdmin($request);
        $teacher = User::where('role', 'Teacher')->findOrFail($id);
        $this->ensureAdminInstituteAccess($account, $teacher->institute);

        return app(\App\Services\MobileWebContext::class)->run($request,
            fn () => app(\App\Http\Controllers\UserController::class)->delete($id));
    }

    public function adminStudents(Request $request)
    {
        $account = $this->requireAdmin($request);
        $page = Student::query()
            ->tap(fn ($query) => app(\App\Services\MobileManagementFilters::class)->apply($query, $request, 'students'))
            ->when($account->role === 'InstituteAdmin', fn ($query) => $query->where('institute', $account->institute))
            ->orderBy('class')->orderBy('section')->orderBy('name')->orderBy('id')->paginate(50);
        return response()->json([
            'pagination' => $this->pageMetadata($page),
            'students' => $page->getCollection()
                ->map(function (Student $student) {
                    return [
                        'id' => $student->id,
                        'student_id' => $student->student_id,
                        'title' => $student->name ?: 'Student',
                        'subtitle' => trim(($student->class ?: 'Class n/a') . ($student->section ? ' · ' . $student->section : '') . ($student->institute ? ' · ' . $student->institute : '')),
                        'status' => $student->status ? 'Active' : 'Inactive',
                    ];
                })
                ->values(),
        ]);
    }

    public function adminStudent(Request $request, int $id)
    {
        $account = $this->requireAdmin($request);
        $student = Student::findOrFail($id);
        $this->ensureAdminInstituteAccess($account, $student->institute);

        return response()->json([
            'student' => [
                'id' => $student->id,
                'student_id' => $student->student_id,
                'title' => $student->name ?: 'Student',
                'subtitle' => trim(($student->student_id ? $student->student_id . ' · ' : '') . $student->class . ' ' . $student->section . ($student->institute ? ' · ' . $student->institute : '')),
                'contact' => $student->contact,
                'email' => $student->email,
                'guardian_name' => $student->guardian_name,
                'is_robotics_club_member' => (bool) $student->is_robotics_club_member,
                'class' => $student->class,
                'section' => $student->section,
                'institute' => $student->institute,
                'status' => $student->status ? 'Active' : 'Inactive',
            ],
        ]);
    }

    public function storeAdminStudent(Request $request)
    {
        $account = $this->requireAdmin($request);

        $validated = $request->validate([
            'student_id' => ['required', 'string', 'max:50', 'unique:students,student_id'],
            'name' => ['required', 'string', 'max:100'],
            'institute' => [$account->role === 'Admin' ? 'required' : 'nullable', 'string', 'max:255', 'exists:institutes,institute_name'],
            'class' => ['required', 'string', 'max:50'],
            'section' => ['required', 'string', 'max:20'],
            'contact' => ['required', 'string', 'max:20'],
            'email' => ['nullable', 'email', 'max:255'],
            'guardian_name' => ['nullable', 'string', 'max:255'],
            'is_robotics_club_member' => ['required', 'boolean'],
            'password' => ['required', 'string', 'min:6'],
            'status' => ['required', 'boolean'],
        ]);

        $studentInstitute = $account->role === 'InstituteAdmin' ? $account->institute : $validated['institute'];
        $classExists = SchoolClass::where('institute', $studentInstitute)
            ->where('class_name', $validated['class'])
            ->where('section', $validated['section'])
            ->exists();

        if (!$classExists) {
            throw ValidationException::withMessages([
                'class' => 'Select a valid class and section for the chosen institute.',
            ]);
        }

        $student = Student::create([
            'student_id' => $validated['student_id'],
            'name' => $validated['name'],
            'institute' => $studentInstitute,
            'class' => $validated['class'],
            'section' => $validated['section'],
            'contact' => $validated['contact'],
            'email' => $validated['email'] ?? null,
            'guardian_name' => $validated['guardian_name'] ?? null,
            'is_robotics_club_member' => (bool) $validated['is_robotics_club_member'],
            'password' => Hash::make($validated['password']),
            'status' => (bool) $validated['status'],
            'profile_completed' => true,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Student added successfully.',
            'student' => [
                'id' => $student->id,
                'student_id' => $student->student_id,
                'title' => $student->name,
                'subtitle' => trim(($student->student_id ? $student->student_id . ' · ' : '') . $student->class . ' ' . $student->section . ($student->institute ? ' · ' . $student->institute : '')),
                'status' => $student->status ? 'Active' : 'Inactive',
            ],
        ]);
    }

    public function updateAdminStudent(Request $request, int $id)
    {
        $account = $this->requireAdmin($request);
        $student = Student::findOrFail($id);

        $this->ensureAdminInstituteAccess($account, $student->institute);

        $validated = $request->validate([
            'student_id' => ['required', 'string', 'max:50', 'unique:students,student_id,' . $id],
            'name' => ['required', 'string', 'max:100'],
            'institute' => [$account->role === 'Admin' ? 'required' : 'nullable', 'string', 'max:255', 'exists:institutes,institute_name'],
            'class' => ['required', 'string', 'max:50'],
            'section' => ['required', 'string', 'max:20'],
            'contact' => ['required', 'string', 'max:20'],
            'email' => ['nullable', 'email', 'max:255'],
            'guardian_name' => ['nullable', 'string', 'max:255'],
            'is_robotics_club_member' => ['required', 'boolean'],
            'password' => ['nullable', 'string', 'min:6'],
            'status' => ['required', 'boolean'],
        ]);

        $studentInstitute = $account->role === 'InstituteAdmin' ? $account->institute : $validated['institute'];
        $classExists = SchoolClass::where('institute', $studentInstitute)
            ->where('class_name', $validated['class'])
            ->where('section', $validated['section'])
            ->exists();

        if (!$classExists) {
            throw ValidationException::withMessages([
                'class' => 'Select a valid class and section for the chosen institute.',
            ]);
        }

        $studentData = [
            'student_id' => $validated['student_id'],
            'name' => $validated['name'],
            'institute' => $studentInstitute,
            'class' => $validated['class'],
            'section' => $validated['section'],
            'contact' => $validated['contact'],
            'email' => $validated['email'] ?? null,
            'guardian_name' => $validated['guardian_name'] ?? null,
            'is_robotics_club_member' => (bool) $validated['is_robotics_club_member'],
            'status' => (bool) $validated['status'],
            'profile_completed' => true,
        ];

        $newPassword = (string) ($validated['password'] ?? '');
        $student->update($studentData);

        if ($newPassword !== '') {
            DB::table('students')->where('id', $student->id)->update([
                'password' => Hash::make($newPassword),
                'updated_at' => now(),
            ]);
            $student->refresh();
            if (!Hash::check($newPassword, (string) $student->password)) {
                throw ValidationException::withMessages([
                    'password' => 'The new student password could not be saved. Please try again.',
                ]);
            }
        }

        return response()->json([
            'success' => true,
            'message' => 'Student updated successfully.',
        ]);
    }

    public function deleteAdminStudent(Request $request, int $id)
    {
        $account = $this->requireAdmin($request);
        $student = Student::findOrFail($id);

        $this->ensureAdminInstituteAccess($account, $student->institute);
        return app(\App\Services\MobileWebContext::class)->run($request,
            fn () => app(\App\Http\Controllers\PageController::class)->deleteStudent($id));
    }

    public function storeAdminClass(Request $request)
    {
        $account = $this->requireAdmin($request);

        $validated = $request->validate([
            'class_name' => ['required', 'string', 'max:50'],
            'sections' => ['required', 'array', 'min:1'],
            'sections.*' => ['required', 'string', 'in:A,B,C,D,E,Combined'],
            'academic_year' => ['required', 'string', 'max:20'],
            'status' => ['required', 'boolean'],
            'institute' => [$account->role === 'Admin' ? 'required' : 'nullable', 'string', 'max:255', 'exists:institutes,institute_name'],
        ]);

        $institute = $account->role === 'InstituteAdmin' ? $account->institute : $validated['institute'];
        $created = 0;
        foreach (array_values(array_unique($validated['sections'])) as $section) {
            $class = SchoolClass::firstOrCreate(
                [
                    'institute' => $institute,
                    'class_name' => $validated['class_name'],
                    'section' => $section,
                    'academic_year' => $validated['academic_year'],
                ],
                [
                    'class_teacher' => null,
                    'status' => (bool) $validated['status'],
                ]
            );

            if ($class->wasRecentlyCreated) {
                $created++;
            }
        }

        return response()->json([
            'success' => true,
            'message' => $created > 0 ? "{$created} class section(s) added successfully." : 'No new class sections were added because they already exist.',
        ]);
    }

    public function updateAdminClass(Request $request, int $id)
    {
        $account = $this->requireAdmin($request);
        $class = SchoolClass::findOrFail($id);
        $this->ensureAdminInstituteAccess($account, $class->institute);

        $validated = $request->validate([
            'class_name' => ['required', 'string', 'max:50'],
            'section' => ['required', 'string', 'max:20'],
            'academic_year' => ['required', 'string', 'max:20'],
            'status' => ['required', 'boolean'],
        ]);

        $class->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'Class updated successfully.',
        ]);
    }

    public function deleteAdminClass(Request $request, int $id)
    {
        $account = $this->requireAdmin($request);
        $class = SchoolClass::findOrFail($id);
        $this->ensureAdminInstituteAccess($account, $class->institute);
        return app(\App\Services\MobileWebContext::class)->run($request,
            fn () => app(\App\Http\Controllers\ClassController::class)->delete($id));
    }

    public function adminCourses(Request $request)
    {
        $account = $this->requireAdmin($request);

        return response()->json([
            'courses' => Course::query()
                ->tap(fn ($query) => app(\App\Services\MobileManagementFilters::class)->apply($query, $request, 'courses'))
                ->when(
                    $account->role === 'InstituteAdmin',
                    fn ($query) => $query
                        ->where('institute', $account->institute)
                        ->where('availability_type', 'Institute')
                )
                ->orderBy('institute')
                ->orderBy('course_title')
                ->get()
                ->map(function (Course $course) use ($account) {
                    $canManage = $account->role === 'Admin' || ! $this->isHybridLearnerCourse($course);
                    return [
                        'id' => $course->id,
                        'title' => $course->course_title,
                        'subtitle' => trim(($course->institute ? 'Institute: ' . $course->institute . ' · ' : '') . ($course->assigned_class ?? 'General course')),
                        'status' => $course->status ? 'Active' : 'Draft',
                        'availability_type' => $course->availability_type,
                        'can_manage' => $canManage,
                    ];
                })
                ->values(),
        ]);
    }

    public function adminCourse(Request $request, int $id)
    {
        $account = $this->requireAdmin($request);
        $course = Course::findOrFail($id);
        $this->ensureAdminInstituteAccess($account, $course->institute);
        $this->ensureHybridLearnerCourseManagementAllowed($account, $course);

        return response()->json([
            'course' => [
                'id' => $course->id,
                'title' => $course->course_title,
                'subtitle' => trim(($course->institute ? 'Institute: ' . $course->institute . ' · ' : '') . ($course->assigned_class ?? 'General course')),
                'description' => $course->description,
                'target' => $course->target,
                'assigned_class' => $course->assigned_class,
                'price' => $course->price,
                'availability_type' => $course->availability_type,
                'is_template_source' => (bool) $course->is_template_source,
                'institute' => $course->institute,
                'status' => $course->status ? 'Active' : 'Draft',
            ],
        ]);
    }

    private function courseInput(Request $request, User $account): array
    {
        abort_if($account->role !== 'Admin' && $request->boolean('is_template_source'), 403, 'Only Super Admin can manage template source courses.');
        abort_if($account->role === 'InstituteAdmin' && blank($account->institute), 403);
        $template = $account->role === 'Admin' && $request->boolean('is_template_source');
        $data = $request->validate([
            'course_title' => 'required|string|max:255', 'description' => 'nullable|string',
            'target' => 'required|in:Student,Teacher,Both', 'assigned_class' => 'nullable|string|max:255',
            'price' => 'required|numeric|min:0', 'availability_type' => 'required|in:Institute,Independent,Both',
            'is_active' => 'required|boolean', 'is_template_source' => 'nullable|boolean',
            'institute' => [$account->role === 'Admin' && !$template ? 'required' : 'nullable', 'string', 'exists:institutes,institute_name'],
        ]);
        $this->ensureHybridLearnerCourseManagementAllowed($account, $data['availability_type'] ?? null);
        $data['institute'] = $account->role === 'InstituteAdmin' ? $account->institute : ($template ? null : ($data['institute'] ?? null));
        if (!empty($data['assigned_class']) && !SchoolClass::where('class_name', $data['assigned_class'])
            ->when($data['institute'], fn ($q) => $q->where('institute', $data['institute']))->exists()) {
            throw ValidationException::withMessages(['assigned_class' => 'Select an existing class for this institute.']);
        }
        $data['is_template_source'] = $template;
        $data['status'] = (bool) $data['is_active'];
        return $data;
    }

    public function storeAdminCourse(Request $request)
    {
        $account = $this->requireAdmin($request);
        $course = Course::create($this->courseInput($request, $account) + ['certificate_enabled' => 1]);
        return response()->json(['message' => 'Course created successfully.', 'course' => ['id' => $course->id]], 201);
    }

    public function updateAdminCourse(Request $request, int $id)
    {
        $account = $this->requireAdmin($request);
        $course = Course::findOrFail($id);
        $this->ensureAdminInstituteAccess($account, $course->institute);
        $this->ensureHybridLearnerCourseManagementAllowed($account, $course);
        $data = $this->courseInput($request, $account);
        if ($data['institute'] !== $course->institute && ($course->contents()->exists() || TeachingPlan::where('course_id', $id)->exists())) {
            throw ValidationException::withMessages(['institute' => 'A course with lessons or teaching plans cannot be moved to another institute.']);
        }
        $course->update($data);
        return response()->json(['message' => 'Course updated successfully.']);
    }

    public function deleteAdminCourse(Request $request, int $id)
    {
        $account = $this->requireAdmin($request);
        $course = Course::findOrFail($id);
        $this->ensureAdminInstituteAccess($account, $course->institute);
        $this->ensureHybridLearnerCourseManagementAllowed($account, $course);
        return app(\App\Http\Controllers\CourseController::class)->delete($id);
    }

    public function adminTeachingPlans(Request $request)
    {
        $account = $this->requireAdmin($request);

        $query = TeachingPlan::query()
            ->with('course')
            ->withCount([
                'weeks',
                'weeks as released_weeks_count' => fn ($query) => $query->where('status', 'released'),
                'weeks as completed_weeks_count' => fn ($query) => $query->where('status', 'completed'),
            ])
            ->when(
                $account->role === 'InstituteAdmin',
                fn ($query) => $query->where('institute', $account->institute)
            )
            ->when($request->filled('institute'), fn ($q) => $q->where('institute', $request->input('institute')))
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->input('status')))
            ->when($request->filled('is_template'), fn ($q) => $q->where('is_template', $request->boolean('is_template')))
            ->when($request->filled('plan_class'), fn ($q) => $q->where('class', $request->input('plan_class')))
            ->when($request->filled('plan_section'), function ($q) use ($request) {
                $request->input('plan_section') === '__unassigned'
                    ? $q->where(fn ($q) => $q->whereNull('section')->orWhere('section', ''))
                    : $q->where('section', $request->input('plan_section'));
            })
            ->when($request->filled('search'), fn ($q) => $q->where(fn ($q) => $q
                ->where('title', 'like', '%'.$request->input('search').'%')
                ->orWhere('class', 'like', '%'.$request->input('search').'%')
                ->orWhereHas('course', fn ($q) => $q->where('course_title', 'like', '%'.$request->input('search').'%'))));
        $page = $query->latest()->orderByDesc('id')->paginate(25);
        $plans = collect($page->items())->map(function (TeachingPlan $plan) {
                return [
                    'id' => $plan->id,
                    'title' => $plan->is_template ? ($plan->title ?: 'Teaching Plan Template') : trim(($plan->class ?: 'Class n/a') . ($plan->section ? ' · ' . $plan->section : '')),
                    'subtitle' => trim(($plan->course?->course_title ?: 'Course') . ' · ' . ($plan->institute ?: 'Template / institute n/a')),
                    'status' => $plan->status ?: 'inactive',
                    'is_template' => (bool) $plan->is_template,
                    'institute' => $plan->institute,
                    'course_id' => $plan->course_id,
                    'remarks' => $plan->remarks,
                    'release_policy' => $plan->release_policy,
                    'ai_training_start_date' => optional($plan->ai_training_start_date)->toDateString(),
                    'weeks_count' => (int) $plan->weeks_count,
                    'released_weeks_count' => (int) $plan->released_weeks_count,
                    'completed_weeks_count' => (int) $plan->completed_weeks_count,
                ];
            })
            ->values();

        $requestedInstitute = trim((string) $request->input('institute', ''));
        $selectedInstitute = $account->role === 'InstituteAdmin'
            ? ($requestedInstitute !== '' && $requestedInstitute !== $account->institute ? '__unauthorised_institute__' : $account->institute)
            : $requestedInstitute;
        $selectedInstitute = $selectedInstitute !== '' ? $selectedInstitute : null;
        $configuredClasses = SchoolClass::query()
            ->where('status', 1)
            ->when($selectedInstitute, fn ($q) => $q->where('institute', $selectedInstitute));
        $planOptions = TeachingPlan::query()
            ->when($account->role === 'InstituteAdmin', fn ($q) => $q->where('institute', $account->institute))
            ->when($selectedInstitute, fn ($q) => $q->where('institute', $selectedInstitute));
        $classOptions = (clone $configuredClasses)->pluck('class_name')
            ->merge((clone $planOptions)->pluck('class'))
            ->filter()
            ->unique()
            ->sort()
            ->values();
        $sectionOptions = (clone $configuredClasses)
            ->when($request->filled('plan_class'), fn ($q) => $q->where('class_name', $request->input('plan_class')))
            ->pluck('section')
            ->merge((clone $planOptions)->when($request->filled('plan_class'), fn ($q) => $q->where('class', $request->input('plan_class')))->pluck('section'))
            ->map(fn ($section) => filled($section) ? $section : '__unassigned')
            ->filter()
            ->unique()
            ->sort()
            ->values();
        return response()->json([
            'plans' => $plans,
            'pagination' => $this->pageMetadata($page),
            'classes' => $classOptions,
            'sections' => $sectionOptions,
            'institutes' => Institute::query()->where('status', 1)
                ->when($account->role === 'InstituteAdmin', fn ($q) => $q->where('institute_name', $account->institute))
                ->orderBy('institute_name')->pluck('institute_name'),
        ]);
    }

    public function adminTeachingPlan(Request $request, int $id)
    {
        $account = $this->requireAdmin($request);
        $plan = TeachingPlan::with([
            'course',
            'weeks' => fn ($query) => $query->withCount('items')->orderBy('week_number'),
        ])->findOrFail($id);
        $this->ensureAdminInstituteAccess($account, $plan->institute);

        return response()->json([
            'plan' => $this->mobileTeachingPlanPayload($plan),
        ]);
    }

    public function updateAdminTeachingPlan(Request $request, int $id)
    {
        $account = $this->requireAdmin($request);
        $plan = TeachingPlan::findOrFail($id);
        $this->ensureAdminInstituteAccess($account, $plan->institute);

        $validated = $request->validate([
            'title' => ['nullable', 'string', 'max:255'],
            'start_date' => ['nullable', 'date'],
            'release_day' => ['required', 'in:Monday,Tuesday,Wednesday,Thursday,Friday,Saturday,Sunday'],
            'status' => ['required', 'in:active,inactive,completed'],
            'remarks' => ['nullable', 'string', 'max:2000'],
        ]);

        DB::transaction(function () use ($plan, $validated) {
            $oldStartDate = $plan->start_date ? Carbon::parse($plan->start_date)->toDateString() : null;
            $newStartDate = $validated['start_date'] ?? $oldStartDate;
            $scheduleChanged = $oldStartDate !== $newStartDate || $plan->release_day !== $validated['release_day'];

            $plan->update([
                'title' => filled($validated['title'] ?? null) ? $validated['title'] : $plan->title,
                'start_date' => $newStartDate,
                'plan_start_date' => $newStartDate,
                'release_day' => $validated['release_day'],
                'status' => $validated['status'],
                'remarks' => $validated['remarks'] ?? null,
            ]);

            if ($scheduleChanged && $newStartDate) {
                $startDate = Carbon::parse($newStartDate)->startOfDay();
                $plan->weeks()->where('status', 'locked')->orderBy('week_number')->get()->each(function (TeachingPlanWeek $week) use ($startDate, $validated) {
                    $weekStart = $startDate->copy()->addWeeks(max(0, ((int) $week->week_number) - 1));
                    $releaseDate = (int) $week->week_number === 1
                        ? $weekStart->copy()
                        : (strtolower($weekStart->format('l')) === strtolower($validated['release_day'])
                            ? $weekStart->copy()->subWeek()
                            : $weekStart->copy()->previous($validated['release_day']));

                    $week->update([
                        'week_start_date' => $weekStart->toDateString(),
                        'week_end_date' => $weekStart->copy()->addDays(6)->toDateString(),
                        'release_date' => $releaseDate->toDateString(),
                    ]);
                });
            }
        });

        return response()->json([
            'success' => true,
            'message' => 'Teaching Plan updated successfully.',
            'plan' => $this->mobileTeachingPlanPayload($plan->fresh(['course', 'weeks'])),
        ]);
    }

    public function releaseNextAdminTeachingPlan(Request $request, int $id, TeachingPlanReleaseService $releaseService)
    {
        $account = $this->requireAdmin($request);
        $plan = TeachingPlan::findOrFail($id);
        $this->ensureAdminInstituteAccess($account, $plan->institute);

        if ($plan->is_template) {
            throw ValidationException::withMessages([
                'plan' => 'Templates must be deployed before their weeks can be released.',
            ]);
        }

        $week = $releaseService->releaseNextWeek($plan, 'manual_release');
        if (!$week) {
            throw ValidationException::withMessages([
                'plan' => 'No locked week is available to release.',
            ]);
        }

        return response()->json([
            'success' => true,
            'message' => "Week {$week->week_number} released successfully.",
            'week' => $this->mobileTeachingPlanWeekPayload($week->fresh()->loadCount('items')),
        ]);
    }

    public function updateAdminTeachingPlanWeek(Request $request, int $id, int $weekId)
    {
        $account = $this->requireAdmin($request);
        $plan = TeachingPlan::findOrFail($id);
        $this->ensureAdminInstituteAccess($account, $plan->institute);
        $week = TeachingPlanWeek::where('teaching_plan_id', $plan->id)->findOrFail($weekId);

        $validated = $request->validate([
            'status' => ['required', 'in:locked,released,completed,skipped'],
        ]);

        if ($validated['status'] === 'completed' && $week->items()->where('status', '!=', 'completed')->exists()) {
            throw ValidationException::withMessages([
                'status' => 'A week can only be completed after all topics are completed through STEM Engineer sessions.',
            ]);
        }

        if ($validated['status'] === 'released') {
            if ($week->status !== 'released' && !app(TeachingPlanReleaseService::class)->releaseWeek($week)) {
                throw ValidationException::withMessages(['status' => 'This week cannot be released before the plan starts or its required earlier weeks are complete.']);
            }
            return response()->json(['success' => true, 'message' => 'Week released.', 'week' => $this->mobileTeachingPlanWeekPayload($week->fresh()->loadCount('items'))]);
        }

        $week->update([
            'status' => $validated['status'],
            'released_at' => $validated['status'] === 'released' ? ($week->released_at ?: now()) : $week->released_at,
            'completed_at' => $validated['status'] === 'completed' ? ($week->completed_at ?: now()) : null,
        ]);

        if (in_array($validated['status'], ['locked', 'released', 'skipped'], true)) {
            $week->items()->update([
                'status' => $validated['status'],
                'released_at' => $validated['status'] === 'released' ? now() : null,
                'completed_at' => null,
            ]);
        }

        return response()->json([
            'success' => true,
            'message' => 'Teaching Plan week updated successfully.',
            'week' => $this->mobileTeachingPlanWeekPayload($week->fresh()->loadCount('items')),
        ]);
    }

    public function completeTeacherTopic(Request $request, int $contentId)
    {
        $a = $request->user();
        abort_unless($a instanceof User && in_array($a->role, ['Admin', 'Teacher', 'STEM Engineer'], true), 403);
        $request->validate(['plan_item_id' => 'nullable|integer']);
        return app(\App\Services\MobileWebContext::class)->run($request,
            fn () => app(\App\Http\Controllers\UserController::class)->markTopicComplete($request, $contentId));
    }

    public function engineerSessions(Request $request)
    {
        $teacher = $request->user();
        app(\App\Services\ClassSessionLifecycle::class)->expire($teacher);
        $class = $request->filled('class_id')
            ? SchoolClass::where('institute', $teacher->institute)->findOrFail($request->integer('class_id')) : null;
        $today = now()->toDateString();
        $currentWeekStart = now()->copy()->startOfWeek()->toDateString();
        $currentWeekEnd = now()->copy()->endOfWeek()->toDateString();

        $completedPlanItems = ClassContentSession::where('stem_engineer_id', $teacher->id)
            ->where('status', 'completed')
            ->whereNotNull('teaching_plan_item_id')
            ->select('teaching_plan_item_id');

        $latestPendingSessionIds = ClassContentSession::query()
            ->selectRaw('MAX(id)')
            ->where('institute', $teacher->institute)->where('stem_engineer_id', $teacher->id)
            ->whereIn('status', ['in_progress', 'partially_completed', 'cancelled'])
            ->when($class, fn ($q) => $q->where('class', $class->class_name)->where('section', $class->section))
            ->where(function ($q) use ($completedPlanItems) {
                $q->whereNull('teaching_plan_item_id')
                    ->orWhereNotIn('teaching_plan_item_id', $completedPlanItems);
            })
            ->groupBy('teaching_plan_item_id', 'class', 'section', 'content_id');

        $pendingPage = ClassContentSession::query()
            ->whereIn('id', $latestPendingSessionIds)
            ->orderByRaw("CASE WHEN status = 'in_progress' THEN 0 ELSE 1 END")
            ->latest('session_date')->orderByDesc('id')->paginate(30);
        $todayPage = ClassContentSession::query()
            ->where('institute', $teacher->institute)
            ->where('stem_engineer_id', $teacher->id)
            ->whereDate('session_date', $today)
            ->when($class, fn ($q) => $q->where('class', $class->class_name)->where('section', $class->section))
            ->with(['course', 'content', 'teachingPlan', 'teachingPlanWeek', 'teachingPlanItem'])
            ->latest('session_date')
            ->orderByDesc('id')
            ->paginate(30);
        $contentPage = TeachingPlanItem::query()
            ->whereHas('plan', function ($q) use ($teacher, $class) {
                $q->where('institute', $teacher->institute)->where('is_template', false)->whereIn('status', ['active', 'completed'])
                    ->when($class, fn ($q) => $q->where('class', $class->class_name)->where('section', $class->section));
            })
            ->whereIn('status', ['released', 'completed'])
            ->whereHas('week', function ($q) use ($today, $currentWeekStart, $currentWeekEnd) {
                $q->where(function ($weekQuery) use ($today) {
                    $weekQuery->whereDate('week_start_date', '<=', $today)
                        ->whereDate('week_end_date', '>=', $today);
                })->orWhere(function ($weekQuery) use ($currentWeekStart, $currentWeekEnd) {
                    $weekQuery->whereNull('week_start_date')
                        ->whereNull('week_end_date')
                        ->whereBetween('release_date', [$currentWeekStart, $currentWeekEnd]);
                });
            })
            ->whereHas('content', fn ($q) => $q->where('status', 1))
            ->with(['content.aiSummary', 'content.courseContent.sourceTemplateContent.aiSummary', 'plan', 'week'])
            ->orderBy('teaching_plan_week_id')->orderBy('sort_order')->orderBy('id')->paginate(30);

        return response()->json([
            'pagination' => [
                'pending_sessions' => $this->pageMetadata($pendingPage),
                'today_sessions' => $this->pageMetadata($todayPage),
                'learning_content' => $this->pageMetadata($contentPage),
            ],
            'my_classes' => SchoolClass::query()
                ->where('institute', $teacher->institute)
                ->orderBy('class_name')
                ->orderBy('section')
                ->get()
                ->map(function (SchoolClass $class) {
                    return [
                        'id' => $class->id,
                        'title' => trim($class->class_name . ($class->section ? ' ' . $class->section : '')),
                        'subtitle' => trim('Academic year: ' . ($class->academic_year ?? 'n/a')),
                        'class_id' => $class->id,
                        'can_start' => false,
                    ];
                })
                ->values(),
            'pending_sessions' => $pendingPage->getCollection()
                ->map(function (ClassContentSession $session) {
                    return [
                        'id' => $session->id,
                        'session_id' => $session->id,
                        'item_id' => $session->teaching_plan_item_id,
                        'content_id' => $session->content_id,
                        'title' => $session->planned_topic ?: 'Session',
                        'subtitle' => trim(($session->class ?? 'Class n/a') . ($session->section ? ' · ' . $session->section : '') . ($session->status ? ' · ' . $session->status : '')),
                        'status' => $session->status,
                        'can_end' => in_array($session->status, ['in_progress', 'started'], true),
                    ];
                })
                ->values(),
            'today_sessions' => $todayPage->getCollection()
                ->map(function (ClassContentSession $session) {
                    return [
                        'id' => $session->id,
                        'session_id' => $session->id,
                        'item_id' => $session->teaching_plan_item_id,
                        'content_id' => $session->content_id,
                        'title' => $session->planned_topic ?: ($session->content?->content_title ?: 'Session'),
                        'subtitle' => trim(($session->class ?? 'Class n/a') . ($session->section ? ' · ' . $session->section : '') . ($session->status ? ' · ' . $session->status : '')),
                        'status' => $session->status,
                        'session_date' => $session->session_date,
                        'start_time' => $session->start_time,
                        'end_time' => $session->end_time,
                        'can_end' => in_array($session->status, ['in_progress', 'started'], true),
                    ];
                })
                ->values(),
            'learning_content' => $contentPage->getCollection()
                ->map(function (TeachingPlanItem $item) use ($teacher) {
                    $hasCompletedSession = ClassContentSession::query()
                        ->where('teaching_plan_item_id', $item->id)
                        ->where('status', 'completed')
                        ->exists();
                    $requiresAiPrep = $this->teachingPlanItemRequiresAiTraining($item)
                        && !$this->teachingPlanItemBypassesAiPrepForTeacher($item, $teacher);
                    $hasReadyAiPrep = !$requiresAiPrep || (
                        $this->generatedAiSummaryForContentRecord($item->content)
                        && !$this->teacherNeedsAiPrep(
                            $item->content,
                            $teacher->id,
                            $this->gradeLevelFromClass($item->plan?->class)
                        )
                    );

                    return [
                        'id' => $item->id,
                        'item_id' => $item->id,
                        'content_id' => $item->content_id,
                        'title' => $item->content?->content_title ?: 'Learning content',
                        'subtitle' => trim(($item->plan?->class ?? 'Class n/a') . ($item->plan?->section ? ' · ' . $item->plan->section : '') . ($item->status ? ' · ' . $item->status : '')),
                        'status' => $item->status ?: 'Planned',
                        'can_start' => $item->status === 'released'
                            && $item->week?->status === 'released'
                            && !$hasCompletedSession
                            && $item->content
                            && (bool) $item->content->file_path
                            && $item->content->status == 1
                            && $hasReadyAiPrep,
                    ];
                })
                ->values(),
        ]);
    }

    public function startEngineerSession(Request $request)
    {
        app(\App\Services\ClassSessionLifecycle::class)->expire($request->user());
        $teacher = $request->user();

        abort_unless($teacher instanceof User && in_array($teacher->role, ['Teacher', 'STEM Engineer'], true), 403, 'Teacher access is required.');

        $validated = $request->validate([
            'teaching_plan_item_id' => ['required', 'integer', 'exists:teaching_plan_items,id'],
            'session_day' => ['nullable', 'string', 'max:20'],
            'session_date' => ['nullable', 'date'],
            'start_time' => ['nullable'],
        ]);

        $item = TeachingPlanItem::with([
                'plan',
                'week',
                'content.aiSummary',
                'content.courseContent.sourceTemplateContent.aiSummary',
                'course',
            ])
            ->where('status', 'released')
            ->whereHas('plan', function ($query) use ($teacher) {
                $query->where('institute', $teacher->institute)
                    ->whereIn('status', ['active', 'completed']);
            })
            ->findOrFail($validated['teaching_plan_item_id']);

        if (!$item->content || !$item->content->file_path || $item->content->status != 1) {
            abort(409, 'This Teaching Plan topic is missing its active content file.');
        }

        if (!$item->week || $item->week->status !== 'released') {
            abort(409, 'Only currently released Teaching Plan topics can be started.');
        }

        $requiresAiPrep = $this->teachingPlanItemRequiresAiTraining($item)
            && !$this->teachingPlanItemBypassesAiPrepForTeacher($item, $teacher);
        if ($requiresAiPrep && !$this->generatedAiSummaryForContentRecord($item->content)) {
            abort(409, 'AI prep is still being prepared for this content. Please try again shortly.');
        }
        if ($requiresAiPrep && $this->teacherNeedsAiPrep(
            $item->content,
            $teacher->id,
            $this->gradeLevelFromClass($item->plan?->class)
        )) {
            abort(409, 'Please pass the AI prep assessment before starting this session.');
        }

        $existingSession = ClassContentSession::where('stem_engineer_id', $teacher->id)
            ->where('status', 'in_progress')
            ->first();

        if ($existingSession) {
            abort(409, 'A session is already running.');
        }

        $activeClassSession = ClassContentSession::where('institute', $teacher->institute)
            ->where('class', $item->plan->class)
            ->where('status', 'in_progress')
            ->where(function ($query) use ($item) {
                if (filled($item->plan->section)) {
                    $query->where('section', $item->plan->section);
                } else {
                    $query->whereNull('section')
                        ->orWhere('section', '');
                }
            })
            ->first();

        if ($activeClassSession) {
            abort(409, trim($item->plan->class . ' ' . $item->plan->section) . ' already has an active session.');
        }

        $session = ClassContentSession::create([
            'institute' => $teacher->institute,
            'course_id' => $item->course_id,
            'course_content_id' => $item->course_content_id,
            'teaching_plan_id' => $item->teaching_plan_id,
            'teaching_plan_week_id' => $item->teaching_plan_week_id,
            'teaching_plan_item_id' => $item->id,
            'content_id' => $item->content_id,
            'stem_engineer_id' => $teacher->id,
            'class' => $item->plan->class,
            'section' => $item->plan->section,
            'session_day' => ($validated['session_day'] ?? null) ?: Carbon::parse($validated['session_date'] ?? now())->format('l'),
            'session_date' => $validated['session_date'] ?? now()->toDateString(),
            'start_time' => $validated['start_time'] ?? now()->format('H:i:s'),
            'started_at' => now(),
            'status' => 'in_progress',
            'planned_topic' => $item->content->content_title ?? null,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Class session started successfully.',
            'session_id' => $session->id,
            'content_id' => $session->content_id,
            'started_at' => $session->started_at?->toIso8601String(),
            'server_time' => now()->toIso8601String(),
        ]);
    }

    public function engineerAiPrep(Request $request, int $contentId)
    {
        $teacher = $request->user();
        abort_unless($teacher instanceof User && in_array($teacher->role, ['Teacher', 'STEM Engineer'], true), 403, 'STEM Engineer access is required.');

        $content = Content::with(['aiSummary', 'courseContent.sourceTemplateContent.aiSummary'])
            ->where('id', $contentId)
            ->where('institute', $teacher->institute)
            ->where('status', 1)
            ->firstOrFail();
        $summary = $this->generatedAiSummaryForContentRecord($content);
        abort_unless($summary, 409, 'AI prep is not available for this content yet.');

        $gradeLevel = $this->engineerPrepGrade($request, $teacher, $content);
        $quiz = $this->aiQuizForContent($content, $summary, 'teacher', $gradeLevel);
        $attempt = AiQuizAttempt::where('ai_quiz_id', $quiz->id)
            ->where('teacher_id', $teacher->id)
            ->where('attempt_type', 'teacher_prep')
            ->latest()
            ->first();

        return response()->json([
            'success' => true,
            'content' => ['id' => $content->id, 'title' => $content->content_title, 'description' => $content->description],
            'summary' => $summary->summary,
            'key_points' => $summary->key_points ?? [],
            'grade_level' => $gradeLevel,
            'passing_percentage' => $this->teacherAiPassingPercentage(),
            'already_passed' => $attempt?->status === 'passed',
            'latest_attempt' => $attempt ? ['status' => $attempt->status, 'percentage' => (float) ($attempt->percentage ?? 0), 'feedback' => $attempt->feedback] : null,
            'quiz' => [
                'id' => $quiz->id,
                'title' => $quiz->title,
                'instructions' => $quiz->instructions,
                'questions' => $quiz->questions()->orderBy('question_order')->get()->map(fn (AiQuizQuestion $question) => [
                    'id' => $question->id,
                    'order' => $question->question_order,
                    'question' => $question->question_text,
                    'options' => $question->options ?? [],
                    'marks' => (int) $question->marks,
                ])->values(),
            ],
        ]);
    }

    public function submitEngineerAiPrep(Request $request, int $contentId)
    {
        $teacher = $request->user();
        abort_unless($teacher instanceof User && in_array($teacher->role, ['Teacher', 'STEM Engineer'], true), 403, 'STEM Engineer access is required.');
        $content = Content::with(['aiSummary', 'courseContent.sourceTemplateContent.aiSummary'])
            ->where('id', $contentId)->where('institute', $teacher->institute)->where('status', 1)->firstOrFail();
        $summary = $this->generatedAiSummaryForContentRecord($content);
        abort_unless($summary, 409, 'AI prep is not available for this content yet.');
        $gradeLevel = $this->engineerPrepGrade($request, $teacher, $content);
        $quiz = $this->aiQuizForContent($content, $summary, 'teacher', $gradeLevel);
        $validated = $request->validate(['answers' => ['required', 'array'], 'answers.*' => ['nullable', 'string', 'max:500']]);
        $answers = collect($validated['answers'])->map(fn ($answer) => trim((string) $answer));
        abort_if($answers->filter()->isEmpty(), 422, 'Please answer at least one question before submitting the prep quiz.');
        abort_if(AiQuizAttempt::where('ai_quiz_id', $quiz->id)->where('teacher_id', $teacher->id)->where('attempt_type', 'teacher_prep')->where('status', 'passed')->exists(), 409, 'Prep quiz already cleared.');
        $questions = $quiz->questions()->orderBy('question_order')->get();
        $attempt = AiQuizAttempt::create(['ai_quiz_id' => $quiz->id, 'content_id' => $this->aiQuizOwnerContent($content)->id, 'attempt_type' => 'teacher_prep', 'grade_level' => $gradeLevel, 'teacher_id' => $teacher->id, 'status' => 'submitted', 'started_at' => now(), 'submitted_at' => now()]);
        foreach ($questions as $question) {
            AiQuizAnswer::create(['ai_quiz_attempt_id' => $attempt->id, 'ai_quiz_question_id' => $question->id, 'answer_text' => $answers->get($question->id)]);
        }
        $evaluation = $this->evaluateMcqQuizAttempt($questions, $answers);
        $percentage = (float) ($evaluation['percentage'] ?? 0);
        $status = $percentage >= $this->teacherAiPassingPercentage() ? 'passed' : 'failed';
        $attempt->update(['score' => $evaluation['score'], 'percentage' => $percentage, 'status' => $status, 'feedback' => $evaluation['feedback'], 'evaluated_at' => now()]);
        foreach ($evaluation['answer_feedback'] as $feedback) {
            AiQuizAnswer::where('ai_quiz_attempt_id', $attempt->id)->where('ai_quiz_question_id', $feedback['question_id'])->update(['score' => $feedback['score'], 'feedback' => $feedback['feedback']]);
        }
        return response()->json(['success' => true, 'status' => $status, 'percentage' => $percentage, 'score' => $evaluation['score'], 'total_marks' => $evaluation['total_marks'], 'message' => $status === 'passed' ? 'AI prep passed. You can now start this session.' : 'AI prep score is below the passing percentage. Please review and try again.']);
    }

    public function engineerContentPreview(Request $request, int $contentId)
    {
        $teacher = $request->user();
        abort_unless($teacher instanceof User && in_array($teacher->role, ['Teacher', 'STEM Engineer'], true), 403, 'STEM Engineer access is required.');
        $content = Content::where('id', $contentId)->where('institute', $teacher->institute)->where('status', 1)->firstOrFail();
        app(\App\Services\MobileContentAccess::class)->authorize($teacher, $content);
        return response()->json(app(\App\Services\MobilePreviewService::class)->payload(
            $content, 'mobile.engineer.content-preview',
            ['contentId' => $content->id, 'account_id' => $teacher->id], 'teacher'
        ));
    }

    public function serveEngineerContentPreview(Request $request, int $contentId)
    {
        $teacher = User::findOrFail($request->integer('account_id'));
        abort_unless(in_array($teacher->role, ['Teacher', 'STEM Engineer'], true), 403);
        $content = Content::where('id', $contentId)->where('status', 1)->firstOrFail();
        app(\App\Services\MobileContentAccess::class)->authorize($teacher, $content);
        return app(\App\Services\MobilePreviewService::class)->response($content, 'teacher');
    }

    public function studentContentPreview(Request $request, int $contentId)
    {
        $student = $request->user();
        abort_unless($student instanceof Student, 403, 'Student access is required.');
        abort_unless($this->studentAvailableContentIds($student)->contains($contentId), 403, 'This lesson is not available to this student.');
        $content = Content::where('id', $contentId)->where('status', 1)->firstOrFail();
        app(\App\Services\MobileContentAccess::class)->authorize($student, $content);

        return $this->mobilePreviewPayload(
            $content,
            'mobile.student.content-preview',
            ['studentId' => $student->id, 'contentId' => $content->id]
        );
    }

    public function serveStudentContentPreview(Request $request, int $studentId, int $contentId)
    {
        $student = Student::findOrFail($studentId);
        abort_unless($this->studentAvailableContentIds($student)->contains($contentId), 403, 'This lesson is not available to this student.');
        $content = Content::where('id', $contentId)->where('status', 1)->firstOrFail();
        app(\App\Services\MobileContentAccess::class)->authorize($student, $content);

        return app(\App\Services\MobilePreviewService::class)->response($content);
    }

    public function hybridLearnerLessonPreview(Request $request, int $contentId)
    {
        $learner = $this->requireHybridLearner($request);
        $content = Content::where('id', $contentId)->where('status', 1)->where('is_released', true)->firstOrFail();
        CourseEnrollment::where('learner_id', $learner->id)->where('course_id', $content->course_id)->firstOrFail();
        app(\App\Services\MobileContentAccess::class)->authorize($learner, $content);

        return $this->mobilePreviewPayload(
            $content,
            'mobile.hybrid.content-preview',
            ['learnerId' => $learner->id, 'contentId' => $content->id]
        );
    }

    public function serveHybridLearnerLessonPreview(Request $request, int $learnerId, int $contentId)
    {
        $learner = IndependentLearner::findOrFail($learnerId);
        $content = Content::where('id', $contentId)->where('status', 1)->where('is_released', true)->firstOrFail();
        CourseEnrollment::where('learner_id', $learner->id)->where('course_id', $content->course_id)->firstOrFail();
        app(\App\Services\MobileContentAccess::class)->authorize($learner, $content);

        return app(\App\Services\MobilePreviewService::class)->response($content);
    }

    public function endEngineerSession(Request $request, int $sessionId)
    {
        $teacher = $request->user();

        abort_unless($teacher instanceof User && in_array($teacher->role, ['Teacher', 'STEM Engineer'], true), 403, 'Teacher access is required.');

        $request->validate(['status' => 'nullable|in:completed,partially_completed,cancelled', 'remarks' => 'nullable|string|max:5000', 'delivered_topic' => 'nullable|string|max:255', 'auto_ended' => 'nullable|boolean']);

        $session = ClassContentSession::with('teachingPlanItem')->findOrFail($sessionId);

        if ($session->stem_engineer_id != $teacher->id || $session->institute != $teacher->institute) {
            abort(403, 'This class session is assigned to another STEM Engineer.');
        }

        if ($session->status !== 'in_progress' || !$session->started_at) {
            abort(409, 'Session already completed.');
        }

        $endedAt = now();
        $actualDuration = $session->started_at ? $session->started_at->diffInSeconds($endedAt) : 0;
        $minimumSessionSeconds = 30 * 60;
        $maximumSessionSeconds = 50 * 60;
        $autoEnded = $actualDuration >= $maximumSessionSeconds || ($request->boolean('auto_ended') && $actualDuration >= ($maximumSessionSeconds - 5));
        $status = $autoEnded ? 'partially_completed' : ($request->input('status', 'completed'));

        if ($status !== 'cancelled' && $actualDuration < $minimumSessionSeconds) {
            abort(422, 'Sessions can be ended as completed or partially completed only after at least 30 minutes.');
        }

        $remarks = $request->input('remarks');
        if ($autoEnded) {
            $remarks = trim(($remarks ? $remarks . "\n" : '') . 'System note: Session automatically ended after reaching the 50 minute maximum window.');
        } elseif ($actualDuration > $maximumSessionSeconds) {
            $remarks = trim(($remarks ? $remarks . "\n" : '') . 'System note: Session exceeded the 50 minute maximum window. Duration was capped at 50 minutes for reporting.');
        }

        if ($status === 'completed' && (!$session->teachingPlanItem || $session->teachingPlanItem->status !== 'released')) {
            abort(409, 'Only released Teaching Plan content can be completed.');
        }

        $session->update([
            'ended_at' => $endedAt,
            'end_time' => $endedAt->format('H:i:s'),
            'duration_seconds' => min($actualDuration, $maximumSessionSeconds),
            'status' => $status,
            'delivered_topic' => $request->input('delivered_topic', $session->planned_topic),
            'delivered_content_id' => $session->content_id,
            'remarks' => $remarks,
        ]);

        if ($status === 'completed' && $session->teachingPlanItem) {
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

        return response()->json([
            'success' => true,
            'message' => 'Class session ended successfully.',
            'session_id' => $session->id,
            'status' => $status,
            'duration_seconds' => $session->duration_seconds,
            'celebrate' => !$autoEnded && in_array($status, ['completed', 'partially_completed'], true),
            'celebration_video_url' => !$autoEnded && in_array($status, ['completed', 'partially_completed'], true)
                ? app(\App\Http\Controllers\PageController::class)->randomSessionCompletionVideoUrl(true) : null,
        ]);
    }

    public function learningContent(Request $request)
    {
        $account = $request->user();
        $role = $this->displayRoleFor($account);

        $lessons = collect();

        if ($role === 'Student') {
            $contentIds = $this->studentAvailableContentIds($account);
            $unlockedIds = app(\App\Services\MobileContentAccess::class)->unlockedIds($account);
            $completedIds = LessonProgress::query()
                ->where('student_id', $account->id)
                ->where('is_completed', true)
                ->whereIn('content_id', $contentIds)
                ->pluck('content_id')
                ->map(fn ($id) => (int) $id);

            $lessons = Content::query()
                ->with(['course', 'aiSummary', 'courseContent.sourceTemplateContent.aiSummary'])
                ->whereIn('id', $contentIds)
                ->where('status', 1)
                ->orderBy('course_id')
                ->orderBy('lesson_order')
                ->get()
                ->map(function (Content $content) use ($account, $completedIds, $unlockedIds) {
                    $locked = !$unlockedIds->contains((int) $content->id);
                    $requiresAiQuiz = !$locked
                        && $this->studentContentRequiresAiReview($account, $content)
                        && $this->lessonNeedsAiReview($content, $account->id);

                    return [
                        'id' => $content->id,
                        'content_id' => $content->id,
                        'title' => $content->content_title ?: 'Lesson',
                        'status' => $locked ? 'Locked' : ($completedIds->contains((int) $content->id) ? 'Completed' : 'Available'),
                        'summary' => trim(($content->course?->course_title ?? 'Course') . ' · ' . ($content->description ?: 'Learning content')),
                        'requires_ai_quiz' => $requiresAiQuiz,
                        'ai_summary' => null,
                        'has_ai_summary' => false,
                    ];
                });
        } elseif ($role === 'STEM Engineer') {
            $lessons = $this->legacyPage(TeachingPlanItem::query()
                ->whereHas('plan', fn ($query) => $query->where('institute', $account->institute))
                ->with(['content', 'plan'])
                ->orderBy('sort_order'), 'lessons')
                ->map(function (TeachingPlanItem $item) {
                    return [
                        'id' => $item->id,
                        'content_id' => $item->content_id,
                        'title' => $item->content?->content_title ?: 'Lesson',
                        'status' => $item->status ?: 'Planned',
                        'summary' => trim(($item->plan?->class ?? 'Class n/a') . ($item->plan?->section ? ' · ' . $item->plan->section : '') . ' · ' . ($item->plan?->course?->course_title ?? 'Course')),
                    ];
                });
        }

        return $this->legacyResponse([
            'lessons' => $lessons->values(),
        ]);
    }

    public function engineerStudents(Request $request)
    {
        $teacher = $request->user();

        return $this->legacyResponse([
            'details' => $this->legacyPage(Student::query()
                ->where('institute', $teacher->institute)
                ->orderBy('class')
                ->orderBy('section')
                ->orderBy('name'), 'details')
                ->map(function (Student $student) {
                    return [
                        'id' => $student->id,
                        'title' => $student->name,
                        'subtitle' => trim(($student->student_id ?: 'Student') . ($student->class ? ' · ' . $student->class : '') . ($student->section ? ' · ' . $student->section : '')),
                        'meta' => [
                            'student_id' => $student->student_id,
                            'class' => $student->class,
                            'section' => $student->section,
                            'institute' => $student->institute,
                        ],
                    ];
                })
                ->values(),
            'results' => $this->legacyPage(AssessmentResult::query()
                ->whereHas('student', fn ($query) => $query->where('institute', $teacher->institute))
                ->whereHas('assessment', fn ($q) => $q->where('teacher_id', $teacher->id)->where('institute', $teacher->institute))
                ->with(['student', 'assessment'])
                ->latest('evaluated_at'), 'results')
                ->map(function (AssessmentResult $result) {
                    return [
                        'id' => $result->id,
                        'title' => $result->student?->name ?: 'Result',
                        'subtitle' => trim(($result->assessment?->assessment_title ?? 'Assessment') . ($result->percentage !== null ? ' · ' . $result->percentage . '%' : '')),
                        'meta' => [
                            'assessment' => $result->assessment?->assessment_title,
                            'percentage' => $result->percentage !== null ? (float) $result->percentage : null,
                            'score' => $result->score !== null ? (int) $result->score : null,
                            'total_marks' => $result->total_marks !== null ? (int) $result->total_marks : null,
                            'evaluated_at' => optional($result->evaluated_at)->format('Y-m-d H:i'),
                            'status' => $result->status,
                        ],
                    ];
                })
                ->values(),
            'certificates' => $this->legacyPage(Certificate::query()
                ->whereHas('student', fn ($query) => $query->where('institute', $teacher->institute))
                ->with('student')
                ->latest('issued_date'), 'certificates')
                ->map(function (Certificate $certificate) {
                    return [
                        'id' => $certificate->id,
                        'title' => $certificate->student?->name ?: 'Certificate',
                        'subtitle' => trim(($certificate->final_grade ?: 'Grade n/a') . ($certificate->final_classification ? ' · ' . $certificate->final_classification : '')),
                        'meta' => [
                            'grade' => $certificate->final_grade,
                            'classification' => $certificate->final_classification,
                            'issued_date' => optional($certificate->issued_date)->format('Y-m-d'),
                        ],
                    ];
                })
                ->values(),
        ]);
    }

    public function engineerAssessments(Request $request)
    {
        $teacher = $request->user();

        return $this->legacyResponse([
            'management' => $this->legacyPage(Assessment::query()
                ->where('institute', $teacher->institute)
                ->where('teacher_id', $teacher->id)
                ->orderByDesc('assessment_date'), 'management')
                ->map(function (Assessment $assessment) {
                    return [
                        'id' => $assessment->id,
                        'title' => $assessment->assessment_title,
                        'subtitle' => trim(($assessment->assigned_class ?: 'Class n/a') . ($assessment->assessment_category ? ' · ' . $assessment->assessment_category : '')),
                        'assessment_date' => $assessment->assessment_date ? Carbon::parse($assessment->assessment_date)->format('Y-m-d') : null,
                        'total_marks' => (int) ($assessment->total_marks ?? 0),
                        'duration' => (int) ($assessment->duration ?? 0),
                        'paper_url' => route('assessment.paper', [$assessment->id, 'file']),
                        'preview_url' => route('assessment.paper', [$assessment->id, 'preview']),
                    ];
                })
                ->values(),
            'evaluation' => $this->legacyPage(AssessmentResult::query()
                ->whereHas('assessment', fn ($query) => $query->where('institute', $teacher->institute)->where('teacher_id', $teacher->id))
                ->with(['assessment', 'student'])
                ->latest('evaluated_at'), 'evaluation')
                ->map(function (AssessmentResult $result) {
                    return [
                        'id' => $result->id,
                        'title' => $result->assessment?->assessment_title ?: 'Evaluation',
                        'subtitle' => trim(($result->student?->name ?? 'Student') . ($result->percentage !== null ? ' · ' . $result->percentage . '%' : '')),
                        'result_date' => optional($result->evaluated_at ?? $result->submitted_at ?? $result->created_at)->format('Y-m-d'),
                        'student_name' => $result->student?->name ?? '',
                        'percentage' => $result->percentage !== null ? (float) $result->percentage : null,
                        'score' => $result->score !== null ? (int) $result->score : null,
                        'total_marks' => $result->total_marks !== null ? (int) $result->total_marks : null,
                        'status' => $result->status,
                    ];
                })
                ->values(),
        ]);
    }

    public function engineerAssessmentResult(Request $request, int $resultId)
    {
        $teacher = $request->user();
        abort_unless($teacher instanceof User, 403, 'Teacher access is required.');

        $result = AssessmentResult::query()
            ->with(['assessment', 'student', 'evaluator'])
            ->whereHas('assessment', fn ($query) => $query->where('institute', $teacher->institute)->where('teacher_id', $teacher->id))
            ->findOrFail($resultId);

        $answerFileUrl = $result->answer_file_path
            ? url('/api/workflows/results/'.$result->id.'/document')
            : null;

        return response()->json([
            'success' => true,
            'result' => [
                'id' => $result->id,
                'title' => $result->assessment?->assessment_title ?: 'Result review',
                'assessment_title' => $result->assessment?->assessment_title ?: '',
                'student_name' => $result->student?->name ?? '',
                'student_id' => $result->student?->student_id ?? '',
                'result_date' => optional($result->evaluated_at ?? $result->submitted_at ?? $result->created_at)->format('Y-m-d'),
                'percentage' => $result->percentage !== null ? (float) $result->percentage : null,
                'score' => $result->score !== null ? (int) $result->score : null,
                'total_marks' => $result->total_marks !== null ? (int) $result->total_marks : null,
                'status' => $result->status ?? '',
                'badge' => $result->badge ?? '',
                'answer_text' => $result->answer_text ?? '',
                'feedback' => $result->feedback ?? '',
                'evaluated_by' => $result->evaluator?->name ?? '',
                'evaluated_at' => optional($result->evaluated_at)->format('Y-m-d H:i'),
                'answer_file_url' => $answerFileUrl,
                'assessment_date' => $result->assessment?->assessment_date ? Carbon::parse($result->assessment->assessment_date)->format('Y-m-d') : null,
                'assessment_type' => $result->assessment?->assessment_type ?? '',
                'assessment_category' => $result->assessment?->assessment_category ?? '',
            ],
        ]);
    }

    public function studentAssessments(Request $request)
    {
        $student = $request->user();
        $assignedClass = $this->studentClassName($student);
        $attemptedIds = AssessmentResult::where('student_id', $student->id)
            ->pluck('assessment_id');

        $now = now()->format('H:i:s');
        $page = Assessment::query()
                ->where('institute', $student->institute)
                ->where('status', 1)
                ->where('question_paper_status', 'Approved')
                ->whereNotNull('file_path')
                ->whereDate('assessment_date', '<=', today())
                ->whereRaw("REPLACE(TRIM(assigned_class), '  ', ' ') = ?", [$assignedClass])
                ->whereNotIn('id', $attemptedIds)
                ->where(function ($q) {
                    $q->whereDate('assessment_date', today())->orWhere(function ($q) {
                        $q->where(fn ($q) => $q->whereNull('start_time')->orWhere('start_time', ''))
                            ->where(fn ($q) => $q->whereNull('end_time')->orWhere('end_time', ''));
                    });
                })
                ->where(fn ($q) => $q->whereNull('start_time')->orWhere('start_time', '')->orWhereTime('start_time', '<=', $now))
                ->where(fn ($q) => $q->whereNull('end_time')->orWhere('end_time', '')->orWhereTime('end_time', '>=', $now))
                ->orderByDesc('assessment_date')
                ->orderByDesc('id')->paginate(30);
        return response()->json([
            'pagination' => $this->pageMetadata($page),
            'assessments' => $page->getCollection()
                ->map(function (Assessment $assessment) {
                    return [
                        'id' => $assessment->id,
                        'title' => $assessment->assessment_title,
                        'status' => $assessment->status ?: 'Available',
                        'description' => trim(($assessment->assessment_category ?: 'Assessment') . ($assessment->duration ? ' · ' . $assessment->duration . ' mins' : '')),
                        'can_start' => true,
                    ];
                })
                ->values(),
        ]);
    }

    public function studentComponentMastery(Request $request)
    {
        $student = $request->user();
        abort_unless($student instanceof Student, 403, 'Student access is required.');

        $passedContentIds = AiQuizAttempt::query()
            ->where('student_id', $student->id)
            ->where('attempt_type', self::AI_STUDENT_ATTEMPT_TYPE)
            ->where('status', 'passed')
            ->whereNotNull('content_id')
            ->pluck('content_id')
            ->unique();

        $offers = AiComponentContentProfile::query()
            ->where('is_practical', true)
            ->whereIn('content_id', $passedContentIds)
            ->get()
            ->groupBy('component_key')
            ->map(function (Collection $profiles, string $componentKey) {
                return [
                    'component_key' => $componentKey,
                    'component_label' => $profiles->first()->component_label,
                    'completed_practical_topics' => $profiles->pluck('content_id')->unique()->count(),
                    'content_ids' => $profiles->pluck('content_id')->unique()->values()->all(),
                ];
            })
            ->filter(fn (array $offer) => $offer['completed_practical_topics'] >= 5)
            ->values();

        $keys = $offers->pluck('component_key');
        $assessments = Assessment::query()
            ->where('institute', $student->institute)
            ->where('assessment_category', 'Component Mastery')
            ->whereIn('component_key', $keys)
            ->where('status', 1)
            ->where('question_paper_status', 'Approved')
            ->latest()
            ->get()
            ->unique('component_key')
            ->keyBy('component_key');

        $results = AssessmentResult::query()
            ->where('student_id', $student->id)
            ->whereIn('assessment_id', $assessments->pluck('id'))
            ->latest()
            ->get()
            ->keyBy('assessment_id');

        return response()->json([
            'assessments' => $offers->map(function (array $offer) use ($assessments, $results) {
                $assessment = $assessments->get($offer['component_key']);
                $result = $assessment ? $results->get($assessment->id) : null;

                return [
                    'component_key' => $offer['component_key'],
                    'component_label' => $offer['component_label'],
                    'completed_practical_topics' => $offer['completed_practical_topics'],
                    'assessment_id' => $assessment?->id,
                    'assessment_title' => $assessment?->assessment_title,
                    'status' => $result
                        ? ($result->status === 'Completed'
                            ? ($result->passed ? 'Certificate pending approval' : 'Completed - score below certification grade')
                            : 'Pending AI evaluation')
                        : ($assessment ? 'Ready to take' : 'Eligible - assessment not prepared'),
                    'can_take' => (bool) $assessment && !$result,
                ];
            })->values(),
        ]);
    }

    public function generateStudentComponentMastery(Request $request, string $componentKey, GeminiAiService $ai)
    {
        $student = $request->user();
        abort_unless($student instanceof Student, 403, 'Student access is required.');

        $response = app(\App\Http\Controllers\PageController::class)
            ->generateStudentComponentAssessment($request, $componentKey, $ai, $student);

        if ($response instanceof \Illuminate\Http\RedirectResponse) {
            $error = $response->getSession()?->get('error');
            if ($error) {
                return response()->json(['success' => false, 'message' => $error], 422);
            }
        }

        $assessment = Assessment::query()
            ->where('institute', $student->institute)
            ->where('assessment_category', 'Component Mastery')
            ->where('component_key', $componentKey)
            ->where('assigned_class', $this->studentClassName($student))
            ->latest()
            ->first();

        return response()->json([
            'success' => (bool) $assessment,
            'assessment_id' => $assessment?->id,
            'message' => $assessment ? 'Component mastery assessment is ready.' : 'The assessment could not be prepared right now.',
        ], $assessment ? 200 : 503);
    }

    public function completeStudentLesson(Request $request, int $contentId)
    {
        $student = $request->user();

        abort_unless($student instanceof Student, 403, 'Student access is required.');

        $contentIds = $this->studentAvailableContentIds($student);
        $content = Content::query()
            ->with(['aiSummary', 'courseContent.sourceTemplateContent.aiSummary'])
            ->where('id', $contentId)
            ->where('status', 1)
            ->first();

        if (!$content || !$contentIds->contains((int) $content->id)) {
            abort(403, 'This lesson is not assigned to your class.');
        }

        if ($this->studentContentIsSequenceLocked($student, $content)) {
            abort(403, 'Please clear the previous released content before starting this topic.');
        }

        if ($this->studentContentRequiresAiReview($student, $content) && !$this->generatedAiSummaryForContentRecord($content)) {
            abort(409, 'Training assessment is still being prepared for this lesson. Please try again shortly.');
        }

        if ($this->studentContentRequiresAiReview($student, $content) && $this->lessonNeedsAiReview($content, $student->id)) {
            $this->unlockStudentAiReview($student->id, $content->id);

            return response()->json([
                'success' => true,
                'message' => 'Lesson is ready for the AI quiz.',
                'requires_ai_review' => true,
                'requires_ai_quiz' => true,
                'content_id' => $content->id,
            ]);
        }

        LessonProgress::updateOrCreate(
            [
                'student_id' => $student->id,
                'content_id' => $content->id,
            ],
            [
                'is_completed' => true,
                'completed_at' => now(),
            ]
        );

        return response()->json([
            'success' => true,
            'message' => 'Lesson marked as completed.',
            'requires_ai_review' => false,
            'requires_ai_quiz' => false,
            'content_id' => $content->id,
        ]);
    }

    public function studentAiReview(Request $request, int $contentId)
    {
        $student = $request->user();

        abort_unless($student instanceof Student, 403, 'Student access is required.');

        $content = $this->studentAccessibleContent($student, $contentId);

        if (!$content) {
            abort(403, 'This lesson is not assigned to your class.');
        }

        if ($this->studentContentIsSequenceLocked($student, $content)) {
            abort(403, 'Please clear the previous released content before starting this training assessment.');
        }

        if (!$this->studentContentRequiresAiReview($student, $content)) {
            return response()->json([
                'success' => true,
                'message' => 'Training assessment is not required for this lesson.',
                'requires_ai_review' => false,
                'content_id' => $content->id,
            ]);
        }

        if (!$this->studentAiReviewIsUnlocked($student->id, $content)) {
            abort(403, 'Please mark this topic as complete before starting the training assessment.');
        }

        $summary = $this->generatedAiSummaryForContentRecord($content);
        if (!$summary) {
            abort(409, 'AI quiz is not available for this content yet.');
        }

        $gradeLevel = $this->studentGradeName($student);
        $quiz = $this->studentQuizForContent($content, $summary, $gradeLevel);
        $latestAttempt = AiQuizAttempt::where('ai_quiz_id', $quiz->id)
            ->where('student_id', $student->id)
            ->where('attempt_type', self::AI_STUDENT_ATTEMPT_TYPE)
            ->latest()
            ->first();

        return response()->json([
            'success' => true,
            'content' => [
                'id' => $content->id,
                'title' => $content->content_title,
                'summary' => trim(($content->course?->course_title ?? 'Course') . ' · ' . ($content->description ?: 'Learning content')),
                'ai_summary' => null,
                'has_ai_summary' => false,
            ],
            'quiz' => [
                'id' => $quiz->id,
                'title' => $quiz->title,
                'instructions' => $quiz->instructions,
                'passing_marks' => (int) $quiz->passing_marks,
                'total_marks' => (int) $quiz->total_marks,
                'questions' => $quiz->questions->map(function (AiQuizQuestion $question) {
                    return [
                        'id' => $question->id,
                        'order' => (int) $question->question_order,
                        'text' => $question->question_text,
                        'options' => array_values($question->options ?? []),
                        'marks' => (int) $question->marks,
                    ];
                })->values(),
            ],
            'latest_attempt' => $latestAttempt ? [
                'id' => $latestAttempt->id,
                'status' => $latestAttempt->status,
                'percentage' => (float) ($latestAttempt->percentage ?? 0),
                'score' => (int) ($latestAttempt->score ?? 0),
            ] : null,
            'can_start' => true,
            'already_passed' => $latestAttempt?->status === 'passed',
        ]);
    }

    public function submitStudentAiReview(Request $request, int $contentId)
    {
        $student = $request->user();

        abort_unless($student instanceof Student, 403, 'Student access is required.');

        $content = $this->studentAccessibleContent($student, $contentId);

        if (!$content) {
            abort(403, 'This lesson is not assigned to your class.');
        }

        if ($this->studentContentIsSequenceLocked($student, $content)) {
            abort(403, 'Please clear the previous released content before submitting this training assessment.');
        }

        if (!$this->studentContentRequiresAiReview($student, $content)) {
            abort(409, 'Training assessment is not required for this lesson.');
        }

        if (!$this->studentAiReviewIsUnlocked($student->id, $content)) {
            abort(403, 'Please mark this topic as complete before submitting the training assessment.');
        }

        $summary = $this->generatedAiSummaryForContentRecord($content);
        if (!$summary) {
            abort(409, 'AI quiz is not available for this content yet.');
        }

        $gradeLevel = $this->studentGradeName($student);
        $quiz = $this->studentQuizForContent($content, $summary, $gradeLevel);
        $questions = $quiz->questions()->orderBy('question_order')->get();

        if (AiQuizAttempt::where('ai_quiz_id', $quiz->id)
            ->where('student_id', $student->id)
            ->where('attempt_type', self::AI_STUDENT_ATTEMPT_TYPE)
            ->where('status', 'passed')
            ->exists()) {
            return response()->json([
                'success' => true,
                'message' => 'Training assessment already cleared.',
                'status' => 'passed',
                'already_passed' => true,
            ]);
        }

        $validated = $request->validate([
            'answers' => ['required', 'array'],
            'answers.*' => ['nullable', 'string', 'max:500'],
        ]);

        $answers = collect($validated['answers'])
            ->map(fn ($answer) => trim((string) $answer));

        if ($answers->filter()->isEmpty()) {
            abort(422, 'Please answer at least one question before submitting the AI quiz.');
        }

        $attempt = AiQuizAttempt::create([
            'ai_quiz_id' => $quiz->id,
            'content_id' => $this->aiQuizOwnerContent($content)->id,
            'attempt_type' => self::AI_STUDENT_ATTEMPT_TYPE,
            'grade_level' => $gradeLevel,
            'student_id' => $student->id,
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

        if ($status === 'passed') {
            LessonProgress::updateOrCreate(
                [
                    'student_id' => $student->id,
                    'content_id' => $content->id,
                ],
                [
                    'is_completed' => true,
                    'completed_at' => now(),
                ]
            );

            Cache::forget($this->studentAiReviewUnlockKey($student->id, $content->id));
        }

        return response()->json([
            'success' => true,
            'message' => $status === 'passed'
                ? 'AI quiz passed. Lesson marked as completed.'
                : 'AI quiz score is below ' . $passingPercentage . '%. Please review the content and try again.',
            'status' => $status,
            'percentage' => $percentage,
            'score' => $evaluation['score'] ?? null,
            'total_marks' => $evaluation['total_marks'] ?? null,
            'attempt_id' => $attempt->id,
        ]);
    }

    public function startStudentAssessment(Request $request, int $assessmentId)
    {
        $student = $request->user();

        abort_unless($student instanceof Student, 403, 'Student access is required.');

        $assessment = Assessment::query()
            ->where('id', $assessmentId)
            ->where('status', 1)
            ->where('question_paper_status', 'Approved')
            ->whereNotNull('file_path')
            ->whereDate('assessment_date', '<=', today())
            ->first();

        if (!$assessment) {
            abort(404, 'Assessment not found.');
        }

        if (!$this->studentCanAccessAssessment($student, $assessment)) {
            abort(403, 'This assessment is not assigned to your class.');
        }

        if (!$this->assessmentWindowIsOpen($assessment)) {
            abort(409, 'This assessment is not open yet.');
        }

        $session = DB::transaction(function () use ($student, $assessment) {
            // Serialize starts for this student so retries cannot create extra timers.
            Student::whereKey($student->id)->lockForUpdate()->firstOrFail();
            abort_if(AssessmentResult::where('student_id', $student->id)
                ->where('assessment_id', $assessment->id)->exists(), 409,
                'You have already submitted this assessment.');

            return AssessmentSession::query()
                ->where('assessment_id', $assessment->id)
                ->where('user_id', $student->id)
                ->where('user_type', 'Student')
                ->where('status', 'Started')
                ->lockForUpdate()->first() ?? AssessmentSession::create([
                    'assessment_id' => $assessment->id,
                    'user_id' => $student->id,
                    'user_type' => 'Student',
                    'started_at' => now(),
                    'status' => 'Started',
                ]);
        });
        $service = app(\App\Services\MobileAssessmentService::class);
        if ($service->deadline($session)->lte(now())) {
            $service->submit($session, null, true);
            abort(409, 'The time for this assessment has ended. Your saved answers were submitted.');
        }

        return response()->json([
            'success' => true,
            'message' => 'Assessment session started.',
            'session_id' => $session->id,
            'assessment_id' => $assessment->id,
            'title' => $assessment->assessment_title,
            'paper_url' => '/api/workflows/assessments/'.$assessment->id.'/document',
            'preview_url' => '/api/workflows/assessments/'.$assessment->id.'/document',
            'server_time' => now()->toIso8601String(),
            'deadline' => app(\App\Services\MobileAssessmentService::class)->deadline($session)->toIso8601String(),
            'duration' => (int) ($assessment->duration ?? 0),
            'total_marks' => (int) ($assessment->total_marks ?? 0),
            'assessment_date' => $assessment->assessment_date ? Carbon::parse($assessment->assessment_date)->format('Y-m-d') : null,
        ]);
    }

    public function submitStudentAssessment(Request $request, int $sessionId)
    {
        $student = $request->user();

        abort_unless($student instanceof Student, 403, 'Student access is required.');

        $session = AssessmentSession::query()
            ->where('id', $sessionId)
            ->where('user_id', $student->id)
            ->where('user_type', 'Student')
            ->firstOrFail();

        $assessment = Assessment::query()
            ->where('id', $session->assessment_id)
            ->where('institute', $student->institute)
            ->where('status', 1)
            ->where('question_paper_status', 'Approved')
            ->whereNotNull('file_path')
            ->first();

        if (!$assessment || !$this->studentCanAccessAssessment($student, $assessment)) {
            abort(403, 'This assessment is not assigned to your class or is not approved yet.');
        }

        $validated = $request->validate([
            'answer_text' => ['nullable', 'string', 'max:200000'],
            'auto_submitted' => ['nullable', 'boolean'],
        ]);

        app(\App\Services\MobileAssessmentService::class)->submit($session, $validated['answer_text'] ?? null, $request->boolean('auto_submitted'));

        return response()->json([
            'success' => true,
            'message' => 'Assessment submitted successfully.',
            'session_id' => $session->id,
            'assessment_id' => $assessment->id,
            'assessment_title' => $assessment->assessment_title,
            'result_status' => 'Pending Review',
            'score' => 0,
            'total_marks' => (int) ($assessment->total_marks ?? 1),
        ]);
    }

    public function studentAchievements(Request $request)
    {
        $student = $request->user();

        return $this->legacyResponse([
            'achievements' => $this->legacyPage(StudentAchievement::query()
                ->where('student_id', $student->id)
                ->latest('achievement_date'), 'achievements')
                ->map(function (StudentAchievement $achievement) {
                    return [
                        'id' => $achievement->id,
                        'title' => $achievement->title,
                        'subtitle' => trim(($achievement->achievement_type ?: 'Achievement') . ($achievement->position ? ' · ' . $achievement->position : '')),
                        'achievement_type' => $achievement->achievement_type ?? '',
                        'organizer' => $achievement->organizer ?? '',
                        'description' => $achievement->description ?? '',
                        'achievement_date' => optional($achievement->achievement_date)->format('Y-m-d'),
                        'position' => $achievement->position ?? '',
                        'verification_status' => $achievement->verification_status ?? '',
                        'certificate_file' => $achievement->certificate_file ?? '',
                        'icon' => $this->achievementIcon($achievement->achievement_type),
                    ];
                })
                ->values(),
        ]);
    }

    public function studentAssessmentResults(Request $request)
    {
        $student = $request->user();

        abort_unless($student instanceof Student, 403, 'Student access is required.');

        $page = AssessmentResult::query()
                ->where('student_id', $student->id)
                ->with(['assessment', 'evaluator'])
                ->latest('evaluated_at')
                ->latest('created_at')
                ->orderByDesc('id')->paginate(30);
        return response()->json([
            'pagination' => $this->pageMetadata($page),
            'results' => $page->getCollection()
                ->map(function (AssessmentResult $result) {
                    return [
                        'id' => $result->id,
                        'title' => $result->assessment?->assessment_title ?: 'Assessment result',
                        'subtitle' => trim(($result->status ?: 'Pending Review') . ($result->percentage !== null ? ' · ' . $result->percentage . '%' : '')),
                        'assessment_title' => $result->assessment?->assessment_title ?: '',
                        'result_date' => optional($result->evaluated_at ?? $result->submitted_at ?? $result->created_at)->format('Y-m-d'),
                        'percentage' => $result->percentage !== null ? (float) $result->percentage : null,
                        'score' => $result->score !== null ? (int) $result->score : null,
                        'total_marks' => $result->total_marks !== null ? (int) $result->total_marks : null,
                        'status' => $result->status ?? '',
                        'badge' => $result->badge ?? '',
                        'feedback' => $result->feedback ?? '',
                        'evaluated_by' => $result->evaluator?->name ?? '',
                        'evaluated_at' => optional($result->evaluated_at)->format('Y-m-d H:i'),
                    ];
                })
                ->values(),
        ]);
    }

    private function requireAdmin(Request $request): User
    {
        $account = $request->user();

        abort_unless(
            $account instanceof User && in_array($account->role, ['Admin', 'InstituteAdmin'], true),
            403,
            'Admin access is required.'
        );

        return $account;
    }

    private function isHybridLearnerCourse(Course|string|null $courseOrAvailability): bool
    {
        $availability = $courseOrAvailability instanceof Course
            ? $courseOrAvailability->availability_type
            : $courseOrAvailability;

        return in_array($availability, ['Independent', 'Both'], true);
    }

    private function ensureHybridLearnerCourseManagementAllowed(User $account, Course|string|null $courseOrAvailability): void
    {
        abort_if(
            $account->role !== 'Admin' && $this->isHybridLearnerCourse($courseOrAvailability),
            403,
            'Only Super Admin can manage Hybrid Learner courses.'
        );
    }

    private function requireRole(Request $request, array $roles): User
    {
        $account = $request->user();
        abort_unless($account instanceof User && in_array($account->role, $roles, true), 403, 'This account cannot access the requested workspace.');

        return $account;
    }

    private function studentAvailableContentIds(Student $student): Collection
    {
        return app(\App\Services\MobileContentAccess::class)->availableIds($student);
    }

    private function studentClassName(Student $student): string
    {
        return preg_replace('/\s+/', ' ', trim($student->class . ' ' . $student->section));
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
        Cache::put($this->studentAiReviewUnlockKey($studentId, $contentId), true, now()->addDay());
    }

    private function studentAiReviewIsUnlocked(int $studentId, Content $content): bool
    {
        return Cache::has($this->studentAiReviewUnlockKey($studentId, $content->id));
    }

    private function studentAiReviewUnlockKey(int $studentId, int $contentId): string
    {
        return 'student_ai_review_unlock_' . $studentId . '_' . $contentId;
    }

    private function lessonNeedsAiReview(Content $content, int $studentId): bool
    {
        $student = Student::find($studentId);
        $summary = $this->generatedAiSummaryForContentRecord($content);

        if (!$summary || !$student) {
            return false;
        }

        $quiz = $this->studentQuizForContent($content, $summary, $this->studentGradeName($student));

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
                            ->whereRaw("REPLACE(TRIM(class), '  ', ' ') = ?", [$assignedClass])
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
            if ($date && Carbon::parse($date)->toDateString() >= $startDate) {
                return true;
            }
        }

        return false;
    }

    private function planAiTrainingStartDate(TeachingPlan $plan): ?string
    {
        return $plan->ai_training_start_date
            ? Carbon::parse($plan->ai_training_start_date)->toDateString()
            : null;
    }

    private function teachingPlanItemBypassesAiPrepForTeacher(
        TeachingPlanItem $item,
        User $teacher
    ): bool {
        if ($item->week?->release_reason === 'lagged_content') {
            return true;
        }

        return ClassContentSession::query()
            ->where('teaching_plan_item_id', $item->id)
            ->where('stem_engineer_id', $teacher->id)
            ->where('institute', $teacher->institute)
            ->whereIn('status', ['partially_completed', 'cancelled'])
            ->exists();
    }

    private function teacherNeedsAiPrep(
        Content $content,
        int $teacherId,
        ?string $gradeLevel = null
    ): bool {
        $summary = $this->generatedAiSummaryForContentRecord($content);
        if (!$summary) {
            return false;
        }

        $quiz = $this->aiQuizForContent($content, $summary, 'teacher', $gradeLevel);

        return !AiQuizAttempt::query()
            ->where('ai_quiz_id', $quiz->id)
            ->where('teacher_id', $teacherId)
            ->where('attempt_type', 'teacher_prep')
            ->where('status', 'passed')
            ->exists();
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

    private function generatedAiSummaryForContent(int $contentId): ?AiContentSummary
    {
        return AiContentSummary::where('content_id', $contentId)
            ->where('status', 'generated')
            ->first();
    }

    private function aiQuizOwnerContent(Content $content): Content
    {
        $sourceContent = $content->courseContent?->sourceTemplateContent;

        if ($sourceContent && ($sourceContent->aiSummary || $sourceContent->hasAiPdfMaterial())) {
            return $sourceContent;
        }

        return $content;
    }

    private function studentGradeName(Student $student): ?string
    {
        return $this->gradeLevelFromClass($student->class);
    }

    private function gradeLevelFromClass(?string $class): ?string
    {
        $class = preg_replace('/\s+/', ' ', trim((string) $class));

        return $class !== '' ? $class : null;
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

        return $points->take(5)->map(function ($point) use ($points, $genericDistractors) {
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

    private function assessmentWindowIsOpen(Assessment $assessment): bool
    {
        if ($assessment->assessment_date) {
            $assessmentDate = Carbon::parse($assessment->assessment_date)->toDateString();
            $today = today()->toDateString();

            if ($assessmentDate > $today) {
                return false;
            }

            if (($assessment->start_time || $assessment->end_time) && $assessmentDate < $today) {
                return false;
            }
        }

        $now = now()->format('H:i:s');

        return !($assessment->start_time && $now < $assessment->start_time)
            && !($assessment->end_time && $now > $assessment->end_time);
    }

    private function studentCanAccessAssessment(Student $student, Assessment $assessment): bool
    {
        return Assessment::query()
            ->where('id', $assessment->id)
            ->where('institute', $student->institute)
            ->where('status', 1)
            ->where('question_paper_status', 'Approved')
            ->whereNotNull('file_path')
            ->whereDate('assessment_date', '<=', today())
            ->whereRaw("REPLACE(TRIM(assigned_class), '  ', ' ') = ?", [$this->studentClassName($student)])
            ->exists();
    }

    private function ensureAdminInstituteAccess(User $account, ?string $institute): void
    {
        if ($account->role === 'InstituteAdmin') {
            abort_unless(
                $institute && $institute === $account->institute,
                403,
                'This item belongs to another institute.'
            );
        }
    }

    private function phoneHint(string $phone): string
    {
        $digits = preg_replace('/\D+/', '', $phone);
        if (strlen($digits) <= 4) {
            return '****';
        }

        return str_repeat('*', max(0, strlen($digits) - 4)) . substr($digits, -4);
    }

    private function emailHint(string $email): string
    {
        [$local, $domain] = array_pad(explode('@', $email, 2), 2, '');
        if ($local === '' || $domain === '') {
            return 'your registered guardian email';
        }

        return substr($local, 0, 1) . str_repeat('*', max(1, strlen($local) - 2)) . substr($local, -1) . '@' . $domain;
    }

    private function publicStorageUrl(?string $path): ?string
    {
        if (blank($path)) {
            return null;
        }

        if (Str::startsWith($path, ['http://', 'https://'])) {
            return $path;
        }

        if (Str::startsWith($path, ['/storage/', 'storage/'])) {
            return URL::to('/' . ltrim($path, '/'));
        }

        return Storage::disk('public')->url(ltrim($path, '/'));
    }

    private function resolveAccount(string $email, string $password, string $role): ?array
    {
        if ($role === 'Hybrid Learner') {
            $learner = IndependentLearner::query()
                ->where('email', $email)
                ->where('status', true)
                ->get()
                ->first(function (IndependentLearner $learner) use ($password) {
                    return $this->passwordMatches($password, (string) $learner->password);
                });

            if (!$learner) {
                return null;
            }

            return [
                $learner,
                'Hybrid Learner',
                '',
                null,
                $learner->id,
                $learner->name,
            ];
        }

        if ($role === 'Student') {
            $student = Student::query()
                ->whereRaw('LOWER(TRIM(student_id)) = ?', [strtolower($email)])
                ->where('status', 1)
                ->get()
                ->first(function (Student $student) use ($password) {
                    return $this->passwordMatches($password, (string) $student->password);
                });

            if (!$student) {
                return null;
            }

            return [
                $student,
                'Student',
                $student->institute,
                $student->profile_image ?? null,
                $student->id,
                $student->name,
            ];
        }

        if (in_array($role, ['Manager', 'Principal'], true)) {
            $user = User::query()
                ->where('email', $email)
                ->where('role', $role)
                ->where('status', 1)
                ->get()
                ->first(function (User $user) use ($password) {
                    return $this->passwordMatches($password, (string) $user->password);
                });

            if (!$user) {
                return null;
            }

            return [
                $user,
                $role,
                $user->institute,
                $user->profile_image ?? null,
                $user->id,
                $user->name,
            ];
        }

        if ($role === 'STEM Engineer') {
            $user = User::query()
                ->where('email', $email)
                ->whereIn('role', ['Teacher', 'STEM Engineer'])
                ->where('status', 1)
                ->get()
                ->first(function (User $user) use ($password) {
                    return $this->passwordMatches($password, (string) $user->password);
                });

            if (!$user) {
                return null;
            }

            return [
                $user,
                'STEM Engineer',
                $user->institute,
                $user->profile_image ?? null,
                $user->id,
                $user->name,
            ];
        }

        $user = User::query()
            ->where('email', $email)
            ->whereIn('role', ['Admin', 'InstituteAdmin'])
            ->where('status', 1)
            ->get()
            ->first(function (User $user) use ($password) {
                return $this->passwordMatches($password, (string) $user->password);
            });

        if (!$user) {
            return null;
        }

        return [
            $user,
            $user->role === 'InstituteAdmin' ? 'InstituteAdmin' : 'Admin',
            $user->institute,
            $user->profile_image ?? null,
            $user->id,
            $user->name,
        ];
    }

    private function passwordMatches(string $plainPassword, string $storedPassword): bool
    {
        return Hash::check($plainPassword, $storedPassword) || hash_equals($storedPassword, $plainPassword);
    }

    private function displayRoleFor($account): string
    {
        if ($account instanceof Student) {
            return 'Student';
        }

        if ($account instanceof IndependentLearner) {
            return 'Hybrid Learner';
        }

        if ($account instanceof User) {
            return in_array($account->role, ['Teacher', 'STEM Engineer'], true)
                ? 'STEM Engineer'
                : ($account->role === 'InstituteAdmin' ? 'InstituteAdmin' : $account->role);
        }

        return 'User';
    }

    private function sendPasswordResetLink(User $user)
    {
        if (!$user->email) {
            return response()->json([
                'success' => false,
                'message' => 'Your account does not have a login email address.',
            ], 422);
        }

        $token = Str::random(64);
        $expiresAt = now()->addMinutes(30);

        DB::transaction(function () use ($user, $token, $expiresAt) {
            PendingPasswordChange::where('user_id', $user->id)
                ->whereNull('confirmed_at')
                ->delete();

            PendingPasswordChange::create([
                'user_id' => $user->id,
                'email' => $user->email,
                'token_hash' => hash('sha256', $token),
                'purpose' => 'reset',
                'new_password' => null,
                'expires_at' => $expiresAt,
            ]);
        });

        try {
            Mail::send('emails.password-reset-request', [
                'user' => $user,
                'resetUrl' => route('password-change.confirm', $token),
                'expiresAt' => $expiresAt->format('d M Y, h:i A'),
            ], function ($message) use ($user) {
                $message->to($user->email)
                    ->subject('Reset your InnovatEdge LMS password');
            });
        } catch (\Throwable $exception) {
            report($exception);

            PendingPasswordChange::where('user_id', $user->id)
                ->where('token_hash', hash('sha256', $token))
                ->delete();

            return response()->json([
                'success' => false,
                'message' => 'Password reset email could not be sent. Please check mail configuration and try again.',
            ], 500);
        }

        return response()->json([
            'success' => true,
            'message' => 'If an active account exists for that email, we have sent a password reset link.',
        ]);
    }

    private function sendPasswordChangeConfirmation(User $user, string $newPassword)
    {
        if (!$user->email) {
            return response()->json([
                'success' => false,
                'message' => 'Your account does not have a login email address.',
            ], 422);
        }

        $token = Str::random(64);
        $expiresAt = now()->addMinutes(30);

        DB::transaction(function () use ($user, $newPassword, $token, $expiresAt) {
            PendingPasswordChange::where('user_id', $user->id)
                ->whereNull('confirmed_at')
                ->delete();

            PendingPasswordChange::create([
                'user_id' => $user->id,
                'email' => $user->email,
                'token_hash' => hash('sha256', $token),
                'purpose' => 'change',
                'new_password' => Hash::make($newPassword),
                'expires_at' => $expiresAt,
            ]);
        });

        try {
            Mail::send('emails.password-change-confirmation', [
                'user' => $user,
                'confirmationUrl' => route('password-change.confirm', $token),
                'expiresAt' => $expiresAt->format('d M Y, h:i A'),
            ], function ($message) use ($user) {
                $message->to($user->email)
                    ->subject('Confirm your InnovatEdge LMS password change');
            });
        } catch (\Throwable $exception) {
            report($exception);

            PendingPasswordChange::where('user_id', $user->id)
                ->where('token_hash', hash('sha256', $token))
                ->delete();

            return response()->json([
                'success' => false,
                'message' => 'Password confirmation email could not be sent. Please check mail configuration and try again.',
            ], 500);
        }

        return response()->json([
            'success' => true,
            'message' => 'A confirmation email has been sent to your login email. Click "Yes, it is me" to complete the password change.',
        ]);
    }

    private function buildDashboardSummary($account, string $role): array
    {
        if ($role === 'InstituteAdmin') {
            return ['headline' => 'Institute overview', 'description' => $account->institute, 'metrics' => [
                ['label' => 'Students', 'value' => (string) Student::where('institute', $account->institute)->count()],
                ['label' => 'Classes', 'value' => (string) SchoolClass::where('institute', $account->institute)->count()],
                ['label' => 'Teachers', 'value' => (string) User::where('institute', $account->institute)->whereIn('role', ['Teacher', 'STEM Engineer'])->count()],
                ['label' => 'Assessments', 'value' => (string) Assessment::where('institute', $account->institute)->count()],
            ]];
        }
        if ($role === 'Admin') {
            return [
                'headline' => 'Live admin overview',
                'description' => 'Summary data from institutes, users, classes and assessments.',
                'metrics' => [
                    ['label' => 'Students', 'value' => (string) Student::count()],
                    ['label' => 'Classes', 'value' => (string) SchoolClass::count()],
                    ['label' => 'Teachers', 'value' => (string) User::whereIn('role', ['Teacher', 'STEM Engineer'])->count()],
                    ['label' => 'Assessments', 'value' => (string) Assessment::count()],
                ],
            ];
        }

        if (in_array($role, ['Manager', 'Principal'], true)) {
            return [
                'headline' => $role . ' reporting workspace',
                'description' => $role === 'Principal' ? ($account->institute ?: 'Assigned institute') : 'All institutions',
                'metrics' => [
                    ['label' => 'Session reports', 'value' => 'Daily / Weekly / Monthly'],
                    ['label' => $role === 'Manager' ? 'Engineer performance' : 'Student performance', 'value' => 'Available'],
                    ['label' => 'AI report PDFs', 'value' => 'Available'],
                ],
            ];
        }

        if ($role === 'STEM Engineer') {
            $web = app(\App\Services\MobileWebContext::class)->run(request(),
                fn () => app(\App\Http\Controllers\PageController::class)->teacherDashboard())->getData();
            $labels = ['assignedClasses' => 'My classes', 'activeClasses' => 'Active classes',
                'contentCount' => 'Released content', 'assessmentCount' => 'Assessments',
                'monthlyAssessmentCount' => 'Monthly assessments', 'annualAssessmentCount' => 'Annual assessments',
                'totalStudents' => 'Students', 'pendingEvaluationCount' => 'Pending evaluations',
                'todaySessionCount' => 'Sessions today', 'unfinishedSessionCount' => 'Unfinished sessions'];
            return [
                'headline' => 'Your teaching workspace',
                'description' => 'Current live view of classes, sessions and learning content.',
                'metrics' => collect($labels)->map(fn ($label, $key) => ['label' => $label, 'value' => (string) $web[$key]])->values(),
                'class_roster' => $web['classes']->map(fn ($class) => [
                    'id' => $class->id, 'title' => trim($class->class_name.' '.$class->section),
                    'students' => $web['classStudentCounts']->get(trim($class->class_name.' '.$class->section), 0),
                    'status' => $class->status ? 'Active' : 'Inactive',
                ])->values(),
            ];
        }

        if ($role === 'Hybrid Learner' && $account instanceof IndependentLearner) {
            return [
                'headline' => 'Your Hybrid Learner workspace',
                'description' => 'Browse independent courses, continue enrollments and view certificates.',
                'metrics' => [
                    ['label' => 'Available courses', 'value' => (string) Course::where('is_active', 1)->whereIn('availability_type', ['Independent', 'Both'])->count()],
                    ['label' => 'My enrollments', 'value' => (string) CourseEnrollment::where('learner_id', $account->id)->count()],
                    ['label' => 'Completed', 'value' => (string) CourseEnrollment::where('learner_id', $account->id)->where('is_completed', true)->count()],
                    ['label' => 'Certificates', 'value' => (string) Certificate::where('independent_learner_id', $account->id)->where('certificate_type', 'Independent')->count()],
                ],
            ];
        }

        $web = app(\App\Services\MobileWebContext::class)->run(request(),
            fn () => app(\App\Http\Controllers\PageController::class)->studentDashboard())->getData();
        return [
            'headline' => 'Your learning dashboard',
            'description' => 'Current live progress across released content, assessments and achievements.',
            'metrics' => [
                ['label' => 'Released content', 'value' => (string) app(\App\Services\MobileContentAccess::class)->availableIds($account)->count()],
                ['label' => 'Assessments', 'value' => (string) $web['totalAssessmentCount']],
                ['label' => 'Completed assessments', 'value' => (string) $web['completedAssessmentCount']],
                ['label' => 'Pending assessments', 'value' => (string) $web['pendingAssessmentCount']],
                ['label' => 'Average score', 'value' => $web['averagePercentage'].'%'],
                ['label' => 'Assessment progress', 'value' => $web['assessmentProgress'].'%'],
                ['label' => 'Badges', 'value' => (string) $web['badgeCount']],
                ['label' => 'Certificates', 'value' => (string) Certificate::where('student_id', $account->id)->count()],
                ['label' => 'Achievements', 'value' => (string) StudentAchievement::where('student_id', $account->id)->count()],
            ],
            'upcoming_assessments' => $web['upcomingAssessments']->map(fn ($a) => $a->only(['id', 'assessment_title', 'assessment_date', 'start_time', 'end_time']))->values(),
        ];
    }

    private function adminManagementItems($account, string $role): array
    {
        $scope = fn ($query) => $query->when($role === 'InstituteAdmin', fn ($q) => $q->where('institute', $account->institute));
        $items = [
            ['title' => 'STEM Engineer Management', 'subtitle' => $scope(User::whereIn('role', ['Teacher', 'STEM Engineer']))->count() . ' engineers available'],
            ['title' => 'Student Management', 'subtitle' => $scope(Student::query())->count() . ' students available'],
            ['title' => 'Class Management', 'subtitle' => $scope(SchoolClass::query())->count() . ' classes available'],
            ['title' => 'Course Management', 'subtitle' => $scope(Course::query())
                ->when($role === 'InstituteAdmin', fn ($q) => $q->where('availability_type', 'Institute'))
                ->count() . ' courses available'],
            ['title' => 'Teaching Plans', 'subtitle' => $scope(TeachingPlan::query())->count() . ' plans available'],
        ];

        if ($role === 'Admin') {
            array_unshift($items, [
                'title' => 'Institute Management',
                'subtitle' => Institute::count() . ' institutes available',
            ]);
        }

        return $items;
    }

    private function adminReportItems(): array
    {
        return [
            ['title' => 'Daily Session Report', 'subtitle' => 'Review sessions for a selected date'],
            ['title' => 'Weekly Session Report', 'subtitle' => 'Review sessions across a date range'],
            ['title' => 'Weekly Student AI Review', 'subtitle' => 'Review student AI review performance'],
            ['title' => 'Weekly Student Performance', 'subtitle' => 'Review weekly student progress'],
            ['title' => 'Monthly Student Performance', 'subtitle' => 'Review monthly student progress'],
            ['title' => 'Weekly STEM Engineer Prep', 'subtitle' => 'Review engineer prep readiness'],
            ['title' => 'Weekly STEM Engineer Performance', 'subtitle' => 'Review weekly engineer performance'],
            ['title' => 'Monthly STEM Engineer Performance', 'subtitle' => 'Review monthly engineer performance'],
        ];
    }

    private function adminApprovalItems(User $account): array
    {
        // Queue totals must obey the same institute boundary as their detail pages.
        if ($account->role === 'InstituteAdmin') {
            return collect(['Question Paper Approval','Certificate Approval','My Space - STEM Engineers','My Space - Students','Achievements - STEM Engineers','Achievements - Students'])
                ->map(fn ($title) => ['title' => $title, 'subtitle' => 'Review institute submissions'])->all();
        }
        return [
            ['title' => 'Question Paper Approval', 'subtitle' => Assessment::whereRaw('LOWER(question_paper_status) IN (?, ?)', ['pending', 'pending approval'])->count() . ' pending items'],
            ['title' => 'Certificate Approval', 'subtitle' => Certificate::where('status', 'Pending')->count() . ' pending items'],
            ['title' => 'My Space - STEM Engineers', 'subtitle' => MySpace::where('created_by_type', 'Teacher')->count() . ' submissions'],
            ['title' => 'My Space - Students', 'subtitle' => MySpace::where('created_by_type', 'Student')->count() . ' submissions'],
            ['title' => 'Achievements - STEM Engineers', 'subtitle' => TeacherAchievement::count() . ' submissions'],
            ['title' => 'Achievements - Students', 'subtitle' => StudentAchievement::count() . ' submissions'],
        ];
    }

    private function adminMonitoringItems($account, string $role): array
    {
        $items = [];

        if ($role === 'Admin') {
            $items[] = [
                'title' => 'Learning Content Monitoring',
                'subtitle' => 'Track how long STEM Engineers and students access learning content.',
            ];
        }

        $items[] = [
            'title' => 'Assessment Monitoring',
            'subtitle' => 'Monitor assessments, status, and assessment activity.',
        ];

        $items[] = [
            'title' => 'Assessment Review Monitoring',
            'subtitle' => 'Monitor manual assessment review and evaluation status.',
        ];

        return $items;
    }

    private function mobilePreviewPayload(Content $content, string $routeName, array $parameters): \Illuminate\Http\JsonResponse
    {
        return response()->json(app(\App\Services\MobilePreviewService::class)->payload($content, $routeName, $parameters));
    }

    private function engineerPrepGrade(Request $request, User $teacher, Content $content): ?string
    {
        $items = TeachingPlanItem::with('plan')->where('content_id', $content->id)
            ->whereIn('status', ['released', 'completed'])
            ->whereHas('plan', fn ($q) => $q->where('is_template', false)->where('institute', $teacher->institute)->whereIn('status', ['active', 'completed']))
            ->when($request->filled('item_id'), fn ($q) => $q->whereKey($request->integer('item_id')))->get();
        if ($request->filled('grade')) {
            $grade = $this->gradeLevelFromClass($request->string('grade')->toString());
            $items = $items->filter(fn ($item) => $this->gradeLevelFromClass($item->plan?->class) === $grade);
        }
        abort_if($items->isEmpty(), 403, 'This lesson has no released teaching plan for your institute.');
        return $this->gradeLevelFromClass($items->first()->plan?->class);
    }

    private function mobileTeachingPlanPayload(TeachingPlan $plan): array
    {
        $account = request()->user();
        $plan->loadMissing([
            'course',
            'weeks' => fn ($query) => $query->withCount('items')->orderBy('week_number'),
            'course.courseContents.content',
        ]);
        $accessibleInstituteNames = $account instanceof User && $account->role === 'Admin'
            ? Institute::query()->where('status', 1)->pluck('institute_name')
            : collect([$plan->institute])->filter();
        $aiTrainingReleaseDates = TeachingPlanWeek::whereHas('plan', function ($query) use ($accessibleInstituteNames) {
                $query->where('is_template', false)
                    ->whereIn('institute', $accessibleInstituteNames)
                    ->whereIn('status', ['active', 'completed']);
            })
            ->where(fn ($query) => $query->whereNotNull('release_date')->orWhereNotNull('week_start_date'))
            ->get(['release_date', 'week_start_date'])
            ->flatMap(fn ($week) => [
                $week->release_date ? Carbon::parse($week->release_date)->toDateString() : null,
                $week->week_start_date ? Carbon::parse($week->week_start_date)->toDateString() : null,
            ])
            ->filter()
            ->unique()
            ->sort()
            ->values();

        return [
            'id' => $plan->id,
            'title' => $plan->is_template
                ? ($plan->title ?: 'Teaching Plan Template')
                : trim(($plan->class ?: 'Class n/a') . ($plan->section ? ' · ' . $plan->section : '')),
            'plan_title' => $plan->title,
            'subtitle' => trim(($plan->course?->course_title ?: 'Course') . ' · ' . ($plan->institute ?: 'Template / institute n/a')),
            'status' => $plan->status ?: 'inactive',
            'is_template' => (bool) $plan->is_template,
            'institute' => $plan->institute,
            'course_id' => $plan->course_id,
            'course_title' => $plan->course?->course_title,
            'class' => $plan->class,
            'section' => $plan->section,
            'start_date' => filled($plan->start_date) ? Carbon::parse($plan->start_date)->toDateString() : null,
            'release_day' => $plan->release_day ?: 'Friday',
            'remarks' => $plan->remarks,
            'release_policy' => $plan->release_policy,
            'ai_training_start_date' => optional($plan->ai_training_start_date)->toDateString(),
            'ai_training_release_dates' => $aiTrainingReleaseDates,
            'ai_training_institutes' => $account instanceof User && $account->role === 'Admin'
                ? Institute::query()->where('status', 1)->orderBy('institute_name')->get(['id', 'institute_name'])->map(fn (Institute $institute) => [
                    'id' => $institute->id,
                    'name' => $institute->institute_name,
                    'selected' => $institute->institute_name === $plan->institute,
                ])->values()
                : [],
            'lagged_course_contents' => $plan->course?->courseContents
                ? $plan->course->courseContents
                    ->where('status', 'active')
                    ->sortBy('sort_order')
                    ->map(fn ($courseContent) => [
                        'id' => $courseContent->id,
                        'title' => $courseContent->content?->content_title ?? $courseContent->title ?? 'Content',
                        'sort_order' => $courseContent->sort_order,
                    ])
                    ->values()
                : [],
            'weeks' => $plan->weeks
                ->map(fn (TeachingPlanWeek $week) => $this->mobileTeachingPlanWeekPayload($week))
                ->values(),
        ];
    }

    private function mobileTeachingPlanWeekPayload(TeachingPlanWeek $week): array
    {
        return [
            'id' => $week->id,
            'week_number' => $week->week_number,
            'status' => $week->status,
            'week_start_date' => optional($week->week_start_date)->toDateString(),
            'week_end_date' => optional($week->week_end_date)->toDateString(),
            'release_date' => optional($week->release_date)->toDateString(),
            'release_reason' => $week->release_reason,
            'items_count' => (int) ($week->items_count ?? $week->items()->count()),
            'items' => $week->items()->with('content')->orderBy('sort_order')->get()->map(fn ($item) => [
                'id' => $item->id, 'content_id' => $item->content_id, 'title' => $item->content?->content_title,
                'status' => $item->status, 'can_complete' => request()->user()?->role === 'Admin'
                    && $week->status === 'released' && $item->status === 'released' && filled($item->content?->file_path),
            ])->values(),
        ];
    }

    private function mobileReportModes(): array
    {
        return [
            'daily-session',
            'weekly-session',
            'monthly-session',
            'student-ai-review',
            'daily-student-performance',
            'weekly-student-performance',
            'monthly-student-performance',
            'stem-engineer-prep',
            'weekly-stem-engineer-performance',
            'monthly-stem-engineer-performance',
        ];
    }

    private function mobileReportMode(Request $request): string
    {
        $reportMode = trim((string) $request->input('report_mode', 'daily-session'));
        abort_unless(in_array($reportMode, $this->mobileReportModes(), true), 422, 'Unsupported report type.');

        return $reportMode;
    }

    private function adminIndependentLearnerItems(): array
    {
        return IndependentLearner::query()
            ->withCount(['enrollments', 'certificates'])
            ->orderBy('name')
            ->get()
            ->map(function (IndependentLearner $learner) {
                return [
                    'title' => $learner->name ?: 'Hybrid Learner',
                    'subtitle' => trim(($learner->email ?: 'Email n/a') . ' · ' . ($learner->phone ?: 'Phone n/a')),
                ];
            })
            ->values()
            ->all();
    }

    private function requireHybridLearner(Request $request): IndependentLearner
    {
        $account = $request->user();
        abort_if(!$account instanceof IndependentLearner, 403, 'Hybrid Learner access is required.');

        return $account;
    }

    private function hybridCourseProgress(CourseEnrollment $enrollment): int
    {
        $courseId = $enrollment->course_id;
        $lessonIds = Content::query()
            ->where('course_id', $courseId)
            ->where('status', 1)
            ->where('is_released', true)
            ->pluck('id');

        if ($lessonIds->isEmpty()) {
            return 0;
        }

        $completed = LessonProgress::where('independent_learner_id', $enrollment->learner_id)
            ->whereIn('content_id', $lessonIds)
            ->where('is_completed', true)
            ->count();

        return (int) round(($completed / $lessonIds->count()) * 100);
    }

    private function syncHybridLearnerCourseCompletion(IndependentLearner $learner, CourseEnrollment $enrollment): void
    {
        $lessonIds = Content::query()
            ->where('course_id', $enrollment->course_id)
            ->where('status', 1)
            ->where('is_released', true)
            ->where(function ($query) {
                $query->whereNotNull('student_file_path')
                    ->orWhereNotNull('file_path');
            })
            ->pluck('id');

        if ($lessonIds->isEmpty()) {
            return;
        }

        $completed = LessonProgress::where('independent_learner_id', $learner->id)
            ->whereIn('content_id', $lessonIds)
            ->where('is_completed', true)
            ->count();

        if ($completed !== $lessonIds->count()) {
            return;
        }

        $enrollment->update([
            'is_completed' => true,
            'completed_at' => $enrollment->completed_at ?: now(),
        ]);

        Certificate::firstOrCreate(
            [
                'certificate_type' => 'Independent',
                'independent_learner_id' => $learner->id,
                'course_id' => $enrollment->course_id,
            ],
            [
                'student_id' => null,
                'certificate_code' => 'IND-' . strtoupper(uniqid()),
                'badge_count' => 0,
                'issued_date' => now(),
                'status' => 'Issued',
            ]
        );
    }

    private function reportItems(array $items): array
    {
        return collect($items)
            ->map(fn (array $item) => [
                'title' => $item[0] ?? '',
                'subtitle' => $item[1] ?? '',
            ])
            ->values()
            ->all();
    }

    private function achievementIcon(?string $type): string
    {
        $normalized = strtolower((string) $type);

        if (str_contains($normalized, 'trophy')) {
            return 'trophy';
        }

        if (str_contains($normalized, 'verified') || str_contains($normalized, 'certificate')) {
            return 'verified';
        }

        return 'award';
    }
}
