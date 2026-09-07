<?php

namespace App\Http\Controllers;

use App\Models\Institute;
use App\Models\LmsNotification;
use App\Models\Student;
use App\Services\FirebasePushService;
use Illuminate\Http\Request;

class LmsNotificationController extends Controller
{
    public function index(Request $request)
    {
        $hasFilters = $request->filled('from_date') || $request->filled('to_date');

        $notifications = LmsNotification::query()
            ->when(session('user_role') == 'InstituteAdmin', function ($query) {
                $query->where(function ($scope) {
                    $scope->whereNull('institute')
                        ->orWhere('institute', session('user_institute'));
                });
            })
            ->when($request->filled('from_date'), function ($query) use ($request) {
                $query->whereDate('created_at', '>=', $request->from_date);
            })
            ->when($request->filled('to_date'), function ($query) use ($request) {
                $query->whereDate('created_at', '<=', $request->to_date);
            })
            ->latest()
            ->get();

        $institutes = in_array(session('user_role'), ['Admin', 'Manager'], true)
            ? Institute::where('status', 1)->orderBy('institute_name')->get()
            : collect();

        return view('notifications.index', compact('notifications', 'institutes', 'hasFilters'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'message' => ['required', 'string', 'max:3000'],
            'target' => ['required', 'in:all,teachers,students'],
            'institute' => [in_array(session('user_role'), ['Admin', 'Manager'], true) ? 'nullable' : 'prohibited', 'nullable', 'string', 'max:255'],
            'starts_at' => ['nullable', 'date'],
            'expires_at' => ['nullable', 'date', 'after_or_equal:starts_at'],
            'status' => ['required', 'in:active,draft,archived'],
        ]);

        if (session('user_role') == 'InstituteAdmin') {
            $validated['institute'] = session('user_institute');
        }

        $validated['created_by'] = session('user_id');

        $notification = LmsNotification::create($validated);
        app(FirebasePushService::class)->sendLmsNotification($notification);

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

    public function teacherIndex(Request $request)
    {
        $notifications = $this->audienceNotifications('teachers', session('user_institute'), $request);
        $showFilterPlaceholder = !($request->filled('from_date') || $request->filled('to_date'));

        return view('notifications.audience', [
            'title' => 'Notifications',
            'notifications' => $notifications,
            'sidebar' => 'teacher',
            'showFilterPlaceholder' => $showFilterPlaceholder,
        ]);
    }

    public function studentIndex(Request $request)
    {
        $student = Student::findOrFail(session('student_id'));
        $notifications = $this->audienceNotifications('students', $student->institute, $request, $student->id);
        $showFilterPlaceholder = !($request->filled('from_date') || $request->filled('to_date'));

        return view('notifications.audience', [
            'title' => 'Notifications',
            'notifications' => $notifications,
            'sidebar' => 'student',
            'showFilterPlaceholder' => $showFilterPlaceholder,
        ]);
    }

    private function audienceNotifications(string $audience, ?string $institute, Request $request, ?int $studentId = null)
    {
        $today = now()->toDateString();

        return LmsNotification::query()
            ->where('status', 'active')
            ->where(function ($query) use ($audience, $institute, $studentId) {
                $query->where(function ($scope) use ($audience, $institute) {
                    $scope->whereIn('target', ['all', $audience])
                        ->where(function ($instituteScope) use ($institute) {
                            $instituteScope->whereNull('institute')
                                ->when($institute, fn ($nested) => $nested->orWhere('institute', $institute));
                        });
                });

                if ($audience === 'students' && $studentId) {
                    $query->orWhere('student_id', $studentId);
                }
            })
            ->where(function ($query) use ($today) {
                $query->whereNull('starts_at')
                    ->orWhereDate('starts_at', '<=', $today);
            })
            ->where(function ($query) use ($today) {
                $query->whereNull('expires_at')
                    ->orWhereDate('expires_at', '>=', $today);
            })
            ->when($request->filled('from_date'), function ($query) use ($request) {
                $query->whereDate('created_at', '>=', $request->from_date);
            })
            ->when($request->filled('to_date'), function ($query) use ($request) {
                $query->whereDate('created_at', '<=', $request->to_date);
            })
            ->latest()
            ->get();
    }
}
