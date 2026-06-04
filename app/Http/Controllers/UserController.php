<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use App\Models\UserSession;
use App\Models\Institute;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

class UserController extends Controller
{
    public function index(Request $request)
    {
        $search = $request->search;

        $users = User::where('role', 'Teacher')
            ->when($search, function ($query, $search) {
                return $query->where(function ($q) use ($search) {
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
        ]);

        User::create([
            'user_id' => $request->user_id,
            'name' => $request->name,
            'email' => $request->email,
            'role' => 'Teacher',
            'password' => Hash::make($request->password),
            'status' => $request->status,
        ]);

        return redirect()->back()
            ->with('success', 'Teacher added successfully');
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'user_id' => 'required|string|max:50|unique:users,user_id,' . $id,
            'name' => 'required|string|max:100',
            'email' => 'required|email|unique:users,email,' . $id,
            'status' => 'required|boolean',
        ]);

        $user = User::where('role', 'Teacher')->findOrFail($id);

        $user->update([
            'user_id' => $request->user_id,
            'name' => $request->name,
            'email' => $request->email,
            'status' => $request->status,
        ]);

        return redirect()->back()
            ->with('success', 'Teacher updated successfully');
    }

    public function delete($id)
    {
        $user = User::where('role', 'Teacher')->findOrFail($id);

        $user->delete();

        return redirect()->back()
            ->with('success', 'Teacher deleted successfully');
    }

    public function adminLogin(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required',
        ]);

        $user = User::where('email', $request->email)
            ->where('role', 'Admin')
            ->where('status', 1)
            ->first();

        if ($user && Hash::check($request->password, $user->password)) {

            session([
                'user_id' => $user->id,
                'user_name' => $user->name,
                'user_role' => $user->role,
                'password_changed_at' => $user->password_changed_at,
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
            ->with('error', 'Invalid teacher login details');
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
            'admin_email' => 'required|email|max:255|unique:users,email',
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

        $existingInstitute = Institute::where('institute_name', $instituteName)
            ->first();

        $temporaryPassword = Str::random(10);

        do {
            $adminUserId = 'ADM' . rand(100000, 999999);
        } while (User::where('user_id', $adminUserId)->exists());

        User::create([
            'user_id' => $adminUserId,
            'name' => $request->admin_name,
            'email' => $request->admin_email,
            'phone' => $request->phone,
            'institute' => $instituteName,
            'role' => 'Admin',
            'password' => Hash::make($temporaryPassword),
            'status' => 1,
            'password_changed_at' => null,
        ]);

        if (!$existingInstitute) {

            do {
                $instituteId = 'INS' . rand(100000, 999999);
            } while (Institute::where('institute_id', $instituteId)->exists());

            Institute::create([
                'institute_id' => $instituteId,
                'institute_name' => $instituteName,
                'location' => $request->location,
                'contact_person' => $request->admin_name,
                'email' => $request->admin_email,
                'phone' => $request->phone,
                'status' => 1,
            ]);
        }

        Mail::raw(
            "Your TinkEdge LMS Admin account has been created.\n\n" .
            "Institute: " . $instituteName . "\n" .
            "Email: " . $request->admin_email . "\n" .
            "Temporary Password: " . $temporaryPassword . "\n\n" .
            "Please login and change your password for security.",
            function ($message) use ($request) {
                $message->to($request->admin_email)
                    ->subject('TinkEdge LMS Admin Login Credentials');
            }
        );

        return redirect()->route('admin.login')
            ->with('success', 'Admin account created. Login credentials have been sent to your email.');
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
}