<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\StudentAchievement;
use App\Models\Notification;

class StudentAchievementController extends Controller
{
    public function adminIndex()
    {
        $achievements = StudentAchievement::latest()->get();

        return view('admin-achievements', compact('achievements'));
    }

    public function approve($id)
    {
        $achievement = StudentAchievement::findOrFail($id);

        $achievement->update([
            'verification_status' => 'Approved'
        ]);

        Notification::create([

            'title' => 'Achievement Approved',

            'message' => 'Your achievement "' . $achievement->title . '" has been approved.',

            'target' => 'Student',

            'notification_date' => now(),

            'status' => 'Active'

        ]);
        return redirect()->back()
            ->with('success', 'Achievement approved successfully');
    }
    public function reject($id)
    {
        $achievement = StudentAchievement::findOrFail($id);

        $achievement->update([
            'verification_status' => 'Rejected'
        ]);

        Notification::create([

            'title' => 'Achievement Rejected',

            'message' => 'Your achievement "' . $achievement->title . '" has been rejected.',

            'target' => 'Student',

            'notification_date' => now(),

            'status' => 'Active'

        ]);
        return redirect()->back()
            ->with('success', 'Achievement rejected successfully');
    }
    public function index()
    {
        $studentId = session('student_id');

        $uploadedAchievements = StudentAchievement::where(
            'student_id',
            $studentId
        )->latest()->get();

        $results = collect();

        $goldCount = 0;

        $silverCount = 0;

        $bronzeCount = 0;

        $certificateEligible = false;

        $certificate = null;

        return view('student.student-badges', compact(

            'results',

            'goldCount',

            'silverCount',

            'bronzeCount',

            'certificateEligible',

            'certificate',

            'uploadedAchievements'

        ));
    }

    public function create()
    {
        return view('student.student-achievement-create');
    }

    public function store(Request $request)
    {
        $request->validate([

            'achievement_type' => 'required|string|max:100',

            'title' => 'required|string|max:255',

            'organizer' => 'nullable|string|max:255',

            'description' => 'nullable|string',

            'achievement_date' => 'nullable|date',

            'position' => 'nullable|string|max:100',

            'certificate_file' => 'required|mimes:pdf,jpg,jpeg,png|max:5120',

        ]);

        $filePath = null;

        if ($request->hasFile('certificate_file')) {

            $filePath = $request->file('certificate_file')
                ->store('certificates', 'public');
        }

        StudentAchievement::create([

            'student_id' => session('student_id'),

            'achievement_type' => $request->achievement_type,

            'title' => $request->title,

            'organizer' => $request->organizer,

            'description' => $request->description,

            'achievement_date' => $request->achievement_date,

            'position' => $request->position,

            'certificate_file' => $filePath,

            'verification_status' => 'Pending',

        ]);

        return redirect()
            ->route('student.badges')
            ->with('success', 'Achievement submitted successfully');
    }

    public function edit($id)
    {
        $achievement = StudentAchievement::where('id', $id)
            ->where('student_id', session('student_id'))
            ->firstOrFail();

        if ($achievement->verification_status == 'Approved') {
            return redirect()
                ->route('student.badges')
                ->with('error', 'Approved achievements cannot be edited.');
        }

        return view('student.student-achievement-edit', compact('achievement'));
    }

    public function update(Request $request, $id)
    {
        $achievement = StudentAchievement::where('id', $id)
            ->where('student_id', session('student_id'))
            ->firstOrFail();

        if ($achievement->verification_status == 'Approved') {
            return redirect()
                ->route('student.badges')
                ->with('error', 'Approved achievements cannot be edited.');
        }

        $request->validate([
            'achievement_type' => 'required|string|max:100',
            'title' => 'required|string|max:255',
            'organizer' => 'nullable|string|max:255',
            'description' => 'nullable|string',
            'achievement_date' => 'nullable|date',
            'position' => 'nullable|string|max:100',
            'certificate_file' => 'nullable|mimes:pdf,jpg,jpeg,png|max:5120',
        ]);

        $filePath = $achievement->certificate_file;

        if ($request->hasFile('certificate_file')) {
            $filePath = $request->file('certificate_file')
                ->store('certificates', 'public');
        }

        $achievement->update([
            'achievement_type' => $request->achievement_type,
            'title' => $request->title,
            'organizer' => $request->organizer,
            'description' => $request->description,
            'achievement_date' => $request->achievement_date,
            'position' => $request->position,
            'certificate_file' => $filePath,
            'verification_status' => 'Pending',
        ]);

        return redirect()
            ->route('student.badges')
            ->with('success', 'Achievement updated successfully and sent for review.');
    }

    public function delete($id)
    {
        $achievement = StudentAchievement::where('id', $id)
            ->where('student_id', session('student_id'))
            ->firstOrFail();

        $achievement->delete();

        return redirect()
            ->route('student.badges')
            ->with('success', 'Achievement deleted successfully.');
    }
}