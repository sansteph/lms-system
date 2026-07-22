<?php

namespace App\Services;

use App\Models\Content;
use App\Models\LmsNotification;
use App\Models\TeachingPlanWeek;

class LmsNotificationService
{
    public function notifyTeachersOfReleasedWeek(TeachingPlanWeek $week): void
    {
        $week->loadMissing(['plan.course', 'items.content']);

        $plan = $week->plan;

        if (!$plan || !$plan->institute || $plan->is_template) {
            return;
        }

        $contentTitles = $week->items
            ->pluck('content.content_title')
            ->filter()
            ->unique()
            ->values();

        if ($contentTitles->isEmpty()) {
            return;
        }

        $classLabel = trim(($plan->class ?? '') . ' ' . ($plan->section ?? ''));
        $courseTitle = $plan->course->course_title ?? 'Teaching Plan';
        $title = 'New content released for ' . ($classLabel ?: 'your class');
        $message = $courseTitle . ' - Week ' . $week->week_number . ': ' . $contentTitles->join(', ');

        $this->createOnce([
            'title' => $title,
            'message' => $message,
            'target' => 'teachers',
            'institute' => $plan->institute,
        ]);
    }

    public function notifyStudentsOfReleasedContent(Content $content): void
    {
        if (!$content->institute || !$content->content_title) {
            return;
        }

        $title = 'New learning content available';
        $message = $content->content_title . ' is now available in Learning Content.';

        $this->createOnce([
            'title' => $title,
            'message' => $message,
            'target' => 'students',
            'institute' => $content->institute,
        ]);
    }

    private function createOnce(array $data): void
    {
        LmsNotification::firstOrCreate(
            [
                'title' => $data['title'],
                'message' => $data['message'],
                'target' => $data['target'],
                'institute' => $data['institute'],
            ],
            [
                'status' => 'active',
                'starts_at' => now()->toDateString(),
                'expires_at' => null,
                'created_by' => null,
            ]
        );
    }
}
