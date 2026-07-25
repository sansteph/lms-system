<?php

namespace App\Services;

use App\Models\TeachingPlan;
use App\Models\TeachingPlanItem;
use App\Models\TeachingPlanWeek;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class TeachingPlanReleaseService
{
    public function runFridayRelease(?Carbon $date = null): int
    {
        $date = $date ?: now();
        $released = 0;

        TeachingPlan::where('status', 'active')
            ->where('is_template', false)
            ->with(['weeks.items'])
            ->chunkById(50, function ($plans) use ($date, &$released) {
                foreach ($plans as $plan) {
                    $released += $this->releaseDueWeek($plan, $date);
                }
            });

        return $released;
    }

    public function releaseDueWeek(TeachingPlan $plan, ?Carbon $date = null): int
    {
        $date = $date ?: now();

        if ($plan->is_template) {
            return 0;
        }

        if ($plan->start_date && Carbon::parse($plan->start_date)->startOfDay()->gt($date->copy()->startOfDay())) {
            return 0;
        }

        return DB::transaction(function () use ($plan, $date) {
            $plan->weeks()
                ->where('status', 'released')
                ->where(function ($query) {
                    $query->whereNull('release_reason')
                        ->orWhere('release_reason', '!=', 'lagged_content');
                })
                ->get()
                ->each(fn (TeachingPlanWeek $week) => $this->syncWeekCompletion($week));

            $dueWeeks = $plan->weeks()
                ->where('status', 'locked')
                ->whereNotNull('release_date')
                ->whereDate('release_date', '<=', $date->copy()->toDateString())
                ->where(function ($query) {
                    $query->whereNull('release_reason')
                        ->orWhere('release_reason', '!=', 'lagged_content');
                })
                ->orderBy('week_number')
                ->get();

            foreach ($dueWeeks as $week) {
                $this->releaseWeek($week, $week->week_number == 1 ? 'initial_release' : 'scheduled_friday_release');
            }

            if (
                $dueWeeks->isEmpty() &&
                !$plan->weeks()->whereIn('status', ['locked', 'released', 'skipped'])->exists()
            ) {
                $plan->update(['status' => 'completed']);
            }

            return $dueWeeks->count();
        });
    }

    public function releaseNextWeek(TeachingPlan $plan, string $reason = 'manual_release'): ?TeachingPlanWeek
    {
        if ($plan->is_template) {
            return null;
        }

        return DB::transaction(function () use ($plan, $reason) {
            $week = $plan->weeks()
                ->whereIn('status', ['locked', 'skipped'])
                ->orderBy('week_number')
                ->first();

            if (!$week) {
                return null;
            }

            return $this->releaseWeek($week, $reason) ? $week->fresh() : null;
        });
    }

    public function releaseWeek(TeachingPlanWeek $week, string $reason = 'manual_release'): bool
    {
        $week->refresh();

        if (!in_array($week->status, ['locked', 'skipped'], true)) {
            return false;
        }

        $week->update([
            'status' => 'released',
            'released_at' => now(),
            'release_reason' => $reason,
        ]);

        $week->items()
            ->whereIn('status', ['locked', 'skipped'])
            ->update([
                'status' => 'released',
                'released_at' => now(),
            ]);

        app(LmsNotificationService::class)->notifyTeachersOfReleasedWeek($week->fresh(['plan.course', 'items.content']));

        return true;
    }

    public function markItemCompleted(TeachingPlanItem $item): bool
    {
        if ($item->status !== 'released') {
            return false;
        }

        DB::transaction(function () use ($item) {
            $item->update([
                'status' => 'completed',
                'completed_at' => now(),
            ]);

            $this->syncWeekCompletion($item->week);
        });

        return true;
    }

    public function syncWeekCompletion(?TeachingPlanWeek $week): void
    {
        if (!$week) {
            return;
        }

        $hasPending = $week->items()
            ->where('status', '!=', 'completed')
            ->exists();

        if (!$hasPending) {
            $week->update([
                'status' => 'completed',
                'completed_at' => $week->completed_at ?: now(),
            ]);
        }
    }
}
