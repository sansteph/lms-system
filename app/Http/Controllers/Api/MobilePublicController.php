<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\{Controller, PageController};
use App\Models\CommunityPost;
use App\Services\Newsroom\NewsroomFeedService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class MobilePublicController extends Controller
{
    public function newsroom(NewsroomFeedService $feed)
    {
        // Public refreshes reuse the website cache, rather than triggering paid AI on every pull.
        return response()->json($feed->feed(false));
    }

    public function community(Request $request)
    {
        $posts = CommunityPost::withExistingAuthor()
            ->where('status', 'Approved')
            ->when($request->filled('search'), fn ($query) => $query->where(function ($scope) use ($request) {
                $scope->where('title', 'like', '%' . $request->string('search') . '%')
                    ->orWhere('body', 'like', '%' . $request->string('search') . '%');
            }))
            ->latest('published_at')
            ->latest('id')
            ->paginate(12);

        return response()->json([
            'posts' => $posts->getCollection()->map(fn (CommunityPost $post) => [
                'id' => $post->id,
                'title' => $post->title,
                'body' => $post->body,
                'type' => $post->post_type,
                'author' => $post->authorName(),
                'author_role' => $post->roleLabel(),
                'published_at' => optional($post->published_at)->toIso8601String(),
                'image' => $post->image_path ? Storage::disk('public')->url($post->image_path) : null,
            ])->values(),
            'pagination' => [
                'page' => $posts->currentPage(),
                'last_page' => $posts->lastPage(),
                'total' => $posts->total(),
            ],
        ]);
    }

    public function verifyCertificate(Request $request)
    {
        $data = app(PageController::class)->verifyCertificateSubmit($request)->getData(true);
        $certificate = $data['certificate'] ?? null;
        $status = !$certificate ? 'failed' : (($data['revoked'] ?? false) ? 'revoked' : (($data['inactive'] ?? false) ? 'pending_approval' : 'verified'));
        $message = match ($status) {
            'verified' => 'Certificate verified successfully.',
            'revoked' => 'This certificate has been revoked.',
            'pending_approval' => 'This certificate exists but is not issued yet.',
            default => 'Certificate not found.',
        };

        return response()->json(['status' => $status, 'message' => $message, 'certificate' => $status !== 'verified' ? null : [
            'code' => $certificate->certificate_code,
            'name' => $certificate->certificate_type === 'Independent' ? $certificate->independentLearner?->name : $certificate->student?->name,
            'student_id' => $certificate->certificate_type === 'Independent' ? null : $certificate->student?->student_id,
            'course' => $certificate->course?->course_title ?? 'Program Completion',
            'certificate_type' => $certificate->certificate_type ?: 'Student',
            'score' => $certificate->final_score ?? $certificate->badge_count,
            'grade' => $certificate->final_grade, 'classification' => $certificate->final_classification,
            'issued_date' => $certificate->issued_date,
        ]]);
    }
}
