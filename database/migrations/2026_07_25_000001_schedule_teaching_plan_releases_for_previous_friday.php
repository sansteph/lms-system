<?php

use Carbon\Carbon;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('teaching_plan_weeks') || !Schema::hasTable('teaching_plans')) {
            return;
        }

        if (Schema::hasColumn('teaching_plans', 'release_policy')) {
            DB::table('teaching_plans')
                ->where('release_policy', 'release_next_only_if_previous_completed')
                ->update(['release_policy' => 'scheduled_weekly_release']);
        }

        DB::table('teaching_plan_weeks')
            ->whereNotNull('week_start_date')
            ->whereIn('status', ['locked', 'skipped'])
            ->where(function ($query) {
                $query->whereNull('release_reason')
                    ->orWhere('release_reason', '!=', 'lagged_content');
            })
            ->chunkById(100, function ($weeks) {
                foreach ($weeks as $week) {
                    $releaseDay = DB::table('teaching_plans')
                        ->where('id', $week->teaching_plan_id)
                        ->value('release_day') ?: 'Friday';

                    $releaseDate = $this->releaseDateForWeek(
                        Carbon::parse($week->week_start_date)->startOfDay(),
                        (int) $week->week_number,
                        $releaseDay
                    );

                    DB::table('teaching_plan_weeks')
                        ->where('id', $week->id)
                        ->update([
                            'release_date' => $releaseDate->toDateString(),
                        ]);
                }
            });

        $this->mergeDuplicateTeachingPlanItems();
        $this->removeEmptyTeachingPlanWeeks();
    }

    public function down(): void
    {
        // Data alignment only. Historical release dates cannot be restored safely.
    }

    private function releaseDateForWeek(Carbon $weekStart, int $weekNumber, string $releaseDay): Carbon
    {
        if ($weekNumber <= 1) {
            return $weekStart->copy();
        }

        if (strtolower($weekStart->format('l')) === strtolower($releaseDay)) {
            return $weekStart->copy()->subWeek();
        }

        return $weekStart->copy()->previous($releaseDay);
    }

    private function mergeDuplicateTeachingPlanItems(): void
    {
        if (!Schema::hasTable('teaching_plan_items')) {
            return;
        }

        DB::table('teaching_plan_items')
            ->select('teaching_plan_id', 'course_content_id')
            ->whereNotNull('course_content_id')
            ->groupBy('teaching_plan_id', 'course_content_id')
            ->havingRaw('COUNT(*) > 1')
            ->orderBy('teaching_plan_id')
            ->chunk(100, function ($groups) {
                foreach ($groups as $group) {
                    $this->mergeDuplicateItemGroup((int) $group->teaching_plan_id, 'course_content_id', (int) $group->course_content_id);
                }
            });

        DB::table('teaching_plan_items')
            ->select('teaching_plan_id', 'content_id')
            ->whereNull('course_content_id')
            ->whereNotNull('content_id')
            ->groupBy('teaching_plan_id', 'content_id')
            ->havingRaw('COUNT(*) > 1')
            ->orderBy('teaching_plan_id')
            ->chunk(100, function ($groups) {
                foreach ($groups as $group) {
                    $this->mergeDuplicateItemGroup((int) $group->teaching_plan_id, 'content_id', (int) $group->content_id);
                }
            });
    }

    private function mergeDuplicateItemGroup(int $planId, string $column, int $value): void
    {
        $items = DB::table('teaching_plan_items')
            ->where('teaching_plan_id', $planId)
            ->where($column, $value)
            ->orderByRaw("CASE status WHEN 'completed' THEN 1 WHEN 'released' THEN 2 WHEN 'locked' THEN 3 WHEN 'skipped' THEN 4 ELSE 5 END")
            ->orderBy('id')
            ->get();

        if ($items->count() <= 1) {
            return;
        }

        $keeper = $items->first();
        $candidateIds = $items
            ->whereNotIn('id', [$keeper->id])
            ->filter(function ($item) {
                return in_array($item->status, ['locked', 'skipped'], true);
            })
            ->pluck('id')
            ->values();

        if ($candidateIds->isEmpty()) {
            return;
        }

        if (Schema::hasTable('class_content_sessions') && Schema::hasColumn('class_content_sessions', 'teaching_plan_item_id')) {
            $sessionLinkedIds = DB::table('class_content_sessions')
                ->whereIn('teaching_plan_item_id', $candidateIds)
                ->pluck('teaching_plan_item_id')
                ->unique()
                ->values();

            $candidateIds = $candidateIds->diff($sessionLinkedIds)->values();
        }

        if ($candidateIds->isEmpty()) {
            return;
        }

        if (Schema::hasTable('class_content_sessions') && Schema::hasColumn('class_content_sessions', 'teaching_plan_item_id')) {
            $sessionUpdates = ['teaching_plan_item_id' => $keeper->id];

            if (Schema::hasColumn('class_content_sessions', 'teaching_plan_week_id')) {
                $sessionUpdates['teaching_plan_week_id'] = $keeper->teaching_plan_week_id;
            }

            DB::table('class_content_sessions')
                ->whereIn('teaching_plan_item_id', $candidateIds)
                ->update($sessionUpdates);
        }

        DB::table('teaching_plan_items')
            ->whereIn('id', $candidateIds)
            ->delete();
    }

    private function removeEmptyTeachingPlanWeeks(): void
    {
        if (!Schema::hasTable('teaching_plan_weeks') || !Schema::hasTable('teaching_plan_items')) {
            return;
        }

        DB::table('teaching_plan_weeks')
            ->whereNotIn('status', ['released', 'completed'])
            ->whereNotExists(function ($query) {
                $query->select(DB::raw(1))
                    ->from('teaching_plan_items')
                    ->whereColumn('teaching_plan_items.teaching_plan_week_id', 'teaching_plan_weeks.id');
            })
            ->when(
                Schema::hasTable('class_content_sessions') && Schema::hasColumn('class_content_sessions', 'teaching_plan_week_id'),
                function ($query) {
                    $query->whereNotExists(function ($sessionQuery) {
                        $sessionQuery->select(DB::raw(1))
                            ->from('class_content_sessions')
                            ->whereColumn('class_content_sessions.teaching_plan_week_id', 'teaching_plan_weeks.id');
                    });
                }
            )
            ->delete();
    }
};
