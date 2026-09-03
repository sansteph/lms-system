<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Institute;
use App\Models\MySpace;
use App\Models\SchoolClass;
use App\Models\Student;
use App\Models\StudentAchievement;
use App\Models\TeacherAchievement;
use App\Models\User;
use App\Support\SyncsCommunityPosts;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;

class SubmissionController extends Controller
{
    use SyncsCommunityPosts;

    private function owner(Request $request): array
    {
        $account = $request->user();
        $role = $request->route('audience');
        abort_unless(
            ($role === 'student' && $account instanceof Student) ||
            ($role === 'engineer' && $account instanceof User && in_array($account->role, ['Teacher', 'STEM Engineer'], true)),
            403, 'This submission area is not available to your role.'
        );

        return [$role === 'student' ? 'Student' : 'Teacher', $account->id];
    }

    private function query(Request $request, bool $achievement)
    {
        [$type, $id] = $this->owner($request);
        if (! $achievement) {
            return MySpace::where('created_by_type', $type)->where('created_by_id', $id);
        }

        return $type === 'Student'
            ? StudentAchievement::where('student_id', $id)
            : TeacherAchievement::where('user_id', $id);
    }

    public function index(Request $request)
    {
        $achievement = $request->route('kind') === 'achievements';
        $items = $this->query($request, $achievement)->latest()->get();

        return response()->json([
            $achievement ? 'achievements' : 'posts' => $items->map(fn ($item) => $this->payload($item, $achievement)),
        ]);
    }

    private function payload($item, bool $achievement): array
    {
        $status = $achievement ? $item->verification_status : $item->status;
        $kind = $achievement ? ($item instanceof StudentAchievement ? 'student-achievement' : 'engineer-achievement') : 'my-space';
        $path = $achievement ? $item->certificate_file : $item->blueprint_pdf;

        return array_merge($item->only($achievement
            ? ['id', 'title', 'achievement_type', 'organizer', 'description', 'achievement_date', 'position']
            : ['id', 'title', 'description', 'type', 'repository_link']), [
                'status' => $status,
                'can_edit' => $achievement ? ($item instanceof StudentAchievement && $status !== 'Approved') : ! in_array($status, ['Approved', 'Featured'], true),
                'can_delete' => $achievement || ! in_array($status, ['Approved', 'Featured'], true),
                'attachment_url' => $path ? URL::temporarySignedRoute('mobile.submission-file', now()->addMinutes(5), ['kind' => $kind, 'id' => $item->id]) : null,
            ]);
    }

    public function save(Request $request, string $audience, string $kind, ?int $id = null)
    {
        $achievement = $request->route('kind') === 'achievements';
        [$type, $ownerId] = $this->owner($request);
        $item = $id ? $this->query($request, $achievement)->findOrFail($id) : null;
        if ($item) {
            abort_unless($this->payload($item, $achievement)['can_edit'], 403, 'This approved submission cannot be edited.');
        }
        $rules = ['title' => 'required|string|max:255', 'description' => $achievement ? 'nullable|string' : 'required|string'];
        if ($achievement) {
            $rules += [
                'achievement_type' => 'required|string|max:100', 'organizer' => 'nullable|string|max:255',
                'achievement_date' => 'nullable|date', 'position' => 'nullable|string|max:100',
                'certificate_file' => (! $item && $type === 'Student' ? 'required' : 'nullable').'|file|mimes:pdf,jpg,jpeg,png|max:5120',
            ];
        } else {
            $rules += [
                'type' => 'required|in:Idea,Project',
                'repository_link' => 'required_if:type,Project|nullable|url:http,https|max:2048',
                'blueprint_pdf' => ($request->input('type') === 'Idea' && ! $item?->blueprint_pdf ? 'required' : 'nullable').'|file|mimes:pdf|max:5120',
            ];
        }
        $data = $request->validate($rules);
        $field = $achievement ? 'certificate_file' : 'blueprint_pdf';
        unset($data[$field]);
        $oldPath = $item?->{$field};
        $newPath = null;
        try {
            if ($request->hasFile($field) && ($achievement || $data['type'] === 'Idea')) {
                $newPath = $request->file($field)->store($achievement ? 'certificates' : 'my-space-blueprints', 'public');
                $data[$field] = $newPath;
            }
            if (! $achievement) {
                $data['repository_link'] = $data['type'] === 'Project' ? $data['repository_link'] : null;
                if ($data['type'] === 'Project') {
                    $data['blueprint_pdf'] = null;
                }
            }
            $item = DB::transaction(function () use ($item, $data, $achievement, $type, $ownerId) {
                if ($item) {
                    if ($achievement) {
                        $data['verification_status'] = 'Pending';
                    }
                    $item->update($data);

                    return $item;
                }
                if (! $achievement) {
                    return MySpace::create($data + ['created_by_type' => $type, 'created_by_id' => $ownerId, 'status' => 'Pending']);
                }

                return $type === 'Student'
                    ? StudentAchievement::create($data + ['student_id' => $ownerId, 'verification_status' => 'Pending'])
                    : TeacherAchievement::create($data + ['user_id' => $ownerId, 'verification_status' => 'Pending']);
            });
        } catch (\Throwable $error) {
            if ($newPath) {
                Storage::disk('public')->delete($newPath);
            }
            throw $error;
        }
        if ($oldPath && $oldPath !== $item->{$field}) {
            $this->deleteFile($oldPath);
        }

        return response()->json(['message' => 'Submission saved.', 'item' => $this->payload($item, $achievement)], $id ? 200 : 201);
    }

    public function destroy(Request $request, string $audience, string $kind, int $id)
    {
        $achievement = $request->route('kind') === 'achievements';
        $item = $this->query($request, $achievement)->findOrFail($id);
        abort_unless($this->payload($item, $achievement)['can_delete'], 403, 'Approved or featured submissions cannot be deleted.');
        $path = $achievement ? $item->certificate_file : $item->blueprint_pdf;
        if ($achievement) {
            $this->deleteCommunitySource($item instanceof StudentAchievement ? 'StudentAchievement' : 'TeacherAchievement', $item->id);
        }
        $item->delete();
        if ($path) {
            $this->deleteFile($path);
        }

        return response()->json(['message' => 'Submission deleted.']);
    }

    private function deleteFile(string $path): void
    {
        foreach (['local', 'public'] as $disk) {
            Storage::disk($disk)->delete($path);
        }
    }

    private function adminScope(Request $request): ?string
    {
        $user = $request->user();
        abort_unless($user instanceof User && in_array($user->role, ['Admin', 'InstituteAdmin'], true), 403);
        if ($user->role === 'InstituteAdmin') {
            abort_unless(filled($user->institute), 403);

            return $user->institute;
        }

        return $request->input('institute') ?: null;
    }

    private function adminQuery(Request $request, ?string $institute)
    {
        $student = $request->route('audience') === 'student';
        $achievement = $request->route('kind') === 'achievements';
        $owners = $student ? Student::query() : User::whereIn('role', ['Teacher', 'STEM Engineer']);
        $owners->when($institute, fn ($q) => $q->where('institute', $institute));
        if ($student) {
            $owners->when($request->filled('class'), fn ($q) => $q->where('class', $request->input('class')))
                ->when($request->filled('section'), fn ($q) => $q->where('section', $request->input('section')));
        }
        if ($achievement) {
            return $student ? StudentAchievement::whereIn('student_id', $owners->select('id')) : TeacherAchievement::whereIn('user_id', $owners->select('id'));
        }

        return MySpace::where('created_by_type', $student ? 'Student' : 'Teacher')->whereIn('created_by_id', $owners->select('id'));
    }

    public function reviewIndex(Request $request)
    {
        $institute = $this->adminScope($request);
        $achievement = $request->route('kind') === 'achievements';
        $query = $this->adminQuery($request, $institute);
        $query->when($request->filled('status'), fn ($q) => $q->where($achievement ? 'verification_status' : 'status', $request->input('status')));

        return response()->json([
            'institutes' => Institute::query()->when($request->user()->role === 'InstituteAdmin', fn ($q) => $q->where('institute_name', $institute))->orderBy('institute_name')->pluck('institute_name'),
            'classes' => SchoolClass::query()->when($institute, fn ($q) => $q->where('institute', $institute))->get(['class_name', 'section']),
            $achievement ? 'achievements' : 'posts' => $query->latest()->get()->map(function ($item) use ($achievement) {
                $owner = $achievement ? ($item instanceof StudentAchievement ? $item->student : $item->teacher) : $item->submitter();

                return array_merge($this->payload($item, $achievement), ['can_edit' => false, 'can_delete' => false, 'submitter' => $owner?->name, 'institute' => $owner?->institute]);
            }),
        ]);
    }

    public function decide(Request $request, string $audience, string $kind, int $id, string $decision)
    {
        $institute = $this->adminScope($request);
        $achievement = $kind === 'achievements';
        $item = $this->adminQuery($request, $institute)->findOrFail($id);
        abort_unless(in_array($decision, $achievement ? ['approve', 'reject'] : ['approve', 'reject', 'feature'], true), 422);
        $status = ['approve' => 'Approved', 'reject' => 'Rejected', 'feature' => 'Featured'][$decision];
        DB::transaction(function () use ($item, $achievement, $status) {
            $item->update([$achievement ? 'verification_status' : 'status' => $status]);
            if ($item instanceof StudentAchievement) {
                $this->syncStudentAchievementToCommunity($item);
            } elseif ($item instanceof TeacherAchievement) {
                $this->syncTeacherAchievementToCommunity($item);
            } else {
                $this->syncMySpaceToCommunity($item);
            }
        });

        return response()->json(['message' => "Submission {$status}."]);
    }

    public function file(string $kind, int $id)
    {
        $item = match ($kind) {
            'my-space' => MySpace::findOrFail($id),
            'student-achievement' => StudentAchievement::findOrFail($id),
            'engineer-achievement' => TeacherAchievement::findOrFail($id),
            default => abort(404),
        };
        $path = $kind === 'my-space' ? $item->blueprint_pdf : $item->certificate_file;
        abort_unless($path, 404);
        foreach (['local', 'public'] as $disk) {
            if (Storage::disk($disk)->exists($path)) {
                return response()->file(Storage::disk($disk)->path($path), ['Cache-Control' => 'private, no-store', 'X-Content-Type-Options' => 'nosniff']);
            }
        }
        abort(404);
    }
}
