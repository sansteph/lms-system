<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use App\Models\UserSession;
use App\Models\Institute;
use App\Models\PendingPasswordChange;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use App\Models\InstituteRegistrationRequest;
use App\Models\Content;
use Illuminate\Support\Facades\DB;
use App\Models\SchoolClass;
use App\Models\ClassContentSession;
use App\Support\DeletesAssessments;


class UserController extends Controller
{
    use DeletesAssessments;

    public function index(Request $request)
    {
        $search = $request->search;

        $users = User::where('role', 'Teacher')
            ->when(session('user_role') == 'InstituteAdmin', function ($query) {
                $query->where('institute', session('user_institute'));
            })
            ->when($search, function ($query, $search) {
                $query->where(function ($q) use ($search) {
                    $q->where('user_id', 'like', "%{$search}%")
                    ->orWhere('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('institute', 'like', "%{$search}%");
                });
            })
            ->orderBy('institute')
            ->orderBy('name')
            ->get();

        return view('users', compact('users'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'user_id' => 'required|string|max:50|unique:users,user_id',
            'name' => 'required|string|max:100',
            'email' => 'required|email',
            'qualification' => 'required|string|max:255',
            'password' => 'required|min:6',
            'status' => 'required|boolean',
            'institute' => session('user_role') == 'Admin'
                ? 'required|string|max:255'
                : 'nullable|string|max:255',
        ]);

        User::create([
            'user_id' => $request->user_id,
            'name' => $request->name,
            'email' => $request->email,
            'qualification' => $request->qualification,
            'institute' => session('user_role') == 'InstituteAdmin'
                ? session('user_institute')
                : $request->institute,
            'role' => 'Teacher',
            'password' => Hash::make($request->password),
            'status' => $request->status,
        ]);

        return redirect()->back()
            ->with('success', 'STEM Engineer added successfully');
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'user_id' => 'required|string|max:50|unique:users,user_id,' . $id,
            'name' => 'required|string|max:100',
            'email' => 'required|email',
            'qualification' => 'required|string|max:255',
            'status' => 'required|boolean',
            'institute' => session('user_role') == 'InstituteAdmin'
                ? 'nullable|string|max:255'
                : 'required|string|max:255',
        ]);

        $user = User::where('role', 'Teacher')->findOrFail($id);
        if (
            session('user_role') == 'InstituteAdmin' &&
            $user->institute != session('user_institute')
        ) 
        {
            abort(403, 'Unauthorized action.');
        }

        $user->update([
            'user_id' => $request->user_id,
            'name' => $request->name,
            'email' => $request->email,
            'qualification' => $request->qualification,
            'institute' => session('user_role') == 'InstituteAdmin'
                ? session('user_institute')
                : $request->institute,
            'status' => $request->status,
        ]);

        return redirect()->back()
            ->with('success', 'STEM Engineer updated successfully');
    }

    public function delete($id)
    {
        $user = User::where('role', 'Teacher')->findOrFail($id);

        if (
            session('user_role') == 'InstituteAdmin' &&
            $user->institute != session('user_institute')
        ) {
            abort(403, 'Unauthorized action.');
        }

        DB::transaction(function () use ($user, $id) {

            SchoolClass::where('class_teacher', $user->name)
                ->where('institute', $user->institute)
                ->update([
                    'class_teacher' => null,
                ]);

            $this->deleteTeacherCompletely($user);
        });

        return redirect()->back()
            ->with('success', 'STEM Engineer and related records deleted successfully.');
    }

    public function adminLogin(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required',
        ]);

        $user = User::where('email', $request->email)
            ->whereIn('role', ['Admin', 'InstituteAdmin'])
            ->where('status', 1)
            ->get()
            ->first(function ($user) use ($request) {
                return Hash::check($request->password, $user->password);
            });

        if ($user) {

            session([
                'user_id' => $user->id,
                'user_name' => $user->name,
                'user_role' => $user->role,
                'user_institute' => $user->institute,
                'password_changed_at' => $user->password_changed_at,
            ]);

            $userSession = UserSession::create([
                'user_type' => $user->role,
                'user_id' => $user->id,
                'login_time' => now(),
                'ip_address' => $request->ip(),
                'browser' => $request->userAgent(),
            ]);

            session([
                'tracking_session_id' => $userSession->id,
            ]);
            return redirect()->route('admin.dashboard');
        }

        return redirect()->back()
            ->with('error', 'Invalid admin login details');
    }

    public function teacherLogin(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required',
        ]);

        $user = User::where('email', $request->email)
            ->where('role', 'Teacher')
            ->where('status', 1)
            ->get()
            ->first(function ($user) use ($request) {
                return Hash::check($request->password, $user->password);
            });

        if ($user) {

            session([
                'user_id' => $user->id,
                'user_name' => $user->name,
                'user_role' => $user->role,
                'user_institute' => $user->institute,
                'password_changed_at' => $user->password_changed_at,
            ]);

            $userSession = UserSession::create([
                'user_type' => 'Teacher',
                'user_id' => $user->id,
                'login_time' => now(),
                'ip_address' => $request->ip(),
                'browser' => $request->userAgent(),
            ]);

            session([
                'tracking_session_id' => $userSession->id,
            ]);

            return redirect()->route('teacher.dashboard');
        }

        return redirect()->back()
            ->with('error', 'Invalid STEM Engineer login details');
    }

    public function logout()
    {
        $trackingSessionId = session('tracking_session_id');

        if ($trackingSessionId) {
            $userSession = UserSession::find($trackingSessionId);

            if ($userSession && !$userSession->logout_time) {
                $logoutTime = now();

                $userSession->update([
                    'logout_time' => $logoutTime,
                    'total_duration_seconds' => $logoutTime->diffInSeconds($userSession->login_time),
                ]);
            }
        }

        session()->flush();

        return redirect()->route('home');
    }


    public function instituteRegister()
    {
        return view('institute-register');
    }

    public function instituteRegisterSubmit(Request $request)
    {
        $request->validate([
            'admin_name' => 'required|string|max:255',
            'admin_email' => 'required|email|max:255',
            'institute_name' => 'required|string|max:255',
            'phone' => 'required|string|max:20',
            'location' => 'required|string|max:255',
        ]);

        $instituteName = trim($request->institute_name);

        $adminExistsForInstitute = User::where('role', 'Admin')
            ->where('institute', $instituteName)
            ->exists();

        if ($adminExistsForInstitute) {
            return redirect()->back()
                ->with('error', 'This institute already has an admin account.');
        }

        InstituteRegistrationRequest::create([
            'request_id' => 'REQ' . rand(100000, 999999),
            'admin_name' => $request->admin_name,
            'admin_email' => $request->admin_email,
            'phone' => $request->phone,
            'institute_name' => $instituteName,
            'location' => $request->location,
            'status' => 'Pending',
        ]);

        return redirect()->route('admin.login')
            ->with('success', 'Registration request submitted successfully. You will receive login credentials after approval.');
    }

    public function changePassword()
    {
        return view('change-password', [
            'submitRoute' => route('admin.change.password.submit'),
            'dashboardRoute' => route('admin.dashboard'),
            'sidebar' => 'admin',
        ]);
    }

    public function changePasswordSubmit(Request $request)
    {
        $request->validate([
            'current_password' => 'required',
            'new_password' => 'required|min:6|confirmed',
        ]);

        $user = User::findOrFail(session('user_id'));

        if (!Hash::check($request->current_password, $user->password)) {
            return redirect()->back()
                ->with('error', 'Current password is incorrect.');
        }

        return $this->sendPasswordChangeConfirmation($user, $request->new_password);
    }

    public function teacherChangePassword()
    {
        return view('change-password', [
            'submitRoute' => route('teacher.change.password.submit'),
            'dashboardRoute' => route('teacher.dashboard'),
            'sidebar' => 'teacher',
        ]);
    }

    public function teacherChangePasswordSubmit(Request $request)
    {
        $request->validate([
            'current_password' => 'required',
            'new_password' => 'required|min:6|confirmed',
        ]);

        $user = User::where('role', 'Teacher')->findOrFail(session('user_id'));

        if (!Hash::check($request->current_password, $user->password)) {
            return redirect()->back()
                ->with('error', 'Current password is incorrect.');
        }

        return $this->sendPasswordChangeConfirmation($user, $request->new_password);
    }

    private function sendPasswordChangeConfirmation(User $user, string $newPassword)
    {
        if (!$user->email) {
            return redirect()->back()
                ->with('error', 'Your account does not have a login email address.');
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

            return redirect()->back()
                ->with('error', 'Password confirmation email could not be sent. Please check mail configuration and try again.');
        }

        return redirect()->back()
            ->with('success', 'A confirmation email has been sent to your login email. Click "Yes, it is me" to complete the password change.');
    }

    public function confirmPasswordChange($token)
    {
        $pendingChange = PendingPasswordChange::with('user')
            ->where('token_hash', hash('sha256', $token))
            ->whereNull('confirmed_at')
            ->first();

        if (!$pendingChange || !$pendingChange->user || $pendingChange->expires_at->isPast()) {
            return view('password-change-confirmed', [
                'dashboardRoute' => route('admin.login'),
                'status' => 'error',
                'message' => 'This password confirmation link is invalid or expired.',
            ]);
        }

        $user = $pendingChange->user;

        DB::transaction(function () use ($user, $pendingChange) {
            $user->update([
                'password' => $pendingChange->new_password,
                'password_changed_at' => now(),
            ]);

            PendingPasswordChange::where('user_id', $user->id)
                ->delete();
        });

        if (session('user_id') == $user->id) {
            session([
                'password_changed_at' => now(),
            ]);
        }

        if (session('user_id') == $user->id) {
            $dashboardRoute = $user->role === 'Teacher'
                ? route('teacher.dashboard')
                : route('admin.dashboard');
        } else {
            $dashboardRoute = $user->role === 'Teacher'
                ? route('teacher.login')
                : route('admin.login');
        }

        return view('password-change-confirmed', [
            'dashboardRoute' => $dashboardRoute,
            'status' => 'success',
            'message' => 'Password changed successfully.',
        ]);
    }

    public function instituteRequests()
    {
        $requests = InstituteRegistrationRequest::latest()->get();

        return view('institute-requests', compact('requests'));
    }

    public function approveInstituteRequest($id)
    {
        $requestData = InstituteRegistrationRequest::findOrFail($id);

        if ($requestData->status != 'Pending') {
            return redirect()->back()
                ->with('error', 'This request has already been processed.');
        }

        $instituteName = trim($requestData->institute_name);

        $adminExistsForInstitute = User::where('role', 'InstituteAdmin')
            ->where('institute', $instituteName)
            ->exists();

        if ($adminExistsForInstitute) {
            return redirect()->back()
                ->with('error', 'This institute already has an admin account.');
        }

        $slug = strtolower(preg_replace('/[^a-zA-Z0-9]/', '', $instituteName));
        $customEmail = $slug . '.admin@InnovatEdge.local';

        $temporaryPassword = Str::random(10);

        do {
            $adminUserId = 'ADM' . rand(100000, 999999);
        } while (User::where('user_id', $adminUserId)->exists());

        User::create([
            'user_id' => $adminUserId,
            'name' => $requestData->admin_name,
            'email' => $customEmail,
            'phone' => $requestData->phone,
            'institute' => $instituteName,
            'role' => 'InstituteAdmin',
            'password' => Hash::make($temporaryPassword),
            'status' => 1,
            'password_changed_at' => null,
        ]);

        $existingInstitute = Institute::where('institute_name', $instituteName)->first();

        if (!$existingInstitute) {
            do {
                $instituteId = 'INS' . rand(100000, 999999);
            } while (Institute::where('institute_id', $instituteId)->exists());

            Institute::create([
                'institute_id' => $instituteId,
                'institute_name' => $instituteName,
                'location' => $requestData->location,
                'contact_person' => $requestData->admin_name,
                'email' => $customEmail,
                'phone' => $requestData->phone,
                'status' => 1,
            ]);
        }

        $requestData->update([
            'status' => 'Approved',
            'remarks' => 'Approved and InstituteAdmin account created.',
        ]);

        Mail::raw(
            "Your InnovatEdge Institute Admin account has been approved.\n\n" .
            "Institute: " . $instituteName . "\n" .
            "Login Email: " . $customEmail . "\n" .
            "Temporary Password: " . $temporaryPassword . "\n\n" .
            "Use these credentials to login to the LMS Admin Portal.\n" .
            "Please change your password after login for security.",
            function ($message) use ($requestData) {
                $message->to($requestData->admin_email)
                    ->subject('InnovatEdge Institute Admin Credentials');
            }
        );

        return redirect()->back()
            ->with('success', 'Institute request approved and credentials sent.');
    }

    public function rejectInstituteRequest($id)
    {
        $requestData = InstituteRegistrationRequest::findOrFail($id);

        if ($requestData->status != 'Pending') {
            return redirect()->back()
                ->with('error', 'This request has already been processed.');
        }

        $requestData->update([
            'status' => 'Rejected',
            'remarks' => 'Registration request rejected.',
        ]);

        return redirect()->back()
            ->with('success', 'Institute request rejected.');
    }

    public function markTopicComplete($contentId)
    {
        $teacher = User::findOrFail(session('user_id'));
        $content = Content::with('course')->findOrFail($contentId);

        if ($content->institute != $teacher->institute || !$content->file_path) {
            abort(403, 'This topic cannot be released to students.');
        }

        $completedSession = ClassContentSession::with([
                'teachingPlan',
                'teachingPlanWeek',
                'teachingPlanItem',
            ])
            ->where('institute', $teacher->institute)
            ->where('stem_engineer_id', $teacher->id)
            ->where('content_id', $content->id)
            ->where('status', 'completed')
            ->latest()
            ->first();

        $plan = $completedSession?->teachingPlan;
        $week = $completedSession?->teachingPlanWeek;
        $item = $completedSession?->teachingPlanItem;

        $isCompletedInstitutePlanContent = $plan &&
            $week &&
            $item &&
            $completedSession->institute === $teacher->institute &&
            $plan->institute === $teacher->institute &&
            in_array($plan->status, ['active', 'completed'], true) &&
            in_array($week->status, ['released', 'completed'], true) &&
            $item->status === 'completed' &&
            (int) $item->teaching_plan_id === (int) $plan->id &&
            (int) $item->teaching_plan_week_id === (int) $week->id &&
            (int) $item->content_id === (int) $content->id &&
            (int) $completedSession->teaching_plan_id === (int) $plan->id &&
            (int) $completedSession->teaching_plan_week_id === (int) $week->id &&
            (int) $completedSession->teaching_plan_item_id === (int) $item->id &&
            (int) $completedSession->course_id === (int) $item->course_id &&
            (int) $completedSession->content_id === (int) $item->content_id;

        if (!$isCompletedInstitutePlanContent) {
            abort(403, 'You can only release completed Teaching Plan content from your institute.');
        }

        $wasReleased = (bool) $content->is_released;

        $content->update([

            'is_released' => true

        ]);

        if (!$wasReleased) {
            app(\App\Services\LmsNotificationService::class)
                ->notifyStudentsOfReleasedContent($content->fresh());
        }

        return redirect()->back()
            ->with(
                'success',
                'Topic marked as completed and released to students.'
            );
    }
}
