<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use App\Models\UserSession;
use App\Models\Institute;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use App\Models\InstituteRegistrationRequest;
use App\Models\Content;


class UserController extends Controller
{
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
                    ->orWhere('email', 'like', "%{$search}%");
                });
            })
            ->get();

        return view('users', compact('users'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'user_id' => 'required|string|max:50|unique:users,user_id',
            'name' => 'required|string|max:100',
            'email' => 'required|email|unique:users,email',
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
            'email' => 'required|email|unique:users,email,' . $id,
            'status' => 'required|boolean',
            'institute' => session('user_role') == 'InstituteAdmin'
                ? session('user_institute')
                : $request->institute,
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
        ) 
        {
            abort(403, 'Unauthorized action.');
        }

        $user->delete();

        return redirect()->back()
            ->with('success', 'STEM Engineer deleted successfully');
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
            ->first();

        if ($user && Hash::check($request->password, $user->password)) {

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
            ->first();

        if ($user && Hash::check($request->password, $user->password)) {

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
            'admin_email' => 'required|email|max:255|unique:institute_registration_requests,admin_email|unique:users,email',
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
        return view('change-password');
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

        $user->update([
            'password' => Hash::make($request->new_password),
            'password_changed_at' => now(),
        ]);

        session([
            'password_changed_at' => now(),
        ]);

        return redirect()->route('admin.dashboard')
            ->with('success', 'Password changed successfully.');
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
        $customEmail = $slug . '.admin@tinkedge.local';

        if (User::where('email', $customEmail)->exists()) {
            return redirect()->back()
                ->with('error', 'A login email already exists for this institute.');
        }

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
            "Your TinkEdge LMS Institute Admin account has been approved.\n\n" .
            "Institute: " . $instituteName . "\n" .
            "Login Email: " . $customEmail . "\n" .
            "Temporary Password: " . $temporaryPassword . "\n\n" .
            "Use these credentials to login to the LMS Admin Portal.\n" .
            "Please change your password after login for security.",
            function ($message) use ($requestData) {
                $message->to($requestData->admin_email)
                    ->subject('TinkEdge LMS Institute Admin Credentials');
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
        $content = Content::findOrFail($contentId);

        $content->update([

            'is_released' => true

        ]);

        return redirect()->back()
            ->with(
                'success',
                'Topic marked as completed and released to students.'
            );
    }
}