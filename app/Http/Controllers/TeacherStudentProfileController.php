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
        ['selectedClass' => $selectedClass, 'sectionPager' => $sectionPager] =
            $this->buildClassSectionPager($request, $classOptions);

        $students = $this->teacherAssignedStudentsQuery($teacher)
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

        return view('teacher.student-details', compact('students', 'classOptions', 'selectedClass', 'sectionPager'));
    }

    public function export(Request $request)
    {
        $teacher = User::findOrFail(session('user_id'));
        $classOptions = $this->teacherClassOptions($teacher);
        ['selectedClass' => $selectedClass] = $this->buildClassSectionPager($request, $classOptions);

        $students = $this->teacherAssignedStudentsQuery($teacher)
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
            ->filter()
            ->unique()
            ->values();
    }

    private function buildClassSectionPager(Request $request, $classOptions): array
    {
        if ($classOptions->isEmpty()) {
            return [
                'selectedClass' => null,
                'sectionPager' => null,
            ];
        }

        $requestedClass = $request->input('class');
        $requestedIndex = $requestedClass
            ? $classOptions->search($requestedClass)
            : false;

        $lastPage = $classOptions->count();
        $currentPage = $requestedIndex !== false
            ? $requestedIndex + 1
            : min(max((int) $request->query('section_page', 1), 1), $lastPage);

        $selectedClass = $classOptions->get($currentPage - 1);
        $previousClass = $currentPage > 1 ? $classOptions->get($currentPage - 2) : null;
        $nextClass = $currentPage < $lastPage ? $classOptions->get($currentPage) : null;
        $query = $request->except(['section_page', 'page', 'class']);

        return [
            'selectedClass' => $selectedClass,
            'sectionPager' => [
                'current_label' => $selectedClass,
                'current_page' => $currentPage,
                'last_page' => $lastPage,
                'previous_label' => $previousClass ?: 'Start of list',
                'next_label' => $nextClass ?: 'End of list',
                'previous_url' => $previousClass
                    ? route('teacher.student.profiles', array_merge($query, [
                        'section_page' => $currentPage - 1,
                        'class' => $previousClass,
                    ]))
                    : null,
                'next_url' => $nextClass
                    ? route('teacher.student.profiles', array_merge($query, [
                        'section_page' => $currentPage + 1,
                        'class' => $nextClass,
                    ]))
                    : null,
            ],
        ];
    }
}
