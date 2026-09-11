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
    private const MICROCONTROLLERS = [
        'Arduino Uno' => ['arduino uno'],
        'Arduino Nano' => ['arduino nano'],
        'Arduino Mega' => ['arduino mega'],
        'Arduino' => ['arduino'],
        'ESP32' => ['esp32', 'esp-32'],
        'ESP8266' => ['esp8266', 'esp-8266'],
        'Raspberry Pi Pico' => ['raspberry pi pico', 'rp2040'],
        'ATmega328P' => ['atmega328p', 'atmega 328p'],
        '8051 Microcontroller' => ['8051 microcontroller', 'intel 8051'],
        'PIC Microcontroller' => ['pic microcontroller', 'pic16f', 'pic18f'],
        'STM32' => ['stm32'],
    ];

    private const MICROPROCESSORS = [
        '8085 Microprocessor' => ['8085 microprocessor', 'intel 8085'],
        '8086 Microprocessor' => ['8086 microprocessor', 'intel 8086'],
        'ARM Processor' => ['arm processor', 'arm microprocessor'],
        'Raspberry Pi' => ['raspberry pi'],
    ];

    public function offers(Student $student, ?GeminiAiService $ai = null)
    {
        $classOptions = $this->studentClassOptions($student);
        $courses = Course::where('institute', $student->institute)
            ->get()
            ->filter(fn (Course $course) => $this->courseMatchesStudent($course, $classOptions));
        $offers = collect();

        foreach ($courses as $course) {
            // Include the entire curriculum, including lessons not released yet.
            $contents = Content::with(['aiSummary', 'courseContent.sourceTemplateContent.aiSummary'])
                ->where(fn ($q) => $q->where('course_id', $course->id)
                    ->orWhereHas('courseContent', fn ($q) => $q->where('course_id', $course->id)))
                ->orderBy('id')->get();
            $contents = $contents->filter(fn (Content $content) => $this->contentIsActive($content))->values();
            if ($contents->isEmpty()) {
                continue;
            }

            $completionIdsByContent = $this->completionIdsByContent($contents);
            $allCompletionIds = $completionIdsByContent->values()->flatten()->unique()->values();
            $completed = LessonProgress::where('student_id', $student->id)->where('is_completed', true)
                ->whereIn('content_id', $allCompletionIds)->pluck('content_id')
                ->map(fn ($id) => (int) $id);
            $passed = AiQuizAttempt::where('student_id', $student->id)->where('attempt_type', 'student')
                ->where('status', 'passed')->whereIn('content_id', $allCompletionIds)
                ->pluck('content_id')->map(fn ($id) => (int) $id);
            if ($contents->contains(function (Content $content) use ($completionIdsByContent, $completed, $passed) {
                $ids = $completionIdsByContent->get((int) $content->id, collect());
                return $ids->intersect($completed)->isEmpty() && $ids->intersect($passed)->isEmpty();
            })) {
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
                    try {
                        $classified = ($ai ?? app(GeminiAiService::class))->classifyComponentContent($items)['items'] ?? [];
                    } catch (\Throwable $error) {
                        report($error);
                        $classified = [];
                    }
                    $components = collect($classified)->filter(fn ($item) =>
                        in_array($item['component_type'] ?? '', ['microcontroller', 'microprocessor'], true)
                        && (int) ($item['confidence'] ?? 0) >= 45
                        && in_array((int) ($item['content_id'] ?? 0), array_column($items, 'content_id'), true)
                        && filled($item['component_label'] ?? null))
                        ->map(fn ($item) => ['component_key' => Str::slug($item['component_label']),
                            'component_label' => trim($item['component_label']),
                            'component_type' => $item['component_type'], 'content_id' => (int) $item['content_id']])
                        ->values()->all();
                    $components = collect($components)
                        ->merge($this->detectKnownComponents($items))
                        ->unique(fn ($item) => $item['component_key'].'|'.$item['content_id'])
                        ->values()->all();
                    DB::table('component_mastery_course_scans')->updateOrInsert(
                        ['course_id' => $course->id, 'fingerprint' => $fingerprint],
                        ['components' => json_encode($components), 'created_at' => now(), 'updated_at' => now()]);
                    return $components;
                });
            } else {
                $components = json_decode($scan->components, true);
                $components = collect($components)
                    ->merge($this->detectKnownComponents($items))
                    ->unique(fn ($item) => $item['component_key'].'|'.$item['content_id'])
                    ->values()->all();
                if (json_encode($components) !== $scan->components) {
                    DB::table('component_mastery_course_scans')
                        ->where('id', $scan->id)
                        ->update(['components' => json_encode($components), 'updated_at' => now()]);
                }
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

    private function studentClassOptions(Student $student): array
    {
        return collect([
            $student->class,
            trim((string) $student->class.' '.(string) $student->section),
        ])
            ->map(fn ($value) => $this->normalizeClassName($value))
            ->filter()
            ->unique()
            ->values()
            ->all();
    }

    private function normalizeClassName(?string $value): string
    {
        return mb_strtolower(preg_replace('/\s+/', ' ', trim((string) $value)));
    }

    private function courseMatchesStudent(Course $course, array $classOptions): bool
    {
        $assigned = $this->normalizeClassName($course->assigned_class);

        return $assigned !== '' && in_array($assigned, $classOptions, true);
    }

    private function contentIsActive(Content $content): bool
    {
        $status = $content->status;

        return $status === null
            || $status === true
            || $status === 1
            || $status === '1'
            || mb_strtolower((string) $status) === 'active';
    }

    private function completionIdsByContent($contents)
    {
        return collect($contents)->mapWithKeys(function (Content $content) {
            return [
                (int) $content->id => collect([
                    $content->id,
                    $content->ai_quiz_content_id,
                    $content->courseContent?->source_template_content_id,
                ])->filter()->map(fn ($id) => (int) $id)->unique()->values(),
            ];
        });
    }

    private function detectKnownComponents(array $items): array
    {
        $components = [];

        foreach ($items as $item) {
            $haystack = mb_strtolower(trim(
                ($item['title'] ?? '').' '.
                ($item['summary'] ?? '').' '.
                implode(' ', $item['key_points'] ?? []).' '.
                ($item['text_snippet'] ?? '')
            ));

            foreach (self::MICROCONTROLLERS as $label => $aliases) {
                if ($label === 'Arduino' && preg_match('/(?<![a-z0-9])arduino\s+(uno|nano|mega)(?![a-z0-9])/u', $haystack)) {
                    continue;
                }

                if ($this->containsAlias($haystack, $aliases)) {
                    $components[] = [
                        'component_key' => Str::slug($label),
                        'component_label' => $label,
                        'component_type' => 'microcontroller',
                        'content_id' => (int) $item['content_id'],
                    ];
                }
            }

            foreach (self::MICROPROCESSORS as $label => $aliases) {
                if ($label === 'Raspberry Pi' && str_contains($haystack, 'raspberry pi pico')) {
                    continue;
                }

                if ($this->containsAlias($haystack, $aliases)) {
                    $components[] = [
                        'component_key' => Str::slug($label),
                        'component_label' => $label,
                        'component_type' => 'microprocessor',
                        'content_id' => (int) $item['content_id'],
                    ];
                }
            }
        }

        return $components;
    }

    private function containsAlias(string $haystack, array $aliases): bool
    {
        foreach ($aliases as $alias) {
            if (preg_match('/(?<![a-z0-9])'.preg_quote(mb_strtolower($alias), '/').'(?![a-z0-9])/u', $haystack)) {
                return true;
            }
        }

        return false;
    }
}
