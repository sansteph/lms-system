<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Student;

class StudentProfileController extends Controller
{
    public function create()
    {
        $student = Student::find(session('student_id'));

        if (!$student) {
            return redirect()->route('student.login');
        }

        if ($student->profile_completed) {
            return redirect()->route('student.dashboard');
        }

        return view('student.basic-details', compact('student'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255',
            'class' => 'required|string|max:100',
            'institute' => 'required|string|max:255',
            'guardian_name' => 'required|string|max:255',
            'guardian_contact' => 'required|string|max:20',
            'is_robotics_club_member' => 'required|in:0,1',
        ]);

        $student = Student::find(session('student_id'));

        if (!$student) {
            return redirect()->route('student.login');
        }

        $student->update([
            'name' => $request->name,
            'email' => $request->email,
            'class' => $request->class,
            'guardian_name' => $request->guardian_name,
            'guardian_contact' => $request->guardian_contact,
            'is_robotics_club_member' => $request->is_robotics_club_member,
            'profile_completed' => true,
        ]);

        return redirect()->route('student.dashboard')
            ->with('success', 'Profile completed successfully.');
    }
}