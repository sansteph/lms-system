<?php

namespace App\Http\Controllers;

use App\Models\Student;
use App\Models\User;
use App\Models\SchoolClass;
use Illuminate\Http\Request;

class TeacherStudentProfileController extends Controller
{
    public function index(Request $request)
    {
        $teacher = User::findOrFail(session('user_id'));
        $classOptions = $this->teacherClassOptions($teacher);
        $selectedClass = $request->input('class');

        $students = $this->teacherAssignedStudentsQuery($teacher)
            ->where('profile_completed', true)
            ->when($selectedClass, function ($query) use ($selectedClass) {
                $query->whereRaw(
                    "REPLACE(TRIM(CONCAT(COALESCE(class, ''), ' ', COALESCE(section, ''))), '  ', ' ') = ?",
                    [$selectedClass]
                );
            })
            ->orderBy('class')
            ->orderBy('section')
            ->orderBy('name')
            ->get();

        return view('teacher.student-details', compact('students', 'classOptions', 'selectedClass'));
    }

    public function export(Request $request)
    {
        $teacher = User::findOrFail(session('user_id'));
        $selectedClass = $request->input('class');

        $students = $this->teacherAssignedStudentsQuery($teacher)
            ->where('profile_completed', true)
            ->when($selectedClass, function ($query) use ($selectedClass) {
                $query->whereRaw(
                    "REPLACE(TRIM(CONCAT(COALESCE(class, ''), ' ', COALESCE(section, ''))), '  ', ' ') = ?",
                    [$selectedClass]
                );
            })
            ->orderBy('class')
            ->orderBy('section')
            ->orderBy('name')
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

    private function teacherClassOptions(User $teacher)
    {
        return SchoolClass::where('institute', $teacher->institute)
            ->orderBy('class_name')
            ->orderBy('section')
            ->get()
            ->map(fn ($class) => trim($class->class_name . ' ' . $class->section))
            ->values();
    }
}
