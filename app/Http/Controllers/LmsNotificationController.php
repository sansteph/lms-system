<?php

namespace App\Http\Controllers;

use App\Models\Institute;
use App\Models\LmsNotification;
use App\Models\Student;
use Illuminate\Http\Request;

class LmsNotificationController extends Controller
{
    public function index()
    {
        $notifications = LmsNotification::query()
            ->when(session('user_role') == 'InstituteAdmin', function ($query) {
                $query->where(function ($scope) {
                    $scope->whereNull('institute')
                        ->orWhere('institute', session('user_institute'));
                });
            })
            ->latest()
            ->get();

        $institutes = session('user_role') == 'Admin'
            ? Institute::where('status', 1)->orderBy('institute_name')->get()
            : collect();

        return view('notifications.index', compact('notifications', 'institutes'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'message' => ['required', 'string', 'max:3000'],
            'target' => ['required', 'in:all,teachers,students'],
            'institute' => [session('user_role') == 'Admin' ? 'nullable' : 'prohibited', 'nullable', 'string', 'max:255'],
            'starts_at' => ['nullable', 'date'],
            'expires_at' => ['nullable', 'date', 'after_or_equal:starts_at'],
            'status' => ['required', 'in:active,draft,archived'],
        ]);

        if (session('user_role') == 'InstituteAdmin') {
            $validated['institute'] = session('user_institute');
        }

        $validated['created_by'] = session('user_id');

        LmsNotification::create($validated);

        return redirect()->back()->with('success', 'Notification created successfully.');
    }

    public function delete($id)
    {
        $notification = LmsNotification::findOrFail($id);

        if (
            session('user_role') == 'InstituteAdmin' &&
            $notification->institute !== session('user_institute')
        ) {
            abort(403, 'You cannot delete this notification.');
        }

        $notification->delete();

        return redirect()->back()->with('success', 'Notification deleted successfully.');
    }

    public function teacherIndex()
    {
        $notifications = $this->audienceNotifications('teachers', session('user_institute'));

        return view('notifications.audience', [
            'title' => 'Notifications',
            'notifications' => $notifications,
            'sidebar' => 'teacher',
        ]);
    }

    public function studentIndex()
    {
        $student = Student::findOrFail(session('student_id'));
        $notifications = $this->audienceNotifications('students', $student->institute);

        return view('notifications.audience', [
            'title' => 'Notifications',
            'notifications' => $notifications,
            'sidebar' => 'student',
        ]);
    }

    private function audienceNotifications(string $audience, ?string $institute)
    {
        $today = now()->toDateString();

        return LmsNotification::query()
            ->where('status', 'active')
            ->whereIn('target', ['all', $audience])
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
            ->get();
    }
}
