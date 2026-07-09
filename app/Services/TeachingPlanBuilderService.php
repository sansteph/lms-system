<?php

namespace App\Services;

use App\Models\Course;
use App\Models\TeachingPlan;
use App\Models\TeachingPlanItem;
use App\Models\TeachingPlanWeek;
use Carbon\Carbon;

class TeachingPlanBuilderService
{
    public function buildForCourse(
        Course $course,
        array $settings = [],
        ?int $createdBy = null
    ): TeachingPlan {
        $contents = $course->courseContents()
            ->where('status', 'active')
            ->orderBy('sort_order')
            ->get();

        $startDate = Carbon::parse($settings['start_date'] ?? now()->toDateString())->startOfDay();
        $contentsPerWeek = max(1, (int) ($settings['contents_per_week'] ?? 2));

        $plan = TeachingPlan::create([
            'is_template' => (bool) ($settings['is_template'] ?? false),
            'parent_template_id' => $settings['parent_template_id'] ?? null,
            'title' => $settings['title'] ?? $course->course_title,
            'institute' => $course->institute,
            'institute_id' => $settings['institute_id'] ?? null,
            'class' => $settings['class'] ?? $course->assigned_class,
            'section' => $settings['section'] ?? null,
            'course_id' => $course->id,
            'start_date' => ($settings['is_template'] ?? false) ? null : $startDate->toDateString(),
            'plan_start_date' => ($settings['is_template'] ?? false) ? null : $startDate->toDateString(),
            'release_day' => $settings['release_day'] ?? 'Friday',
            'contents_per_week' => $contentsPerWeek,
            'release_policy' => $settings['release_policy'] ?? 'release_next_only_if_previous_completed',
            'status' => $settings['status'] ?? 'active',
            'created_by' => $createdBy,
            'remarks' => $settings['remarks'] ?? null,
        ]);

        $this->createWeeksAndItems($plan, $contents, $startDate, $contentsPerWeek);

        if ($plan->is_template) {
            return $plan->fresh(['weeks.items']);
        }

        $firstWeek = $plan->weeks()->orderBy('week_number')->first();
        if ($firstWeek) {
            app(TeachingPlanReleaseService::class)->releaseWeek($firstWeek, 'initial_release');
        }

        return $plan->fresh(['weeks.items']);
    }

    private function createWeeksAndItems(TeachingPlan $plan, $contents, Carbon $startDate, int $contentsPerWeek): void
    {
        foreach ($contents->values()->chunk($contentsPerWeek) as $weekIndex => $items) {
            $weekNumber = $weekIndex + 1;
            $weekStart = $startDate->copy()->addWeeks($weekIndex);
            $releaseDate = $this->nextReleaseDate($weekStart, $plan->release_day ?: 'Friday');

            $week = TeachingPlanWeek::create([
                'teaching_plan_id' => $plan->id,
                'week_number' => $weekNumber,
                'week_start_date' => $weekStart->toDateString(),
                'week_end_date' => $weekStart->copy()->addDays(6)->toDateString(),
                'release_date' => $releaseDate->toDateString(),
                'status' => 'locked',
            ]);

            foreach ($items as $courseContent) {
                TeachingPlanItem::create([
                    'teaching_plan_id' => $plan->id,
                    'teaching_plan_week_id' => $week->id,
                    'course_id' => $plan->course_id,
                    'course_content_id' => $courseContent->id,
                    'content_id' => $courseContent->content_id,
                    'sort_order' => $courseContent->sort_order,
                    'status' => 'locked',
                ]);
            }
        }
    }

    private function nextReleaseDate(Carbon $weekStart, string $releaseDay): Carbon
    {
        if (strtolower($weekStart->format('l')) === strtolower($releaseDay)) {
            return $weekStart->copy();
        }

        return $weekStart->copy()->next($releaseDay);
    }
}
