<?php

namespace App\Http\Controllers;

use App\Models\Student;
use App\Models\User;

class TeacherStudentProfileController extends Controller
{
    public function index()
    {
        $teacher = User::findOrFail(session('user_id'));

        $students = $this->teacherAssignedStudentsQuery($teacher)
            ->where('profile_completed', true)
            ->latest()
            ->get();

        return view('teacher.student-details', compact('students'));
    }

    public function export()
    {
        $teacher = User::findOrFail(session('user_id'));

        $students = $this->teacherAssignedStudentsQuery($teacher)
            ->where('profile_completed', true)
            ->get();

        $filename = 'student_profiles.csv';

        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"$filename\"",
        ];

        $callback = function () use ($students) {
            $file = fopen('php://output', 'w');

            fputcsv($file, [
                'Student Name',
                'Email',
                'Class',
                'Institute',
                'Guardian Name',
                'Guardian Contact',
                'Robotics Club Member',
            ]);

            foreach ($students as $student) {
                fputcsv($file, [
                    $student->name,
                    $student->email,
                    $student->class,
                    $student->institute,
                    $student->guardian_name,
                    $student->guardian_contact,
                    $student->is_robotics_club_member ? 'Yes' : 'No',
                ]);
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    private function teacherAssignedStudentsQuery(User $teacher)
    {
        return Student::where('institute', $teacher->institute);
    }
}
