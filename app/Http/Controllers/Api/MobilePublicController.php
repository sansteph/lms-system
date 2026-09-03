<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\{Controller, PageController};
use App\Services\Newsroom\NewsroomFeedService;
use Illuminate\Http\Request;

class MobilePublicController extends Controller
{
    public function newsroom(NewsroomFeedService $feed)
    {
        // Public refreshes reuse the website cache, rather than triggering paid AI on every pull.
        return response()->json($feed->feed(false));
    }

    public function verifyCertificate(Request $request)
    {
        $data = app(PageController::class)->verifyCertificateSubmit($request)->getData();
        $certificate = $data['certificate'];
        $status = !$certificate ? 'failed' : ($data['revoked'] ? 'revoked' : ($data['inactive'] ? 'pending_approval' : 'verified'));
        return response()->json(['status' => $status, 'certificate' => $status !== 'verified' ? null : [
            'code' => $certificate->certificate_code,
            'name' => $certificate->certificate_type === 'Independent' ? $certificate->independentLearner?->name : $certificate->student?->name,
            'student_id' => $certificate->certificate_type === 'Independent' ? null : $certificate->student?->student_id,
            'course' => $certificate->course?->course_title ?? 'Program Completion',
            'score' => $certificate->final_score ?? $certificate->badge_count,
            'grade' => $certificate->final_grade, 'classification' => $certificate->final_classification,
            'issued_date' => $certificate->issued_date,
        ]]);
    }
}
