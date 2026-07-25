<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;
use App\Services\TeachingPlanReleaseService;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('teaching-plans:release-weekly', function (TeachingPlanReleaseService $releaseService) {
    $released = $releaseService->runFridayRelease(now());
    $this->info("Teaching Plan release check completed. Released {$released} week(s).");
})->purpose('Release scheduled Teaching Plan weeks when their release date arrives');

Schedule::command('teaching-plans:release-weekly')
    ->weeklyOn(5, '08:00');
