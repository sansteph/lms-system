<?php

namespace App\Http\Controllers;

use App\Models\CommunityPost;
use App\Models\CommunityPostLike;
use App\Models\Student;
use App\Models\User;
use App\Models\UserSession;
use App\Support\SyncsCommunityPosts;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;

class CommunityFeedController extends Controller
{
    use SyncsCommunityPosts;

    public function blogsEntry()
    {
        if (!session('student_id') && !session('user_id')) {
            return redirect()->route('blogs.login');
        }

        return $this->index(request());
    }

    public function blogsLogin()
    {
        if (session('student_id') || session('user_id')) {
            return redirect()->route('blogs.community-feed');
        }

        return view('community-feed.login');
    }

    public function blogsLoginSubmit(Request $request)
    {
        $request->validate([
            'login' => 'required|string|max:255',
            'password' => 'required|string',
        ]);

        $login = trim($request->login);

        $user = User::where(function ($query) use ($login) {
                $query->where('email', $login)
                    ->orWhere('user_id', $login);
            })
            ->whereIn('role', ['Admin', 'InstituteAdmin', 'Teacher'])
            ->where('status', 1)
            ->get()
            ->first(fn ($user) => Hash::check($request->password, $user->password));

        if ($user) {
            session()->forget(['student_id', 'student_name', 'student_code']);
            session([
                'user_id' => $user->id,
                'user_name' => $user->name,
                'user_role' => $user->role,
                'user_institute' => $user->institute,
                'password_changed_at' => $user->password_changed_at,
            ]);

            $this->trackBlogLogin($request, $user->role, $user->id);

            return redirect()->route('blogs.community-feed');
        }

        $student = Student::where(function ($query) use ($login) {
                $query->where('student_id', $login)
                    ->orWhere('email', $login);
            })
            ->where('status', 1)
            ->get()
            ->first(fn ($student) => Hash::check($request->password, $student->password));

        if ($student) {
            session()->forget(['user_id', 'user_name', 'user_role', 'user_institute', 'password_changed_at']);
            session([
                'student_id' => $student->id,
                'student_name' => $student->name,
                'student_code' => $student->student_id,
            ]);

            $this->trackBlogLogin($request, 'Student', $student->id);

            return redirect()->route('blogs.community-feed');
        }

        return redirect()
            ->back()
            ->withInput($request->only('login'))
            ->with('error', 'Invalid Blogs login details.');
    }

    public function index(Request $request)
    {
        if (!session('student_id') && !session('user_id')) {
            return redirect()->route('blogs.login');
        }

        $actor = $this->currentActor();
        $status = $request->query('status');
        $type = $request->query('type');

        $posts = CommunityPost::with('likes')
            ->when($type, fn ($query) => $query->where('post_type', $type))
            ->when($this->canModerateStudentPosts($actor) && in_array($status, ['Pending', 'Approved', 'Rejected'], true), function ($query) use ($status) {
                $query->where('status', $status);
            })
            ->when(!$this->canModerateStudentPosts($actor), function ($query) use ($actor) {
                $query->where(function ($visibility) use ($actor) {
                    $visibility->where(function ($approved) use ($actor) {
                        $approved->where('status', 'Approved')
                            ->where(function ($scope) use ($actor) {
                                $scope->where('institute', $actor['institute'])
                                    ->orWhereNull('institute');
                            });
                    })
                    ->orWhere(function ($own) use ($actor) {
                        $own->where('author_type', $actor['type'])
                            ->where('author_id', $actor['id']);
                    });
                });
            })
            ->when($actor['type'] === 'Teacher', function ($query) use ($actor) {
                $query->where(function ($visibility) use ($actor) {
                    $visibility->where(function ($approved) use ($actor) {
                        $approved->where('status', 'Approved')
                            ->where(function ($scope) use ($actor) {
                                $scope->where('institute', $actor['institute'])
                                    ->orWhereNull('institute');
                            });
                    })
                    ->orWhere(function ($pendingStudent) use ($actor) {
                        $pendingStudent->where('status', 'Pending')
                            ->where('author_type', 'Student')
                            ->where('institute', $actor['institute']);
                    })
                    ->orWhere(function ($own) use ($actor) {
                        $own->where('author_type', $actor['type'])
                            ->where('author_id', $actor['id']);
                    });
                });
            })
            ->when($this->isInstituteAdmin($actor), fn ($query) => $query->where('institute', $actor['institute']))
            ->latest()
            ->paginate(10)
            ->withQueryString();

        $pendingCount = $this->canModerateStudentPosts($actor)
            ? CommunityPost::when(!$this->isSuperAdmin($actor), fn ($query) => $query->where('institute', $actor['institute']))
                ->when($actor['type'] === 'Teacher', fn ($query) => $query->where('author_type', 'Student'))
                ->where('status', 'Pending')
                ->count()
            : 0;

        return view('community-feed.index', [
            'posts' => $posts,
            'actor' => $actor,
            'pendingCount' => $pendingCount,
            'postTypes' => $this->postTypes(),
            'isBlogsModule' => $request->routeIs('blogs*'),
        ]);
    }

    public function store(Request $request)
    {
        $actor = $this->currentActor();

        $data = $request->validate([
            'post_type' => 'required|in:' . implode(',', $this->postTypes()),
            'title' => 'required|string|max:255',
            'body' => 'required|string|max:3000',
            'image' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:4096',
            'attachment' => 'nullable|file|mimes:pdf,doc,docx,ppt,pptx,xls,xlsx,txt|max:10240',
        ]);

        $status = $actor['type'] === 'Student' ? 'Pending' : 'Approved';

        $postData = [
            'post_type' => $data['post_type'],
            'title' => $data['title'],
            'body' => $data['body'],
            'author_type' => $actor['type'],
            'author_id' => $actor['id'],
            'institute' => $actor['institute'],
            'status' => $status,
            'published_at' => $status === 'Approved' ? now() : null,
            'approved_by' => $status === 'Approved' && session('user_id') ? session('user_id') : null,
            'approved_at' => $status === 'Approved' ? now() : null,
        ];

        if ($request->hasFile('image')) {
            $postData['image_path'] = $request->file('image')->store('community-feed/images', 'public');
        }

        if ($request->hasFile('attachment')) {
            $postData['attachment_path'] = $request->file('attachment')->store('community-feed/attachments', 'public');
            $postData['attachment_original_name'] = $request->file('attachment')->getClientOriginalName();
        }

        CommunityPost::create($postData);

        return redirect()
            ->route($this->routePrefix($actor, $request) . '.community-feed')
            ->with('success', $status === 'Approved'
                ? 'Post published successfully.'
                : 'Post submitted for approval.');
    }

    public function approve(int $id)
    {
        $actor = $this->currentActor();

        $post = CommunityPost::findOrFail($id);
        $this->authorizeModeration($post, $actor);

        $post->update([
            'status' => 'Approved',
            'published_at' => $post->published_at ?: now(),
            'approved_by' => session('user_id'),
            'approved_at' => now(),
            'rejected_at' => null,
        ]);

        return redirect()->back()->with('success', 'Post approved successfully.');
    }

    public function reject(int $id)
    {
        $actor = $this->currentActor();

        $post = CommunityPost::findOrFail($id);
        $this->authorizeModeration($post, $actor);

        $post->update([
            'status' => 'Rejected',
            'rejected_at' => now(),
        ]);

        return redirect()->back()->with('success', 'Post rejected.');
    }

    public function toggleLike(int $id)
    {
        $actor = $this->currentActor();
        $post = CommunityPost::findOrFail($id);

        if (!$this->canViewPost($post, $actor) || $post->status !== 'Approved') {
            abort(403, 'You cannot react to this post.');
        }

        $like = CommunityPostLike::where('community_post_id', $post->id)
            ->where('liker_type', $actor['type'])
            ->where('liker_id', $actor['id'])
            ->first();

        if ($like) {
            $like->delete();
        } else {
            CommunityPostLike::create([
                'community_post_id' => $post->id,
                'liker_type' => $actor['type'],
                'liker_id' => $actor['id'],
            ]);
        }

        return redirect()->back();
    }

    public function delete(int $id)
    {
        $actor = $this->currentActor();
        $post = CommunityPost::findOrFail($id);

        if (!$this->isAdmin($actor)) {
            $isOwner = $post->author_type === $actor['type'] && (int) $post->author_id === $actor['id'];

            if (!$isOwner || $post->status === 'Approved') {
                abort(403, 'You cannot delete this post.');
            }
        } else {
            $this->authorizeAdminScope($post, $actor);
        }

        if (!$post->isSystemSynced()) {
            $this->deleteFiles($post);
        }

        $post->delete();

        return redirect()->back()->with('success', 'Post deleted successfully.');
    }

    private function currentActor(): array
    {
        if (session('student_id')) {
            $student = Student::findOrFail(session('student_id'));

            return [
                'type' => 'Student',
                'id' => $student->id,
                'name' => $student->name,
                'institute' => $student->institute,
            ];
        }

        $user = User::findOrFail(session('user_id'));

        return [
            'type' => $user->role,
            'id' => $user->id,
            'name' => $user->name,
            'institute' => $user->institute,
        ];
    }

    private function canViewPost(CommunityPost $post, array $actor): bool
    {
        if ($this->isAdmin($actor)) {
            return !$this->isInstituteAdmin($actor) || $post->institute === $actor['institute'];
        }

        $isOwner = $post->author_type === $actor['type'] && (int) $post->author_id === $actor['id'];

        return $isOwner || (
            $post->status === 'Approved' &&
            ($post->institute === $actor['institute'] || $post->institute === null)
        ) || (
            $actor['type'] === 'Teacher' &&
            $post->status === 'Pending' &&
            $post->author_type === 'Student' &&
            $post->institute === $actor['institute']
        );
    }

    private function authorizeAdmin(): void
    {
        if (!in_array(session('user_role'), ['Admin', 'InstituteAdmin'], true)) {
            abort(403, 'Only admins can moderate community posts.');
        }
    }

    private function authorizeModeration(CommunityPost $post, array $actor): void
    {
        if ($this->isAdmin($actor)) {
            $this->authorizeAdminScope($post, $actor);
            return;
        }

        if (
            $actor['type'] === 'Teacher' &&
            $post->status === 'Pending' &&
            $post->author_type === 'Student' &&
            $post->institute === $actor['institute']
        ) {
            return;
        }

        abort(403, 'You cannot moderate this blog post.');
    }

    private function authorizeAdminScope(CommunityPost $post, array $actor): void
    {
        if ($this->isInstituteAdmin($actor) && $post->institute !== $actor['institute']) {
            abort(403, 'You cannot moderate posts from another institute.');
        }
    }

    private function isAdmin(array $actor): bool
    {
        return in_array($actor['type'], ['Admin', 'InstituteAdmin'], true);
    }

    private function isInstituteAdmin(array $actor): bool
    {
        return $actor['type'] === 'InstituteAdmin';
    }

    private function isSuperAdmin(array $actor): bool
    {
        return $actor['type'] === 'Admin';
    }

    private function canModerateStudentPosts(array $actor): bool
    {
        return in_array($actor['type'], ['Admin', 'InstituteAdmin', 'Teacher'], true);
    }

    private function routePrefix(array $actor, ?Request $request = null): string
    {
        if ($request && $request->routeIs('blogs*')) {
            return 'blogs';
        }

        return match ($actor['type']) {
            'Teacher' => 'teacher',
            'Student' => 'student',
            default => 'admin',
        };
    }

    private function trackBlogLogin(Request $request, string $type, int $id): void
    {
        $trackingSessionId = session('tracking_session_id');

        if ($trackingSessionId) {
            $existingSession = UserSession::find($trackingSessionId);

            if ($existingSession && !$existingSession->logout_time) {
                return;
            }
        }

        $session = UserSession::create([
            'user_type' => $type,
            'user_id' => $id,
            'login_time' => now(),
            'ip_address' => $request->ip(),
            'browser' => $request->userAgent(),
        ]);

        session(['tracking_session_id' => $session->id]);
    }

    private function postTypes(): array
    {
        return [
            'Achievement',
            'Project Update',
            'Session Highlight',
            'Announcement',
            'General Post',
            'My Space Idea',
            'My Space Project',
            'Featured My Space',
        ];
    }

    private function deleteFiles(CommunityPost $post): void
    {
        foreach (['image_path', 'attachment_path'] as $field) {
            if ($post->{$field} && Storage::disk('public')->exists($post->{$field})) {
                Storage::disk('public')->delete($post->{$field});
            }
        }
    }
}
