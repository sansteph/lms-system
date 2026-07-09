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
                    if ($this->releaseDueWeek($plan, $date)) {
                        $released++;
                    }
                }
            });

        return $released;
    }

    public function releaseDueWeek(TeachingPlan $plan, ?Carbon $date = null): bool
    {
        $date = $date ?: now();

        if ($plan->is_template) {
            return false;
        }

        if ($plan->start_date && Carbon::parse($plan->start_date)->startOfDay()->gt($date->copy()->startOfDay())) {
            return false;
        }

        return DB::transaction(function () use ($plan, $date) {
            $releasedWeek = $plan->weeks()
                ->where('status', 'released')
                ->orderBy('week_number')
                ->first();

            if (!$releasedWeek) {
                $firstWeek = $plan->weeks()
                    ->where('status', 'locked')
                    ->orderBy('week_number')
                    ->first();

                if (!$firstWeek) {
                    return false;
                }

                if ($firstWeek->release_date && Carbon::parse($firstWeek->release_date)->startOfDay()->gt($date->copy()->startOfDay())) {
                    return false;
                }

                $this->releaseWeek($firstWeek, 'initial_release');
                return true;
            }

            $this->syncWeekCompletion($releasedWeek);
            $releasedWeek->refresh();

            if ($releasedWeek->status !== 'completed') {
                return false;
            }

            $nextWeek = $plan->weeks()
                ->where('status', 'locked')
                ->orderBy('week_number')
                ->first();

            if (!$nextWeek) {
                $plan->update(['status' => 'completed']);
                return false;
            }

            if ($nextWeek->release_date && Carbon::parse($nextWeek->release_date)->startOfDay()->gt($date->copy()->startOfDay())) {
                return false;
            }

            $this->releaseWeek($nextWeek, 'scheduled_friday_release');
            return true;
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

            $this->releaseWeek($week, $reason);
            return $week->fresh();
        });
    }

    public function releaseWeek(TeachingPlanWeek $week, string $reason = 'manual_release'): void
    {
        $week->update([
            'status' => 'released',
            'released_at' => now(),
            'release_reason' => $reason,
        ]);

        $week->items()->update([
            'status' => 'released',
            'released_at' => now(),
        ]);
    }

    public function markItemCompleted(TeachingPlanItem $item): void
    {
        DB::transaction(function () use ($item) {
            $item->update([
                'status' => 'completed',
                'completed_at' => now(),
            ]);

            $this->syncWeekCompletion($item->week);

            $week = $item->week?->fresh();

            if (
                $week &&
                $week->status === 'completed' &&
                $week->release_date &&
                Carbon::parse($week->release_date)->endOfDay()->lte(now())
            ) {
                $this->releaseNextWeek($item->plan, 'catch_up_release');
            }
        });
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
