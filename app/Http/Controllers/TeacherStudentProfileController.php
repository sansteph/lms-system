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
        ['selectedClass' => $selectedStudentClass, 'sectionPager' => $classSectionPager] =
            $this->buildStudentClassPager($request, $teacher->institute, 'teacher.student.profiles');
        ['selectedSection' => $selectedStudentSection, 'sectionPager' => $studentSectionPager] =
            $this->buildStudentSectionPager($request, $teacher->institute, $selectedStudentClass, 'teacher.student.profiles');

        $students = $this->teacherAssignedStudentsQuery($teacher)
            ->when($selectedStudentClass, function ($query) use ($selectedStudentClass) {
                $query->where('class', $selectedStudentClass);
            })
            ->when($selectedStudentSection, function ($query) use ($selectedStudentSection) {
                $query->where('section', $selectedStudentSection);
            })
            ->orderBy('class')
            ->orderBy('section')
            ->orderBy('name')
            ->get();

        return view('teacher.student-details', compact(
            'students',
            'classSectionPager',
            'studentSectionPager',
            'selectedStudentClass',
            'selectedStudentSection'
        ));
    }

    public function export(Request $request)
    {
        $teacher = User::findOrFail(session('user_id'));
        ['selectedClass' => $selectedStudentClass] =
            $this->buildStudentClassPager($request, $teacher->institute, 'teacher.student.profiles');
        ['selectedSection' => $selectedStudentSection] =
            $this->buildStudentSectionPager($request, $teacher->institute, $selectedStudentClass, 'teacher.student.profiles');

        $students = $this->teacherAssignedStudentsQuery($teacher)
            ->when($selectedStudentClass, function ($query) use ($selectedStudentClass) {
                $query->where('class', $selectedStudentClass);
            })
            ->when($selectedStudentSection, function ($query) use ($selectedStudentSection) {
                $query->where('section', $selectedStudentSection);
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

    private function buildStudentClassPager(Request $request, ?string $institute, string $routeName): array
    {
        if (!$institute) {
            return [
                'selectedClass' => null,
                'sectionPager' => null,
            ];
        }

        $classOptions = SchoolClass::where('institute', $institute)
            ->whereNotNull('class_name')
            ->orderBy('class_name')
            ->pluck('class_name')
            ->map(fn ($className) => trim((string) $className));

        $studentClassOptions = Student::where('institute', $institute)
            ->whereNotNull('class')
            ->select('class')
            ->distinct()
            ->orderBy('class')
            ->pluck('class')
            ->map(fn ($className) => trim((string) $className));

        $classOptions = $classOptions
            ->merge($studentClassOptions)
            ->filter()
            ->unique(fn ($className) => mb_strtolower($className))
            ->sort()
            ->values();

        if ($classOptions->isEmpty()) {
            return [
                'selectedClass' => null,
                'sectionPager' => null,
            ];
        }

        $requestedClass = $request->input('student_class');
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
        $query = $request->except(['class_page', 'student_class', 'student_section_page', 'student_section', 'section_page', 'page', 'class']);

        return [
            'selectedClass' => $selectedClass,
            'sectionPager' => [
                'current_label' => 'Class ' . $selectedClass,
                'current_page' => $currentPage,
                'last_page' => $lastPage,
                'previous_label' => $previousClass ? 'Class ' . $previousClass : null,
                'next_label' => $nextClass ? 'Class ' . $nextClass : null,
                'previous_url' => $previousClass
                    ? route('teacher.student.profiles', array_merge($query, [
                        'class_page' => $currentPage - 1,
                        'student_class' => $previousClass,
                    ]))
                    : null,
                'next_url' => $nextClass
                    ? route('teacher.student.profiles', array_merge($query, [
                        'class_page' => $currentPage + 1,
                        'student_class' => $nextClass,
                    ]))
                    : null,
            ],
        ];
    }

    private function buildStudentSectionPager(Request $request, ?string $institute, ?string $className, string $routeName): array
    {
        if (!$institute || !$className) {
            return [
                'selectedSection' => null,
                'sectionPager' => null,
            ];
        }

        $sectionOptions = SchoolClass::where('institute', $institute)
            ->where('class_name', $className)
            ->whereNotNull('section')
            ->orderBy('section')
            ->pluck('section')
            ->map(fn ($section) => trim((string) $section));

        $studentSectionOptions = Student::where('institute', $institute)
            ->where('class', $className)
            ->whereNotNull('section')
            ->select('section')
            ->distinct()
            ->orderBy('section')
            ->pluck('section')
            ->map(fn ($section) => trim((string) $section));

        $sectionOptions = $sectionOptions
            ->merge($studentSectionOptions)
            ->filter()
            ->unique(fn ($section) => mb_strtolower($section))
            ->sort()
            ->values();

        if ($sectionOptions->isEmpty()) {
            return [
                'selectedSection' => null,
                'sectionPager' => null,
            ];
        }

        $requestedSection = $request->input('student_section');
        $requestedIndex = $requestedSection ? $sectionOptions->search($requestedSection) : false;
        $lastPage = $sectionOptions->count();
        $currentPage = $requestedIndex !== false
            ? $requestedIndex + 1
            : min(max((int) $request->input('student_section_page', 1), 1), $lastPage);

        $selectedSection = $sectionOptions->get($currentPage - 1);
        $previousSection = $currentPage > 1 ? $sectionOptions->get($currentPage - 2) : null;
        $nextSection = $currentPage < $lastPage ? $sectionOptions->get($currentPage) : null;
        $query = $request->except(['student_section_page', 'student_section', 'page']);

        return [
            'selectedSection' => $selectedSection,
            'sectionPager' => [
                'current_label' => 'Section ' . $selectedSection,
                'current_page' => $currentPage,
                'last_page' => $lastPage,
                'previous_label' => $previousSection ? 'Section ' . $previousSection : null,
                'next_label' => $nextSection ? 'Section ' . $nextSection : null,
                'previous_url' => $previousSection
                    ? route($routeName, array_merge($query, [
                        'student_section_page' => $currentPage - 1,
                        'student_section' => $previousSection,
                    ]))
                    : null,
                'next_url' => $nextSection
                    ? route($routeName, array_merge($query, [
                        'student_section_page' => $currentPage + 1,
                        'student_section' => $nextSection,
                    ]))
                    : null,
            ],
        ];
    }
}
