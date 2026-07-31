<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\MySpace;
use App\Models\SchoolClass;
use App\Models\Student;
use App\Models\User;
use App\Support\SyncsCommunityPosts;
use App\Support\BuildsInstituteSectionPager;
use Illuminate\Support\Facades\Storage;

class MySpaceController extends Controller
{
    use BuildsInstituteSectionPager, SyncsCommunityPosts;

    public function index()
    {
        if (session('user_role') == 'Teacher') {

            $items = MySpace::where('created_by_type', 'Teacher')
                ->where('created_by_id', session('user_id'))
                ->latest()
                ->get();

        } else {

            $items = MySpace::where('created_by_type', 'Student')
                ->where('created_by_id', session('student_id'))
                ->latest()
                ->get();

        }

        return view('my-space.index', compact('items'));
    }

    public function create()
    {
        return view('my-space.create');
    }

    public function store(Request $request)
    {
        $request->validate([
            'title' => 'required|max:255',
            'description' => 'required',
            'type' => 'required|in:Idea,Project',
        ]);

        $data = [
            'title' => $request->title,
            'description' => $request->description,
            'type' => $request->type,
            'created_by_type' => session('user_role') ?? 'Student',
            'created_by_id' => session('user_id') ?? session('student_id'),
            'status' => 'Pending',
        ];

        if ($request->type == 'Idea') {

            $request->validate([
                'blueprint_pdf' => 'required|mimes:pdf|max:5120',
            ]);

            $data['blueprint_pdf'] = $request
                ->file('blueprint_pdf')
                ->store('my-space-blueprints', 'public');
        }

        if ($request->type == 'Project') {

            $request->validate([
                'repository_link' => 'required|url',
            ]);

            $data['repository_link'] = $request->repository_link;
        }

        MySpace::create($data);

        if (session('user_role') == 'Teacher') {
            return redirect()
                ->route('teacher.my-space')
                ->with('success', 'Submission created successfully.');

        }

        return redirect()
            ->route('student.my-space')
            ->with('success', 'Submission created successfully.');
    }

    public function adminIndex(Request $request)
    {
        $submitterType = $request->route('submitterType') ?? $request->query('submitter');
        $sectionPager = null;
        $classSectionPager = null;
        $studentSectionPager = null;
        $currentInstitute = null;
        $selectedStudentClass = null;
        $selectedStudentSection = null;
        $scopedStudentIds = null;
        $scopedTeacherIds = null;

        if (session('user_role') == 'Admin') {
            ['currentInstitute' => $currentInstitute, 'sectionPager' => $sectionPager] =
                $this->buildInstituteSectionPager($request, $request->route()?->getName() ?: 'admin.my-space');
        }

        if (session('user_role') == 'InstituteAdmin') {
            $currentInstitute = session('user_institute');
        }

        if ($submitterType == 'Student' && $currentInstitute) {
            ['selectedClass' => $selectedStudentClass, 'sectionPager' => $classSectionPager] =
                $this->buildStudentClassPager($request, $currentInstitute, $request->route()?->getName() ?: 'admin.my-space');

            ['selectedSection' => $selectedStudentSection, 'sectionPager' => $studentSectionPager] =
                $this->buildStudentSectionPager($request, $currentInstitute, $selectedStudentClass, $request->route()?->getName() ?: 'admin.my-space');
        }

        if (in_array(session('user_role'), ['Admin', 'InstituteAdmin'], true) && $currentInstitute) {
            $studentQuery = Student::where('institute', $currentInstitute);

            if ($selectedStudentClass) {
                $studentQuery->where('class', $selectedStudentClass);
            }

            if ($selectedStudentSection) {
                $studentQuery->where('section', $selectedStudentSection);
            }

            $scopedStudentIds = $studentQuery->pluck('id');

            $scopedTeacherIds = User::where('role', 'Teacher')
                ->where('institute', $currentInstitute)
                ->pluck('id');
        }

        $items = MySpace::when(in_array($submitterType, ['Teacher', 'Student'], true), function ($query) use ($submitterType) {
                $query->where('created_by_type', $submitterType);
            })
            ->when($scopedStudentIds !== null || $scopedTeacherIds !== null, function ($query) use ($submitterType, $scopedStudentIds, $scopedTeacherIds) {
                if ($submitterType == 'Student') {
                    $query->where('created_by_type', 'Student')
                        ->whereIn('created_by_id', $scopedStudentIds ?? collect());

                    return;
                }

                if ($submitterType == 'Teacher') {
                    $query->where('created_by_type', 'Teacher')
                        ->whereIn('created_by_id', $scopedTeacherIds ?? collect());

                    return;
                }

                $query->where(function ($q) use ($scopedStudentIds, $scopedTeacherIds) {
                    $q->where(function ($sub) use ($scopedStudentIds) {
                        $sub->where('created_by_type', 'Student')
                            ->whereIn('created_by_id', $scopedStudentIds ?? collect());
                    })
                    ->orWhere(function ($sub) use ($scopedTeacherIds) {
                        $sub->where('created_by_type', 'Teacher')
                            ->whereIn('created_by_id', $scopedTeacherIds ?? collect());
                    });
                });
            })
            ->latest()
            ->paginate(30)
            ->withQueryString();

        return view('my-space.admin-index', compact(
            'items',
            'submitterType',
            'sectionPager',
            'classSectionPager',
            'studentSectionPager',
            'currentInstitute',
            'selectedStudentClass',
            'selectedStudentSection'
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
        $requestedIndex = $requestedClass
            ? $classOptions->search($requestedClass)
            : false;

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

    private function authorizeAdminAccess($item)
    {
        if (session('user_role') != 'InstituteAdmin') {
            return;
        }

        $submitter = $item->submitter();

        if (!$submitter || $submitter->institute != session('user_institute')) {
            abort(403, 'Unauthorized action.');
        }
    }
    public function approve($id)
    {
        $item = MySpace::findOrFail($id);
        $this->authorizeAdminAccess($item);

        $item->update([
            'status' => 'Approved',
        ]);

        $this->syncMySpaceToCommunity($item->refresh());

        return redirect()->back()
            ->with('success', 'Submission approved successfully.');
    }

    public function reject($id)
    {
        $item = MySpace::findOrFail($id);
        $this->authorizeAdminAccess($item);

        $item->update([
            'status' => 'Rejected',
        ]);

        $this->deleteCommunitySource('MySpace', $item->id);

        return redirect()->back()
            ->with('success', 'Submission rejected successfully.');
    }

    public function feature($id)
    {
        $item = MySpace::findOrFail($id);
        $this->authorizeAdminAccess($item);

        $item->update([
            'status' => 'Featured',
        ]);

        $this->syncMySpaceToCommunity($item->refresh());

        return redirect()->back()
            ->with('success', 'Submission marked as featured.');
    }

    public function show($id)
    {
        $item = MySpace::findOrFail($id);
        $this->authorizeAdminAccess($item);

        $submitter = $item->submitter();

        return view(
            'my-space.show',
            compact(
                'item',
                'submitter'
            )
        );
    }

    public function edit($id)
    {
        $item = MySpace::findOrFail($id);

        $this->authorizeOwner($item);

        if (in_array($item->status, ['Approved', 'Featured'])) {
            return redirect()->back()
                ->with('error', 'Approved or featured submissions cannot be edited.');
        }

        return view('my-space.edit', compact('item'));
    }

    public function update(Request $request, $id)
    {
        $item = MySpace::findOrFail($id);

        $this->authorizeOwner($item);

        if (in_array($item->status, ['Approved', 'Featured'])) {
            return redirect()->back()
                ->with('error', 'Approved or featured submissions cannot be edited.');
        }

        $request->validate([
            'title' => 'required|max:255',
            'description' => 'required',
            'type' => 'required|in:Idea,Project',
        ]);

        $data = [
            'title' => $request->title,
            'description' => $request->description,
            'type' => $request->type,
        ];

        if ($request->type == 'Idea') {
            if ($request->hasFile('blueprint_pdf')) {
                $request->validate([
                    'blueprint_pdf' => 'mimes:pdf|max:5120',
                ]);

                $data['blueprint_pdf'] = $request
                    ->file('blueprint_pdf')
                    ->store('my-space-blueprints', 'public');

                $data['repository_link'] = null;
            }
        }

        if ($request->type == 'Project') {
            $request->validate([
                'repository_link' => 'required|url',
            ]);

            $data['repository_link'] = $request->repository_link;
            $data['blueprint_pdf'] = null;
        }

        $item->update($data);

        if (session('user_role') == 'Teacher') {
            return redirect()->route('teacher.my-space')
                ->with('success', 'Submission updated successfully.');
        }

        return redirect()->route('student.my-space')
            ->with('success', 'Submission updated successfully.');
    }

    public function delete($id)
    {
        $item = MySpace::findOrFail($id);

        $this->authorizeOwner($item);

        if (in_array($item->status, ['Approved', 'Featured'])) {
            return redirect()->back()
                ->with('error', 'Approved or featured submissions cannot be deleted.');
        }

        if ($item->blueprint_pdf) {
            foreach (['public', 'local'] as $disk) {
                if (Storage::disk($disk)->exists($item->blueprint_pdf)) {
                    Storage::disk($disk)->delete($item->blueprint_pdf);
                }
            }
        }

        $item->delete();

        if (session('user_role') == 'Teacher') {
            return redirect()->route('teacher.my-space')
                ->with('success', 'Submission deleted successfully.');
        }

        return redirect()->route('student.my-space')
            ->with('success', 'Submission deleted successfully.');
    }

    private function authorizeOwner($item)
    {
        $currentType = session('user_role') == 'Teacher' ? 'Teacher' : 'Student';
        $currentId = session('user_role') == 'Teacher'
            ? session('user_id')
            : session('student_id');

        if ($item->created_by_type !== $currentType || $item->created_by_id != $currentId) {
            abort(403, 'Unauthorized action.');
        }
    }
}
