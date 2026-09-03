<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\{AssessmentSession, ClassContentSession, Student, User};
use App\Services\{MobileAssessmentService, ClassSessionLifecycle};
use Illuminate\Http\Request;
use Illuminate\Support\Facades\{Cache, DB};

class MobileSessionController extends Controller
{
    public function engineerState(Request $request)
    {
        $teacher = $request->user();
        abort_unless($teacher instanceof User && in_array($teacher->role, ['Teacher', 'STEM Engineer'], true), 403);
        app(ClassSessionLifecycle::class)->expire($teacher);
        $query = ClassContentSession::where('stem_engineer_id', $teacher->id)->where('institute', $teacher->institute);
        $active = (clone $query)->where('status', 'in_progress')->first();
        $history = $query->when($request->filled('item_id'), fn ($q) => $q->where('teaching_plan_item_id', $request->integer('item_id')))->latest()->limit(20)->get();
        $payload = fn ($s) => ['session_id' => $s->id, 'item_id' => $s->teaching_plan_item_id, 'content_id' => $s->content_id,
            'title' => $s->planned_topic, 'status' => $s->status, 'started_at' => $s->started_at?->toIso8601String(), 'ended_at' => $s->ended_at?->toIso8601String(),
            'duration_seconds' => $s->duration_seconds, 'remarks' => $s->remarks];
        return response()->json(['server_time' => now()->toIso8601String(), 'active' => $active ? $payload($active) : null, 'history' => $history->map($payload)]);
    }

    private function ownedAssessment(Request $request, int $id): AssessmentSession
    {
        abort_unless($request->user() instanceof Student, 403);
        return AssessmentSession::with('assessment')->where('user_type', 'Student')->where('user_id', $request->user()->id)->findOrFail($id);
    }

    public function assessmentState(Request $request, int $id, MobileAssessmentService $service)
    {
        $session = $this->ownedAssessment($request, $id);
        if ($session->status === 'Started' && $service->deadline($session)->lte(now())) $service->submit($session, null, true);
        $session->refresh();
        return response()->json(['session_id' => $session->id, 'status' => $session->status, 'server_time' => now()->toIso8601String(),
            'deadline' => $service->deadline($session)->toIso8601String(), 'violations' => (int) $session->violation_count,
            'answer_text' => Cache::get($service->draftKey($session), '')]);
    }

    public function saveDraft(Request $request, int $id, MobileAssessmentService $service)
    {
        $session = $this->ownedAssessment($request, $id);
        $data = $request->validate(['answer_text' => 'nullable|string|max:200000']);
        abort_unless($session->status === 'Started' && $service->deadline($session)->gt(now()) && (int) $session->violation_count < 3, 409, 'The assessment has ended.');
        Cache::put($service->draftKey($session), $data['answer_text'] ?? '', now()->addDay());
        return response()->json(['success' => true]);
    }

    public function violation(Request $request, int $id, MobileAssessmentService $service)
    {
        $owned = $this->ownedAssessment($request, $id);
        return DB::transaction(function () use ($owned, $service, $request) {
            $session = AssessmentSession::lockForUpdate()->findOrFail($owned->id);
            if ($session->status === 'Started') {
                $request->validate(['answer_text' => 'nullable|string|max:200000']);
                if ($service->deadline($session)->gt(now())) Cache::put($service->draftKey($session), $request->input('answer_text', ''), now()->addDay());
                $session->update(['violation_count' => (int) $session->violation_count + 1, 'last_violation_at' => now()]);
                if ((int) $session->violation_count >= 3) $service->submit($session, null, true);
            }
            return response()->json(['violation_count' => (int) $session->violation_count, 'auto_submit' => (int) $session->violation_count >= 3]);
        });
    }
}
