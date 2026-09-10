<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\MySpace;
use App\Models\SchoolClass;
use App\Models\Student;
use App\Models\Institute;
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
        $currentInstitute = session('user_role') == 'InstituteAdmin'
            ? session('user_institute')
            : ($request->filled('institute') ? trim((string) $request->input('institute')) : null);
        $selectedStudentClass = $request->filled('student_class')
            ? trim((string) $request->input('student_class'))
            : null;
        $selectedStudentSection = $request->filled('student_section')
            ? trim((string) $request->input('student_section'))
            : null;
        $selectedItemStatus = $request->filled('status')
            ? trim((string) $request->input('status'))
            : null;
        $hasFilters = session('user_role') == 'InstituteAdmin'
            || $request->filled('institute')
            || $request->filled('student_class')
            || $request->filled('student_section')
            || $request->filled('status');
        $instituteOptions = session('user_role') == 'Admin'
            ? Institute::where('status', 1)->orderBy('institute_name')->pluck('institute_name')
            : collect([session('user_institute')]);
        $classOptions = collect();
        $sectionOptions = collect();

        if ($submitterType === 'Student') {
            $classQuery = Student::query();

            if ($currentInstitute) {
                $classQuery->where('institute', $currentInstitute);
            }

            $classOptions = $classQuery
                ->whereNotNull('class')
                ->select('class')
                ->distinct()
                ->orderBy('class')
                ->pluck('class')
                ->map(fn ($className) => trim((string) $className))
                ->filter()
                ->unique()
                ->values();

            $sectionQuery = Student::query();

            if ($currentInstitute) {
                $sectionQuery->where('institute', $currentInstitute);
            }

            if ($selectedStudentClass) {
                $sectionQuery->where('class', $selectedStudentClass);
            }

            $sectionOptions = $sectionQuery
                ->whereNotNull('section')
                ->select('section')
                ->distinct()
                ->orderBy('section')
                ->pluck('section')
                ->map(fn ($section) => trim((string) $section))
                ->filter()
                ->unique()
                ->values();
        }

        $items = MySpace::when($submitterType, function ($query) use ($submitterType) {
                $query->where('created_by_type', $submitterType);
            })
            ->when($selectedItemStatus, function ($query) use ($selectedItemStatus) {
                $query->where('status', $selectedItemStatus);
            })
            ->when($submitterType === 'Teacher' && $currentInstitute, function ($query) use ($currentInstitute) {
                $query->whereIn('created_by_id', User::where('institute', $currentInstitute)->pluck('id'));
            })
            ->when($submitterType === 'Student' && $currentInstitute, function ($query) use ($currentInstitute) {
                $query->whereIn('created_by_id', Student::where('institute', $currentInstitute)->pluck('id'));
            })
            ->when($submitterType === 'Student' && $selectedStudentClass, function ($query) use ($selectedStudentClass) {
                $query->whereIn('created_by_id', Student::where('class', $selectedStudentClass)->pluck('id'));
            })
            ->when($submitterType === 'Student' && $selectedStudentSection, function ($query) use ($selectedStudentSection) {
                $query->whereIn('created_by_id', Student::where('section', $selectedStudentSection)->pluck('id'));
            })
            ->latest()
            ->paginate(30)
            ->withQueryString();

        return view('my-space.admin-index', compact(
            'items',
            'submitterType',
            'currentInstitute',
            'selectedStudentClass',
            'selectedStudentSection',
            'selectedItemStatus',
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

        \App\Services\ApprovalTransition::apply($item, 'status', [
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

        \App\Services\ApprovalTransition::apply($item, 'status', [
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

        \App\Services\ApprovalTransition::apply($item, 'status', [
            'status' => 'Featured',
        ], feature: true);

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
