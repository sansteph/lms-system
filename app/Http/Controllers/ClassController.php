<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Content;
use App\Models\SchoolClass;
use Illuminate\Support\Facades\DB;
use App\Support\DeletesAssessments;
use Illuminate\Support\Facades\Storage;
use App\Models\Student;
use App\Models\LessonProgress;
use App\Models\UserSession;
use App\Models\Certificate;
use App\Models\StudentAchievement;
use App\Models\AssessmentResult;
use App\Models\Assessment;
use App\Models\ClassTimetable;
use App\Models\ClassContentSession;
use App\Support\BuildsInstituteSectionPager;

class ClassController extends Controller
{
    use BuildsInstituteSectionPager, DeletesAssessments;
    public function index(Request $request)
    {
        $search = $request->search;
        $sectionPager = null;
        $classSectionPager = null;
        $sectionOnlyPager = null;
        $managedInstitute = session('user_role') == 'InstituteAdmin' ? session('user_institute') : null;
        $selectedInstitute = session('user_role') == 'Admin'
            ? trim((string) $request->input('institute'))
            : $managedInstitute;
        $selectedClassName = trim((string) $request->input('class_name')) ?: null;
        $selectedSectionName = trim((string) $request->input('section_name')) ?: null;
        $hasFilters = $request->filled('institute')
            || $request->filled('class_name')
            || $request->filled('section_name')
            || $request->filled('search');

        $instituteOptions = SchoolClass::whereNotNull('institute')
            ->where('institute', '!=', '')
            ->orderBy('institute')
            ->distinct()
            ->pluck('institute');

        $classFilterOptions = SchoolClass::when($selectedInstitute, function ($query) use ($selectedInstitute) {
                $query->where('institute', $selectedInstitute);
            })
            ->whereNotNull('class_name')
            ->where('class_name', '!=', '')
            ->orderBy('class_name')
            ->distinct()
            ->pluck('class_name');

        $sectionFilterOptions = SchoolClass::when($selectedInstitute, function ($query) use ($selectedInstitute) {
                $query->where('institute', $selectedInstitute);
            })
            ->when($selectedClassName, function ($query) use ($selectedClassName) {
                $query->where('class_name', $selectedClassName);
            })
            ->whereNotNull('section')
            ->where('section', '!=', '')
            ->orderBy('section')
            ->distinct()
            ->pluck('section');

        $classes = $hasFilters
            ? SchoolClass::when(
                session('user_role') == 'InstituteAdmin',
                function ($query) {
                    $query->where('institute', session('user_institute'));
                }
            )
            ->when(session('user_role') == 'Admin' && $selectedInstitute, function ($query) use ($selectedInstitute) {
                $query->where('institute', $selectedInstitute);
            })
            ->when($selectedClassName, function ($query) use ($selectedClassName) {
                $query->where('class_name', $selectedClassName);
            })
            ->when($selectedSectionName, function ($query) use ($selectedSectionName) {
                $query->where('section', $selectedSectionName);
            })
            ->when($search, function ($query, $search) {
                $query->where(function ($q) use ($search) {

                    $q->where('class_name', 'like', "%{$search}%")
                    ->orWhere('section', 'like', "%{$search}%")
                    ->orWhere('academic_year', 'like', "%{$search}%");

                });
            })
            ->orderBy('institute')
            ->orderBy('class_name')
            ->orderBy('section')
            ->paginate(30)
            ->withQueryString()
            : collect();

        return view(
            'classes',
            compact(
                'classes',
                'sectionPager',
                'classSectionPager',
                'sectionOnlyPager',
                'selectedClassName',
                'selectedSectionName',
                'managedInstitute',
                'selectedInstitute',
                'instituteOptions',
                'classFilterOptions',
                'sectionFilterOptions',
                'hasFilters'
            )
        );
    }

    private function buildClassNamePager(Request $request, ?string $institute): array
    {
        if (!$institute) {
            return [
                'selectedClassName' => null,
                'sectionPager' => null,
            ];
        }

        $classNames = SchoolClass::where('institute', $institute)
            ->whereNotNull('class_name')
            ->select('class_name')
            ->distinct()
            ->orderBy('class_name')
            ->pluck('class_name')
            ->map(fn ($className) => trim((string) $className))
            ->filter()
            ->unique()
            ->values();

        if ($classNames->isEmpty()) {
            return [
                'selectedClassName' => null,
                'sectionPager' => null,
            ];
        }

        $requestedClass = $request->input('class_name_filter');
        $requestedIndex = $requestedClass ? $classNames->search($requestedClass) : false;
        $lastPage = $classNames->count();
        $currentPage = $requestedIndex !== false
            ? $requestedIndex + 1
            : min(max((int) $request->input('class_page', 1), 1), $lastPage);

        $selectedClassName = $classNames->get($currentPage - 1);
        $previousClassName = $currentPage > 1 ? $classNames->get($currentPage - 2) : null;
        $nextClassName = $currentPage < $lastPage ? $classNames->get($currentPage) : null;
        $query = $request->except(['class_page', 'class_name_filter', 'section_page_filter', 'section_name_filter', 'page']);

        return [
            'selectedClassName' => $selectedClassName,
            'sectionPager' => [
                'current_label' => 'Class ' . $selectedClassName,
                'current_page' => $currentPage,
                'last_page' => $lastPage,
                'previous_label' => $previousClassName ? 'Class ' . $previousClassName : null,
                'next_label' => $nextClassName ? 'Class ' . $nextClassName : null,
                'previous_url' => $previousClassName
                    ? route('classes', array_merge($query, [
                        'class_page' => $currentPage - 1,
                        'class_name_filter' => $previousClassName,
                    ]))
                    : null,
                'next_url' => $nextClassName
                    ? route('classes', array_merge($query, [
                        'class_page' => $currentPage + 1,
                        'class_name_filter' => $nextClassName,
                    ]))
                    : null,
            ],
        ];
    }

    private function buildClassSectionOnlyPager(Request $request, ?string $institute, ?string $className): array
    {
        if (!$institute || !$className) {
            return [
                'selectedSectionName' => null,
                'sectionPager' => null,
            ];
        }

        $sectionNames = SchoolClass::where('institute', $institute)
            ->where('class_name', $className)
            ->whereNotNull('section')
            ->orderBy('section')
            ->pluck('section')
            ->map(fn ($sectionName) => trim((string) $sectionName))
            ->filter()
            ->unique(fn ($sectionName) => mb_strtolower($sectionName))
            ->values();

        if ($sectionNames->isEmpty()) {
            return [
                'selectedSectionName' => null,
                'sectionPager' => null,
            ];
        }

        $requestedSection = $request->input('section_name_filter');
        $requestedIndex = $requestedSection ? $sectionNames->search($requestedSection) : false;
        $lastPage = $sectionNames->count();
        $currentPage = $requestedIndex !== false
            ? $requestedIndex + 1
            : min(max((int) $request->input('section_page_filter', 1), 1), $lastPage);

        $selectedSectionName = $sectionNames->get($currentPage - 1);
        $previousSectionName = $currentPage > 1 ? $sectionNames->get($currentPage - 2) : null;
        $nextSectionName = $currentPage < $lastPage ? $sectionNames->get($currentPage) : null;
        $query = $request->except(['section_page_filter', 'section_name_filter', 'page']);

        return [
            'selectedSectionName' => $selectedSectionName,
            'sectionPager' => [
                'current_label' => 'Section ' . $selectedSectionName,
                'current_page' => $currentPage,
                'last_page' => $lastPage,
                'previous_label' => $previousSectionName ? 'Section ' . $previousSectionName : null,
                'next_label' => $nextSectionName ? 'Section ' . $nextSectionName : null,
                'previous_url' => $previousSectionName
                    ? route('classes', array_merge($query, [
                        'section_page_filter' => $currentPage - 1,
                        'section_name_filter' => $previousSectionName,
                    ]))
                    : null,
                'next_url' => $nextSectionName
                    ? route('classes', array_merge($query, [
                        'section_page_filter' => $currentPage + 1,
                        'section_name_filter' => $nextSectionName,
                    ]))
                    : null,
            ],
        ];
    }


    public function store(Request $request)
    {
        $request->validate([
            'class_name' => 'required|string|max:50',
            'sections' => 'required|array|min:1',
            'sections.*' => 'required|string|in:A,B,C,D,E,Combined',
            'academic_year' => 'required|string|max:20',
            'status' => 'required|boolean',
            'content_id' => 'nullable|exists:contents,id',
            'institute' => session('user_role') == 'Admin'
                ? 'required|string|max:255'
                : 'nullable|string|max:255',
        ]);

        $institute = session('user_role') == 'InstituteAdmin'
            ? session('user_institute')
            : $request->institute;

        $sections = array_values(array_unique($request->input('sections', [])));

        $createdCount = 0;

        DB::transaction(function () use ($request, $institute, $sections, &$createdCount) {
            foreach ($sections as $section) {
                $class = SchoolClass::firstOrCreate(
                    [
                        'institute' => $institute,
                        'class_name' => $request->class_name,
                        'section' => $section,
                        'academic_year' => $request->academic_year,
                    ],
                    [
                        'class_teacher' => null,
                        'content_id' => $request->content_id,
                        'status' => $request->status,
                    ]
                );

                if ($class->wasRecentlyCreated) {
                    $createdCount++;
                }
            }
        });

        if ($createdCount === 0) {
            return redirect()->back()->with('success', 'No new class sections were added because they already exist.');
        }

        return redirect()->back()->with('success', $createdCount . ' class section' . ($createdCount === 1 ? '' : 's') . ' added successfully.');
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'class_name' => 'required|string|max:50',
            'section' => 'required|string|max:20',
            'academic_year' => 'required|string|max:20',
            'content_id' => 'nullable|exists:contents,id',
            'status' => 'required|boolean',
            'institute' => session('user_role') == 'Admin'
                ? 'required|string|max:255'
                : 'nullable|string|max:255',
        ]);

        $class = SchoolClass::findOrFail($id);

        if (
            session('user_role') == 'InstituteAdmin' &&
            $class->institute != session('user_institute')
        ) {
            abort(403, 'Unauthorized action.');
        }

        $class->update([
            'institute' => session('user_role') == 'InstituteAdmin'
                ? session('user_institute')
                : $request->institute,

            'class_name' => $request->class_name,
            'section' => $request->section,
            'class_teacher' => null,
            'academic_year' => $request->academic_year,
            'content_id' => $request->content_id,
            'status' => $request->status,
        ]);

        return redirect()->back()->with('success', 'Class updated successfully');
    }

    public function delete($id)
    {
        $class = SchoolClass::findOrFail($id);

        if (
            session('user_role') == 'InstituteAdmin' &&
            $class->institute != session('user_institute')
        ) {
            abort(403, 'Unauthorized action.');
        }

        DB::transaction(function () use ($class) {

            $students = Student::where('class', $class->class_name)
                ->where('section', $class->section)
                ->where('institute', $class->institute)
                ->get();

            foreach ($students as $student) {
                $this->deleteStudentCompletely($student);
            }

            $this->deleteClassSessionsForSchoolClass($class);

            $timetables = ClassTimetable::where('class_id', $class->id)->get();

            foreach ($timetables as $timetable) {
                $timetable->delete();
            }

            $assignedClass = trim($class->class_name . ' ' . $class->section);

            $assessments = Assessment::where('assigned_class', $assignedClass)
                ->where('institute', $class->institute)
                ->get();

            foreach ($assessments as $assessment) {
                $this->deleteAssessmentCompletely($assessment);
            }

            $class->delete();
        });

        return redirect()->back()
            ->with('success', 'Class and all related records deleted successfully.');
    }

    private function deleteClassSessionsForSchoolClass(SchoolClass $class): void
    {
        ClassContentSession::where(function ($query) use ($class) {
                $query->where('class_id', $class->id)
                    ->orWhere(function ($nested) use ($class) {
                        $nested->where('institute', $class->institute)
                            ->where('class', $class->class_name)
                            ->where('section', $class->section);
                    });
            })
            ->delete();
    }
}
