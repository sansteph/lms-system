<?php

namespace App\Services;

use App\Models\AiQuizAttempt;
use App\Models\Content;
use App\Models\Course;
use App\Models\LessonProgress;
use App\Models\Student;
use App\Services\Ai\GeminiAiService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class ComponentMasteryService
{
    public function offers(Student $student, ?GeminiAiService $ai = null)
    {
        $class = preg_replace('/\s+/', ' ', trim($student->class.' '.$student->section));
        $courses = Course::where('institute', $student->institute)
            ->whereRaw("REPLACE(TRIM(assigned_class), '  ', ' ') = ?", [$class])->get();
        $offers = collect();

        foreach ($courses as $course) {
            // Include the entire curriculum, including lessons not released yet.
            $contents = Content::with(['aiSummary', 'courseContent.sourceTemplateContent.aiSummary'])
                ->where(fn ($q) => $q->where('course_id', $course->id)
                    ->orWhereHas('courseContent', fn ($q) => $q->where('course_id', $course->id)))
                ->orderBy('id')->get();
            if ($contents->isEmpty()) {
                continue;
            }
            $completed = LessonProgress::where('student_id', $student->id)->where('is_completed', true)
                ->whereIn('content_id', $contents->pluck('id'))->pluck('content_id');
            $passed = AiQuizAttempt::where('student_id', $student->id)->where('attempt_type', 'student')
                ->where('status', 'passed')->whereIn('content_id', $contents->pluck('ai_quiz_content_id'))
                ->pluck('content_id');
            if ($contents->contains(fn ($content) => !$completed->contains($content->id)
                && !$passed->contains($content->ai_quiz_content_id))) {
                continue;
            }

            $items = $contents->map(function ($content) {
                $summary = $content->effective_ai_summary;
                return ['content_id' => $content->id, 'title' => $content->content_title,
                    'summary' => $content->description.' '.($summary?->summary ?? ''),
                    'key_points' => $summary?->key_points ?? [],
                    'text_snippet' => $summary?->extracted_text ?? ''];
            })->all();
            $fingerprint = hash('sha256', json_encode($items));
            $scan = DB::table('component_mastery_course_scans')->where('course_id', $course->id)
                ->where('fingerprint', $fingerprint)->first();
            if (!$scan) {
                $components = Cache::lock('mastery-scan-'.$course->id, 180)->block(5, function () use ($course, $fingerprint, $items, $ai) {
                    $existing = DB::table('component_mastery_course_scans')->where('course_id', $course->id)
                        ->where('fingerprint', $fingerprint)->first();
                    if ($existing) return json_decode($existing->components, true);
                    $classified = ($ai ?? app(GeminiAiService::class))->classifyComponentContent($items)['items'] ?? [];
                    $components = collect($classified)->filter(fn ($item) =>
                        in_array($item['component_type'] ?? '', ['microcontroller', 'microprocessor'], true)
                        && (int) ($item['confidence'] ?? 0) >= 45
                        && in_array((int) ($item['content_id'] ?? 0), array_column($items, 'content_id'), true)
                        && filled($item['component_label'] ?? null))
                        ->map(fn ($item) => ['component_key' => Str::slug($item['component_label']),
                            'component_label' => trim($item['component_label']),
                            'component_type' => $item['component_type'], 'content_id' => (int) $item['content_id']])
                        ->values()->all();
                    DB::table('component_mastery_course_scans')->updateOrInsert(
                        ['course_id' => $course->id, 'fingerprint' => $fingerprint],
                        ['components' => json_encode($components), 'created_at' => now(), 'updated_at' => now()]);
                    return $components;
                });
            } else {
                $components = json_decode($scan->components, true);
            }
            foreach (collect($components)->groupBy('component_key') as $key => $matches) {
                $relevant = $contents->whereIn('id', $matches->pluck('content_id'))->values();
                $offers->push(['component_key' => $key, 'component_label' => $matches->first()['component_label'],
                    'component_type' => $matches->first()['component_type'], 'course_ids' => [$course->id],
                    'course_titles' => [$course->course_title], 'completed_count' => $relevant->count(),
                    'content_ids' => $relevant->pluck('id')->all(), 'content_titles' => $relevant->pluck('content_title')->all()]);
            }
        }

        return $offers->groupBy('component_key')->map(function ($group) {
            $offer = $group->first();
            foreach (['course_ids', 'course_titles', 'content_ids', 'content_titles'] as $field) {
                $offer[$field] = $group->pluck($field)->flatten()->unique()->values()->all();
            }
            $offer['completed_count'] = count($offer['content_ids']);
            return $offer;
        })->sortBy('component_label')->values();
    }
}
