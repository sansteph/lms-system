<?php

namespace App\Http\Controllers;

use App\Models\CommunityPost;
use App\Models\CommunityPostComment;
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
        if (session('independent_learner_id')) {
            return redirect()
                ->route('home')
                ->with('error', 'Blogs are available only for Admins, STEM Engineers, and Students.');
        }

        if (!session('student_id') && !session('user_id')) {
            return redirect()->route('blogs.login');
        }

        return $this->index(request());
    }

    public function blogsLogin()
    {
        if (session('independent_learner_id')) {
            return redirect()
                ->route('home')
                ->with('error', 'Blogs are available only for Admins, STEM Engineers, and Students.');
        }

        if (session('student_id') || session('user_id')) {
            return redirect()->route('blogs.community-feed');
        }

        return view('community-feed.login');
    }

    public function blogsLoginSubmit(Request $request)
    {
        if (session('independent_learner_id')) {
            return redirect()
                ->route('home')
                ->with('error', 'Blogs are available only for Admins, STEM Engineers, and Students.');
        }

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
        if (session('independent_learner_id')) {
            return redirect()
                ->route('home')
                ->with('error', 'Blogs are available only for Admins, STEM Engineers, and Students.');
        }

        if (!session('student_id') && !session('user_id')) {
            return redirect()->route('blogs.login');
        }

        $actor = $this->currentActor();
        $tab = $request->query('tab', 'feed');
        $type = $request->query('type');
        $canModerate = $this->canModerateStudentPosts($actor);

        if (!in_array($tab, ['feed', 'profile', 'approvals', 'post'], true)) {
            $tab = 'feed';
        }

        if ($tab === 'approvals' && !$canModerate) {
            abort(403, 'Only Admins and STEM Engineers can approve blog posts.');
        }

        $posts = CommunityPost::withExistingAuthor()
            ->with(['likes', 'comments'])
            ->when($type, fn ($query) => $query->where('post_type', $type))
            ->when($tab === 'feed', function ($query) use ($actor) {
                $query->where('status', 'Approved')
                    ->when(!$this->isSuperAdmin($actor), function ($scope) use ($actor) {
                        $scope->where(function ($visibility) use ($actor) {
                            $visibility->where('institute', $actor['institute'])
                                ->orWhereNull('institute');
                        });
                    });
            })
            ->when($tab === 'approvals', function ($query) use ($actor) {
                $query->where('status', 'Pending')
                    ->when(!$this->isSuperAdmin($actor), fn ($scope) => $scope->where('institute', $actor['institute']))
                    ->when($actor['type'] === 'Teacher', fn ($scope) => $scope->where('author_type', 'Student'));
            })
            ->when($tab === 'profile', function ($query) use ($request, $actor) {
                $profileType = $request->query('profile_type', $actor['type']);
                $profileId = (int) $request->query('profile_id', $actor['id']);

                $query->where('author_type', $profileType)
                    ->where('author_id', $profileId)
                    ->where(function ($visibility) use ($actor, $profileType, $profileId) {
                        $visibility->where('status', 'Approved')
                            ->orWhere(function ($own) use ($actor, $profileType, $profileId) {
                                $own->where('author_type', $actor['type'])
                                    ->where('author_id', $actor['id'])
                                    ->where('author_type', $profileType)
                                    ->where('author_id', $profileId);
                            });
                    });
            })
            ->when($tab === 'post', fn ($query) => $query->whereRaw('1 = 0'))
            ->latest()
            ->paginate(10)
            ->withQueryString();

        $profile = $tab === 'profile'
            ? $this->blogProfile($request->query('profile_type', $actor['type']), (int) $request->query('profile_id', $actor['id']), $actor)
            : $this->blogProfile($actor['type'], $actor['id'], $actor);

        $pendingCount = $canModerate
            ? CommunityPost::withExistingAuthor()
                ->when(!$this->isSuperAdmin($actor), fn ($query) => $query->where('institute', $actor['institute']))
                ->when($actor['type'] === 'Teacher', fn ($query) => $query->where('author_type', 'Student'))
                ->where('status', 'Pending')
                ->count()
            : 0;

        return view('community-feed.index', [
            'posts' => $posts,
            'actor' => $actor,
            'activeTab' => $tab,
            'profile' => $profile,
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

    public function storeComment(Request $request, int $id)
    {
        $actor = $this->currentActor();
        $post = CommunityPost::findOrFail($id);

        if (!$this->canViewPost($post, $actor) || $post->status !== 'Approved') {
            abort(403, 'You cannot comment on this post.');
        }

        $data = $request->validate([
            'body' => 'required|string|max:1200',
        ]);

        CommunityPostComment::create([
            'community_post_id' => $post->id,
            'commenter_type' => $actor['type'],
            'commenter_id' => $actor['id'],
            'body' => $data['body'],
        ]);

        return redirect()->back()->with('success', 'Comment added.');
    }

    public function updateComment(Request $request, int $id)
    {
        $actor = $this->currentActor();
        $comment = CommunityPostComment::with('post')->findOrFail($id);

        if (!$this->ownsComment($comment, $actor) || !$this->canViewPost($comment->post, $actor)) {
            abort(403, 'You cannot edit this comment.');
        }

        $data = $request->validate([
            'body' => 'required|string|max:1200',
        ]);

        $comment->update([
            'body' => $data['body'],
        ]);

        return redirect()->back()->with('success', 'Comment updated.');
    }

    public function deleteComment(int $id)
    {
        $actor = $this->currentActor();
        $comment = CommunityPostComment::with('post')->findOrFail($id);

        if (!$this->ownsComment($comment, $actor) || !$this->canViewPost($comment->post, $actor)) {
            abort(403, 'You cannot delete this comment.');
        }

        $comment->delete();

        return redirect()->back()->with('success', 'Comment deleted.');
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
                'profile_image' => $student->profile_image,
            ];
        }

        $user = User::findOrFail(session('user_id'));

        return [
            'type' => $user->role,
            'id' => $user->id,
            'name' => $user->name,
            'institute' => $user->institute,
            'profile_image' => $user->profile_image,
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

    private function ownsComment(CommunityPostComment $comment, array $actor): bool
    {
        return $comment->commenter_type === $actor['type']
            && (int) $comment->commenter_id === (int) $actor['id'];
    }

    private function blogProfile(?string $type, int $id, array $actor): array
    {
        $type = in_array($type, ['Admin', 'InstituteAdmin', 'Teacher', 'Student'], true) ? $type : $actor['type'];
        $model = $type === 'Student'
            ? Student::find($id)
            : User::where('id', $id)->where('role', $type)->first();

        if (!$model) {
            abort(404, 'Blog profile not found.');
        }

        $profileInstitute = $model->institute ?? null;

        if (!$this->isSuperAdmin($actor) && $profileInstitute && $profileInstitute !== $actor['institute']) {
            abort(403, 'You cannot view a profile from another institute.');
        }

        $approvedPosts = CommunityPost::where('author_type', $type)
            ->where('author_id', $id)
            ->where('status', 'Approved')
            ->count();

        $pendingPosts = $type === $actor['type'] && $id === $actor['id']
            ? CommunityPost::where('author_type', $type)
                ->where('author_id', $id)
                ->where('status', 'Pending')
                ->count()
            : 0;

        $likeCount = CommunityPostLike::whereHas('post', function ($query) use ($type, $id) {
                $query->where('author_type', $type)
                    ->where('author_id', $id)
                    ->where('status', 'Approved');
            })
            ->count();

        return [
            'type' => $type,
            'id' => $id,
            'name' => $model->name,
            'role' => $type === 'Teacher' ? 'STEM Engineer' : ($type === 'InstituteAdmin' ? 'Institute Admin' : $type),
            'institute' => $profileInstitute,
            'class' => $type === 'Student' ? ($model->class ?? null) : null,
            'section' => $type === 'Student' ? ($model->section ?? null) : null,
            'qualification' => $type === 'Teacher' ? ($model->qualification ?? null) : null,
            'designation' => $type !== 'Student' ? ($model->designation ?? null) : null,
            'image' => $type === 'Student' ? null : ($model->profile_image ?? null),
            'approved_posts' => $approvedPosts,
            'pending_posts' => $pendingPosts,
            'likes' => $likeCount,
        ];
    }

    private function routePrefix(array $actor, ?Request $request = null): string
    {
        return 'blogs';
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
