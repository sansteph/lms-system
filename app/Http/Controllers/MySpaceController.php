<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\MySpace;
use Illuminate\Support\Facades\Storage;

class MySpaceController extends Controller
{
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

    public function adminIndex()
    {
        $items = MySpace::when(session('user_role') == 'InstituteAdmin', function ($query) {

                $studentIds = \App\Models\Student::where('institute', session('user_institute'))
                    ->pluck('id');

                $teacherIds = \App\Models\User::where('role', 'Teacher')
                    ->where('institute', session('user_institute'))
                    ->pluck('id');

                $query->where(function ($q) use ($studentIds, $teacherIds) {
                    $q->where(function ($sub) use ($studentIds) {
                        $sub->where('created_by_type', 'Student')
                            ->whereIn('created_by_id', $studentIds);
                    })
                    ->orWhere(function ($sub) use ($teacherIds) {
                        $sub->where('created_by_type', 'Teacher')
                            ->whereIn('created_by_id', $teacherIds);
                    });
                });

            })
            ->latest()
            ->get();

        return view('my-space.admin-index', compact('items'));
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
