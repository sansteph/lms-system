<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Notification;

class NotificationController extends Controller
{
    public function index(Request $request)
    {
        $search = $request->search;

        $notifications = Notification::when(session('user_role') == 'InstituteAdmin', function ($query) {
                $query->where('institute', session('user_institute'));
            })
            ->when($search, function ($query, $search) {
                $query->where(function ($q) use ($search) {
                    $q->where('title', 'like', "%{$search}%")
                      ->orWhere('message', 'like', "%{$search}%")
                      ->orWhere('target', 'like', "%{$search}%");
                });
            })
            ->latest()
            ->get();

        return view('notifications', compact('notifications'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'title' => 'required|string|max:255',
            'message' => 'required|string',
            'target' => 'required|string',
            'notification_date' => 'required|date',
            'institute' => session('user_role') == 'Admin'
                ? 'required|string|max:255'
                : 'nullable|string|max:255',
        ]);

        Notification::create([
            'institute' => session('user_role') == 'InstituteAdmin'
                ? session('user_institute')
                : $request->institute,
            'title' => $request->title,
            'message' => $request->message,
            'target' => $request->target,
            'notification_date' => $request->notification_date,
        ]);

        return redirect()->back()->with('success', 'Notification sent successfully');
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'title' => 'required|string|max:255',
            'message' => 'required|string',
            'target' => 'required|string',
            'notification_date' => 'required|date',
            'institute' => session('user_role') == 'Admin'
                ? 'required|string|max:255'
                : 'nullable|string|max:255',
        ]);

        $notification = Notification::findOrFail($id);

        if (
            session('user_role') == 'InstituteAdmin' &&
            $notification->institute != session('user_institute')
        ) {
            abort(403, 'Unauthorized action.');
        }

        $notification->update([
            'institute' => session('user_role') == 'InstituteAdmin'
                ? session('user_institute')
                : $request->institute,
            'title' => $request->title,
            'message' => $request->message,
            'target' => $request->target,
            'notification_date' => $request->notification_date,
        ]);

        return redirect()->back()->with('success', 'Notification updated successfully');
    }

    public function delete($id)
    {
        $notification = Notification::findOrFail($id);

        if (
            session('user_role') == 'InstituteAdmin' &&
            $notification->institute != session('user_institute')
        ) {
            abort(403, 'Unauthorized action.');
        }

        $notification->delete();

        return redirect()->back()->with('success', 'Notification deleted successfully');
    }
}