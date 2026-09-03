<?php

namespace App\Services;

use App\Models\Content;
use App\Models\LmsNotification;
use App\Models\LmsNotificationLoginView;
use App\Models\TeachingPlanItem;
use App\Models\TeachingPlanWeek;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

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

    public function notifyTeachersOfAdminCompletedTopic(Content $content, TeachingPlanItem $item): void
    {
        $item->loadMissing('plan.course');
        $plan = $item->plan;

        if (!$plan || !$plan->institute || !$content->content_title) {
            return;
        }

        $classLabel = trim(($plan->class ?? '') . ' ' . ($plan->section ?? ''));
        $courseTitle = $plan->course->course_title ?? 'Teaching Plan';

        LmsNotification::firstOrCreate(
            [
                'title' => 'Topic completed by Admin',
                'message' => ($classLabel ?: 'Your class') . ': ' . $content->content_title . ' was marked completed by Admin in ' . $courseTitle . '.',
                'target' => 'teachers',
                'institute' => $plan->institute,
                'notification_type' => 'admin_topic_complete',
            ],
            [
                'status' => 'active',
                'starts_at' => now()->toDateString(),
                'expires_at' => null,
                'login_display_limit' => 2,
                'created_by' => null,
            ]
        );
    }

    /**
     * Reserve limited notifications at successful login so a browser refresh
     * cannot consume another display or bypass the two-login limit.
     */
    public function reserveTeacherLoginNotifications(User $teacher): array
    {
        if (!Schema::hasTable('lms_notifications') || !Schema::hasTable('lms_notification_login_views')) {
            return [];
        }

        $today = now()->toDateString();

        return DB::transaction(function () use ($teacher, $today) {
            $notifications = LmsNotification::query()
                ->where('status', 'active')
                ->where('target', 'teachers')
                ->where('institute', $teacher->institute)
                ->where('notification_type', 'admin_topic_complete')
                ->where('login_display_limit', '>', 0)
                ->where(function ($query) use ($today) {
                    $query->whereNull('starts_at')->orWhereDate('starts_at', '<=', $today);
                })
                ->where(function ($query) use ($today) {
                    $query->whereNull('expires_at')->orWhereDate('expires_at', '>=', $today);
                })
                ->lockForUpdate()
                ->get();

            $deliveries = [];

            foreach ($notifications as $notification) {
                $view = LmsNotificationLoginView::query()
                    ->where('notification_id', $notification->id)
                    ->where('user_id', $teacher->id)
                    ->lockForUpdate()
                    ->first();

                if (!$view) {
                    $view = LmsNotificationLoginView::create([
                        'notification_id' => $notification->id,
                        'user_id' => $teacher->id,
                        'viewer_role' => 'Teacher',
                        'institute' => $teacher->institute,
                        'display_count' => 0,
                    ]);
                }

                if ($view->display_count >= $notification->login_display_limit) {
                    continue;
                }

                $view->update([
                    'display_count' => $view->display_count + 1,
                    'last_displayed_at' => now(),
                ]);

                $deliveries[$notification->id] = $view->display_count + 1;
            }

            return $deliveries;
        });
    }

    public function mobileLoginNotifications($account): array
    {
        if (!Schema::hasTable('lms_notifications')) return [];
        $teacher = $account instanceof User && in_array($account->role, ['Teacher', 'STEM Engineer'], true);
        if (!$teacher && !($account instanceof \App\Models\Student)) return [];
        $deliveries = $teacher ? $this->reserveTeacherLoginNotifications($account) : [];
        $audience = $teacher ? 'teachers' : 'students';
        $today = now()->toDateString();
        return LmsNotification::where('status', 'active')->whereIn('target', ['all', $audience])
            ->where(fn ($q) => $q->whereNull('institute')->when($account->institute, fn ($q) => $q->orWhere('institute', $account->institute)))
            ->where(fn ($q) => $q->whereNull('starts_at')->orWhereDate('starts_at', '<=', $today))
            ->where(fn ($q) => $q->whereNull('expires_at')->orWhereDate('expires_at', '>=', $today))
            ->when($teacher, fn ($q) => $q->where(fn ($q) => $q->whereNull('notification_type')
                ->orWhere('notification_type', '!=', 'admin_topic_complete')->orWhereIn('id', array_keys($deliveries))))
            ->latest()->get()->map(fn ($n) => [
                'id' => $n->id, 'title' => $n->title, 'message' => $n->message,
                'signature' => $n->notification_type === 'admin_topic_complete' && isset($deliveries[$n->id])
                    ? 'admin-topic-complete-'.$n->id.'-'.$deliveries[$n->id]
                    : $n->id.'-'.$n->updated_at?->timestamp,
            ])->all();
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
