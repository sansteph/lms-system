<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Institute;
use App\Models\SchoolClass;
use App\Models\Student;
use App\Models\StudentAchievement;
use App\Support\SyncsCommunityPosts;
use App\Support\BuildsInstituteSectionPager;
use Illuminate\Support\Facades\Storage;

class StudentAchievementController extends Controller
{
    use BuildsInstituteSectionPager, SyncsCommunityPosts;

    public function adminIndex(Request $request)
    {
        $currentInstitute = session('user_role') == 'InstituteAdmin'
            ? session('user_institute')
            : ($request->filled('institute') ? trim((string) $request->input('institute')) : null);
        $selectedStudentClass = $request->filled('student_class')
            ? trim((string) $request->input('student_class'))
            : null;
        $selectedStudentSection = $request->filled('student_section')
            ? trim((string) $request->input('student_section'))
            : null;
        $hasFilters = session('user_role') == 'InstituteAdmin'
            || $request->filled('institute')
            || $request->filled('student_class')
            || $request->filled('student_section');
        $instituteOptions = session('user_role') == 'Admin'
            ? Institute::where('status', 1)->orderBy('institute_name')->pluck('institute_name')
            : collect([session('user_institute')]);
        $classOptions = Student::when($currentInstitute, function ($query) use ($currentInstitute) {
                $query->where('institute', $currentInstitute);
            })
            ->whereNotNull('class')
            ->select('class')
            ->distinct()
            ->orderBy('class')
            ->pluck('class')
            ->map(fn ($className) => trim((string) $className))
            ->filter()
            ->unique()
            ->values();
        $sectionOptions = Student::when($currentInstitute, function ($query) use ($currentInstitute) {
                $query->where('institute', $currentInstitute);
            })
            ->when($selectedStudentClass, function ($query) use ($selectedStudentClass) {
                $query->where('class', $selectedStudentClass);
            })
            ->whereNotNull('section')
            ->select('section')
            ->distinct()
            ->orderBy('section')
            ->pluck('section')
            ->map(fn ($section) => trim((string) $section))
            ->filter()
            ->unique()
            ->values();

        $achievements = StudentAchievement::with('student')
            ->when($currentInstitute, function ($query) use ($currentInstitute, $selectedStudentClass, $selectedStudentSection) {
                $query->whereHas('student', function ($q) use ($currentInstitute, $selectedStudentClass, $selectedStudentSection) {
                    $q->where('institute', $currentInstitute);

                    if ($selectedStudentClass) {
                        $q->where('class', $selectedStudentClass);
                    }

                    if ($selectedStudentSection) {
                        $q->where('section', $selectedStudentSection);
                    }
                });
            })
            ->latest()
            ->paginate(30)
            ->withQueryString();

        return view('admin-achievements', compact(
            'achievements',
            'currentInstitute',
            'selectedStudentClass',
            'selectedStudentSection',
            'instituteOptions',
            'classOptions',
            'sectionOptions',
            'hasFilters'
        ));
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
        $requestedIndex = $requestedClass ? $classOptions->search($requestedClass) : false;
        $lastPage = $classOptions->count();
        $currentPage = $requestedIndex !== false
            ? $requestedIndex + 1
            : min(max((int) $request->input('class_page', 1), 1), $lastPage);

        $selectedClass = $classOptions->get($currentPage - 1);
        $previousClass = $currentPage > 1 ? $classOptions->get($currentPage - 2) : null;
        $nextClass = $currentPage < $lastPage ? $classOptions->get($currentPage) : null;
        $query = $request->except(['class_page', 'student_class', 'student_section_page', 'student_section', 'page']);

        return [
            'selectedClass' => $selectedClass,
            'sectionPager' => [
                'current_label' => 'Class ' . $selectedClass,
                'current_page' => $currentPage,
                'last_page' => $lastPage,
                'previous_label' => $previousClass ? 'Class ' . $previousClass : null,
                'next_label' => $nextClass ? 'Class ' . $nextClass : null,
                'previous_url' => $previousClass
                    ? route($routeName, array_merge($query, [
                        'class_page' => $currentPage - 1,
                        'student_class' => $previousClass,
                    ]))
                    : null,
                'next_url' => $nextClass
                    ? route($routeName, array_merge($query, [
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

    public function approve($id)
    {
        $achievement = StudentAchievement::with('student')->findOrFail($id);

        $achievement->update([
            'verification_status' => 'Approved'
        ]);

        $this->syncStudentAchievementToCommunity($achievement->refresh());

        return redirect()->back()
            ->with('success', 'Achievement approved successfully');
    }
    public function reject($id)
    {
        $achievement = StudentAchievement::findOrFail($id);

        $achievement->update([
            'verification_status' => 'Rejected'
        ]);

        $this->deleteCommunitySource('StudentAchievement', $achievement->id);

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

            if ($achievement->certificate_file &&
                Storage::disk('public')->exists($achievement->certificate_file)) {

                Storage::disk('public')->delete($achievement->certificate_file);
            }

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

        if ($achievement->certificate_file &&
            Storage::disk('public')->exists($achievement->certificate_file)) {

            Storage::disk('public')->delete($achievement->certificate_file);
        }

        $achievement->delete(); 

        return redirect()
            ->route('student.badges')
            ->with('success', 'Achievement deleted successfully.');
    }
}
