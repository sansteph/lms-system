<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;
use App\Models\Content;
use App\Services\ContentPreviewService;
use App\Services\TeachingPlanReleaseService;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('teaching-plans:release-weekly', function (TeachingPlanReleaseService $releaseService) {
    $released = $releaseService->runFridayRelease(now());
    $this->info("Teaching Plan release check completed. Released {$released} week(s).");
})->purpose('Release the next weekly Teaching Plan batch when previous week is completed');

Artisan::command('content-previews:generate {--limit=10} {--timeout=30} {--content-id=}', function (ContentPreviewService $previewService) {
    $limit = max(1, (int) $this->option('limit'));
    $timeout = max(5, (int) $this->option('timeout'));
    $contentId = $this->option('content-id');
    $generated = 0;
    $failed = 0;

    Content::where('status', 1)
        ->when($contentId, function ($query) use ($contentId) {
            $query->where('id', $contentId);
        })
        ->where(function ($query) {
            $query->where(function ($teacherQuery) {
                $teacherQuery->whereNotNull('file_path')
                    ->whereNull('preview_pdf_path')
                    ->where(function ($extensionQuery) {
                        $extensionQuery->whereRaw('LOWER(file_path) LIKE ?', ['%.ppt'])
                            ->orWhereRaw('LOWER(file_path) LIKE ?', ['%.pptx'])
                            ->orWhereRaw('LOWER(file_path) LIKE ?', ['%.doc'])
                            ->orWhereRaw('LOWER(file_path) LIKE ?', ['%.docx']);
                    });
            })->orWhere(function ($studentQuery) {
                $studentQuery->whereNotNull('student_file_path')
                    ->whereNull('student_preview_pdf_path')
                    ->where(function ($extensionQuery) {
                        $extensionQuery->whereRaw('LOWER(student_file_path) LIKE ?', ['%.ppt'])
                            ->orWhereRaw('LOWER(student_file_path) LIKE ?', ['%.pptx'])
                            ->orWhereRaw('LOWER(student_file_path) LIKE ?', ['%.doc'])
                            ->orWhereRaw('LOWER(student_file_path) LIKE ?', ['%.docx']);
                    });
            });
        })
        ->orderBy('id')
        ->limit($limit)
        ->get()
        ->each(function (Content $content) use ($previewService, $timeout, &$generated, &$failed) {
            if ($previewService->isOfficeFile($content->file_path) && !$previewService->previewExists($content->preview_pdf_path)) {
                $this->line("Converting content #{$content->id} teacher file: {$content->file_path}");
                $previewPath = $previewService->generatePreviewPdf($content->file_path, $timeout);

                if ($previewPath) {
                    $content->update(['preview_pdf_path' => $previewPath]);
                    $this->info("Generated teacher preview for content #{$content->id}");
                    $generated++;
                } else {
                    $this->warn("Failed teacher preview for content #{$content->id}");
                    $failed++;
                }
            }

            if ($previewService->isOfficeFile($content->student_file_path) && !$previewService->previewExists($content->student_preview_pdf_path)) {
                $this->line("Converting content #{$content->id} student file: {$content->student_file_path}");
                $previewPath = $previewService->generatePreviewPdf($content->student_file_path, $timeout);

                if ($previewPath) {
                    $content->update(['student_preview_pdf_path' => $previewPath]);
                    $this->info("Generated student preview for content #{$content->id}");
                    $generated++;
                } else {
                    $this->warn("Failed student preview for content #{$content->id}");
                    $failed++;
                }
            }
        });

    $this->info("Content preview generation completed. Generated {$generated}, failed {$failed}.");
})->purpose('Generate missing PDF previews for uploaded Office content');

Schedule::command('teaching-plans:release-weekly')
    ->weeklyOn(5, '08:00');

Schedule::command('content-previews:generate --limit=20 --timeout=30')
    ->everyFiveMinutes()
    ->withoutOverlapping();
