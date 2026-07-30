<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;
use App\Models\AiContentSummary;
use App\Models\AiQuiz;
use App\Models\AiQuizQuestion;
use App\Models\TeachingPlanItem;
use App\Services\Ai\AiContentSummaryService;
use App\Services\TeachingPlanReleaseService;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('teaching-plans:release-weekly', function (TeachingPlanReleaseService $releaseService) {
    $released = $releaseService->runFridayRelease(now());
    $this->info("Teaching Plan release check completed. Released {$released} week(s).");
})->purpose('Release scheduled Teaching Plan weeks when their release date arrives');

Artisan::command('ai-content:generate-upcoming {--limit=} {--retry-failed}', function (AiContentSummaryService $summaryService) {
    $limit = (int) ($this->option('limit') ?: config('ai.content.auto_generation_limit', 2));
    $limit = max(1, min($limit, 10));
    $retryFailed = (bool) $this->option('retry-failed');

    $items = TeachingPlanItem::with([
            'content.aiSummary',
            'content.courseContent.sourceTemplateContent.aiSummary',
            'content.courseContent.sourceTemplateContent',
            'week',
            'plan',
        ])
        ->whereIn('status', ['locked', 'released'])
        ->whereHas('week', function ($query) {
            $query->whereIn('status', ['locked', 'released'])
                ->whereNotNull('release_date');
        })
        ->whereHas('plan', function ($query) {
            $query->where('is_template', false)
                ->where('status', 'active')
                ->whereNotNull('ai_training_start_date');
        })
        ->orderBy('teaching_plan_week_id')
        ->orderBy('sort_order')
        ->get()
        ->filter(function ($item) {
            if (!$item->week?->release_date || !$item->plan?->ai_training_start_date) {
                return false;
            }

            if ($item->week?->release_reason === 'lagged_content') {
                return false;
            }

            return \Carbon\Carbon::parse($item->week->release_date)->toDateString()
                >= \Carbon\Carbon::parse($item->plan->ai_training_start_date)->toDateString();
        });

    $mcqSeedsForSummary = function (AiContentSummary $summary): array {
        $normalizeMcqSeed = function (array $seed): ?array {
            $question = trim((string) ($seed['question'] ?? ''));
            $options = array_values(array_filter(array_map(
                fn ($option) => trim((string) $option),
                (array) ($seed['options'] ?? [])
            )));
            $correctAnswer = trim((string) ($seed['correct_answer'] ?? $seed['expected_answer'] ?? ''));

            if ($question === '' || count($options) !== 4 || $correctAnswer === '') {
                return null;
            }

            if (!in_array($correctAnswer, $options, true)) {
                return null;
            }

            return [
                'question' => $question,
                'options' => $options,
                'correct_answer' => $correctAnswer,
                'marks' => max(1, (int) ($seed['marks'] ?? 1)),
            ];
        };

        $seeds = collect($summary->quiz_seed ?? [])
            ->map(fn ($seed) => $normalizeMcqSeed((array) $seed))
            ->filter()
            ->values()
            ->all();

        if (count($seeds) >= 5) {
            return array_slice($seeds, 0, 5);
        }

        $points = collect($summary->key_points ?? [])
            ->map(fn ($point) => trim((string) $point))
            ->filter()
            ->values();

        if ($points->isEmpty() && filled($summary->summary)) {
            $points = collect(preg_split('/(?<=[.!?])\s+/', strip_tags((string) $summary->summary)))
                ->map(fn ($point) => trim($point))
                ->filter()
                ->take(8)
                ->values();
        }

        if ($points->isEmpty()) {
            $points = collect([
                'The lesson explains an important STEM concept.',
                'The lesson connects theory with practical learning.',
                'The lesson supports project-based understanding.',
                'The lesson includes key ideas students should remember.',
                'The lesson is part of the InnovatEdge learning sequence.',
            ]);
        }

        $genericDistractors = collect([
            'It is not related to this lesson.',
            'It explains only the certificate workflow.',
            'It is mainly about login permissions.',
            'It describes unrelated administrative setup.',
            'It focuses only on payment settings.',
        ]);

        $fallbackSeeds = $points->take(5)->map(function ($point) use ($points, $genericDistractors) {
            $distractors = $points
                ->reject(fn ($candidate) => $candidate === $point)
                ->take(3)
                ->merge($genericDistractors)
                ->unique()
                ->take(3)
                ->values()
                ->all();

            $options = array_values(array_slice(array_merge([$point], $distractors), 0, 4));

            while (count($options) < 4) {
                $options[] = 'None of the above statements match this lesson.';
            }

            return [
                'question' => 'Which statement is an important point from this lesson?',
                'options' => $options,
                'correct_answer' => $point,
                'marks' => 1,
            ];
        })->all();

        return array_slice(array_merge($seeds, $fallbackSeeds), 0, 5);
    };

    $ensureQuiz = function ($content, AiContentSummary $summary, string $audience, ?string $gradeLevel) use ($mcqSeedsForSummary): void {
        $gradeLevel = $gradeLevel ? preg_replace('/\s+/', ' ', trim($gradeLevel)) : null;
        $passingRatio = $audience == 'teacher'
            ? ((float) config('ai.content.teacher_passing_percentage', 50) / 100)
            : ((float) config('ai.content.student_passing_percentage', 60) / 100);

        $quiz = AiQuiz::firstOrCreate(
            [
                'content_id' => $content->id,
                'audience' => $audience,
                'grade_level' => $gradeLevel,
                'status' => 'active',
            ],
            [
                'provider' => $summary->provider,
                'model' => $summary->model,
                'title' => ($audience == 'teacher' ? 'AI Prep - ' : 'AI Review - ') . ($gradeLevel ? $gradeLevel . ' - ' : '') . $content->content_title,
                'instructions' => $audience == 'teacher'
                    ? 'Answer these prep questions before teaching this lesson.'
                    : 'Answer these questions after reviewing the completed lesson.',
                'total_marks' => 0,
                'passing_marks' => 0,
            ]
        );

        if ($quiz->questions()->exists()) {
            $questions = $quiz->questions()->get();
            $hasOnlyValidMcq = $questions->isNotEmpty()
                && $questions->every(function ($question) {
                    return $question->question_type === 'mcq'
                        && is_array($question->options)
                        && count($question->options) === 4
                        && filled($question->expected_answer)
                        && in_array($question->expected_answer, $question->options, true);
                });

            if (!$hasOnlyValidMcq) {
                $quiz->questions()->delete();

                foreach ($mcqSeedsForSummary($summary) as $index => $seed) {
                    AiQuizQuestion::create([
                        'ai_quiz_id' => $quiz->id,
                        'question_order' => $index + 1,
                        'question_type' => 'mcq',
                        'question_text' => $seed['question'],
                        'options' => $seed['options'],
                        'expected_answer' => $seed['correct_answer'],
                        'marks' => max(1, (int) ($seed['marks'] ?? 1)),
                    ]);
                }

                $quiz->update([
                    'total_marks' => $quiz->questions()->sum('marks'),
                ]);
                $quiz->refresh();
            }

            if ($quiz->total_marks > 0) {
                $quiz->update([
                    'passing_marks' => (int) ceil($quiz->total_marks * $passingRatio),
                ]);
            }

            return;
        }

        $seeds = $mcqSeedsForSummary($summary);

        $totalMarks = 0;

        foreach ($seeds as $index => $seed) {
            $marks = max(1, (int) ($seed['marks'] ?? 1));
            $totalMarks += $marks;

            AiQuizQuestion::create([
                'ai_quiz_id' => $quiz->id,
                'question_order' => $index + 1,
                'question_type' => 'mcq',
                'question_text' => $seed['question'],
                'options' => $seed['options'],
                'expected_answer' => $seed['correct_answer'],
                'marks' => $marks,
            ]);
        }

        $quiz->update([
            'total_marks' => $totalMarks,
            'passing_marks' => (int) ceil($totalMarks * $passingRatio),
        ]);
    };

    $processed = 0;
    $skipped = 0;
    $failed = 0;

    foreach ($items as $item) {
        if ($processed >= $limit) {
            break;
        }

        if (!$item->content) {
            $skipped++;
            continue;
        }

        $sourceTemplateContent = $item->content->courseContent?->sourceTemplateContent;
        $summaryContent = ($sourceTemplateContent && $sourceTemplateContent->hasAiPdfMaterial())
            ? $sourceTemplateContent
            : $item->content;
        $existing = AiContentSummary::where('content_id', $summaryContent->id)->first();

        if ($existing && $existing->status == 'generated') {
            $ensureQuiz($summaryContent, $existing, 'teacher', $item->plan?->class);
            $ensureQuiz($summaryContent, $existing, 'student', $item->plan?->class);
            $skipped++;
            continue;
        }

        if ($existing && $existing->status == 'failed' && !$retryFailed) {
            $skipped++;
            continue;
        }

        try {
            $summary = $summaryService->generate($summaryContent);
            $ensureQuiz($summaryContent, $summary, 'teacher', $item->plan?->class);
            $ensureQuiz($summaryContent, $summary, 'student', $item->plan?->class);
            $processed++;
            $this->line('Generated AI prep for: ' . $summaryContent->content_title);
        } catch (\Throwable $exception) {
            $failed++;
            $this->warn('AI generation failed for ' . $summaryContent->content_title . ': ' . $exception->getMessage());
        }
    }

    $this->info("AI upcoming content generation completed. Generated {$processed}, skipped {$skipped}, failed {$failed}.");
})->purpose('Generate AI summaries and prep quizzes for upcoming Teaching Plan content');

Schedule::command('teaching-plans:release-weekly')
    ->weeklyOn(5, '08:00');

Schedule::command('ai-content:generate-upcoming')
    ->everyFiveMinutes()
    ->withoutOverlapping();
