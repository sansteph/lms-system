<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Notification;

class NotificationController extends Controller
{
    public function index(Request $request)
    {
        $search = $request->search;

        $notifications = Notification::when($search, function ($query, $search) {
            return $query->where('title', 'like', "%{$search}%")
                         ->orWhere('message', 'like', "%{$search}%")
                         ->orWhere('target', 'like', "%{$search}%");
        })->get();

        return view('notifications', compact('notifications'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'title' => 'required|string|max:255',
            'message' => 'required|string',
            'target' => 'required|string',
            'notification_date' => 'required|date',
        ]);

        Notification::create([
            'title' => $request->title,
            'message' => $request->message,
            'target' => $request->target,
            'notification_date' => $request->notification_date,
        ]);

        return redirect()->back()->with('success', 'Notification sent successfully');
    }
    public function delete($id)
    {
        $notification = Notification::findOrFail($id);

        $notification->delete();

        return redirect()->back()->with('success', 'Notification deleted successfully');
    }
    public function update(Request $request, $id)
    {
        $request->validate([
            'title' => 'required|string|max:255',
            'message' => 'required|string',
            'target' => 'required|string',
            'notification_date' => 'required|date',
        ]);

        $notification = Notification::findOrFail($id);

        $notification->update([
            'title' => $request->title,
            'message' => $request->message,
            'target' => $request->target,
            'notification_date' => $request->notification_date,
        ]);

        return redirect()->back()->with('success', 'Notification updated successfully');
    }
}