<?php

namespace App\Services;

use App\Models\ClassContentSession;
use App\Models\User;
use Carbon\Carbon;

class ClassSessionLifecycle
{
    public function expire(User $teacher): void
    {
        ClassContentSession::where('stem_engineer_id', $teacher->id)->where('institute', $teacher->institute)
            ->where('status', 'in_progress')->where('started_at', '<=', now()->subMinutes(50))
            ->get()->each(function ($session) {
                $end = Carbon::parse($session->started_at)->addMinutes(50);
                $session->update(['ended_at' => $end, 'end_time' => $end->format('H:i:s'), 'duration_seconds' => 3000,
                    'status' => 'partially_completed', 'delivered_content_id' => $session->content_id,
                    'delivered_topic' => $session->delivered_topic ?: $session->planned_topic,
                    'remarks' => trim($session->remarks."\nSystem note: Session automatically ended after reaching the 50 minute maximum window.")]);
            });
    }
}
