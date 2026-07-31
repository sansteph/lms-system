<?php

namespace App\Services;

use App\Models\Content;
use App\Models\Course;
use App\Models\CourseContent;
use App\Models\Institute;
use App\Models\TeachingPlan;
use App\Models\TeachingPlanItem;
use App\Models\TeachingPlanWeek;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class TeachingPlanTemplateDeploymentService
{
    public function deployTemplatesToInstituteClasses(
        array $instituteIds,
        array $classesByInstitute,
        ?int $deployedBy = null
    ): array {
        $summary = [
            'deployed' => 0,
            'synced' => 0,
            'skipped' => 0,
            'messages' => [],
        ];

        $institutes = Institute::whereIn('id', $instituteIds)->get();

        foreach ($institutes as $institute) {
            $selectedClasses = collect($classesByInstitute[(string) $institute->id] ?? [])
                ->map(fn ($class) => trim((string) $class))
                ->filter()
                ->unique()
                ->values();

            if ($selectedClasses->isEmpty()) {
                $summary['skipped']++;
                $summary['messages'][] = 'Skipped ' . $institute->institute_name . ': no classes selected.';
                continue;
            }

            $selectedClassLabels = $selectedClasses
                ->map(fn ($class) => $this->normalizeClassLabel($class))
                ->unique()
                ->values()
                ->all();

            $templates = TeachingPlan::with(['course.courseContents.content', 'weeks.items'])
                ->where('is_template', true)
                ->where('status', 'active')
                ->get();

            $matchedAnyTemplate = false;

            foreach ($templates as $template) {
                $targets = $this->matchingDeploymentTargets($template, $selectedClassLabels);

                if ($targets->isEmpty()) {
                    continue;
                }

                $matchedAnyTemplate = true;

                foreach ($targets as $target) {
                    $result = $this->deployTemplate(
                        $template,
                        $institute,
                        $deployedBy,
                        $target['class'],
                        $target['section']
                    );

                    if (array_key_exists($result['status'], $summary)) {
                        $summary[$result['status']]++;
                    } else {
                        $summary['skipped']++;
                    }

                    $summary['messages'][] = $result['message'];
                }
            }

            if (!$matchedAnyTemplate) {
                $summary['skipped']++;
                $summary['messages'][] = 'Skipped ' . $institute->institute_name . ': no matching active templates found.';
                continue;
            }
        }

        return $summary;
    }

    public function deployTemplate(
        TeachingPlan $template,
        Institute $institute,
        ?int $deployedBy = null,
        ?string $targetClass = null,
        ?string $targetSection = null
    ): array
    {
        if (!$template->is_template) {
            return [
                'status' => 'skipped',
                'message' => 'Skipped non-template Teaching Plan: ' . ($template->title ?? $template->id),
            ];
        }

        $targetClass = trim((string) ($targetClass ?: $template->class));
        $targetSection = trim((string) ($targetSection ?? $template->section ?? ''));
        $targetLabel = $this->normalizeClassLabel($targetClass, $targetSection);

        return DB::transaction(function () use ($template, $institute, $deployedBy, $targetClass, $targetSection, $targetLabel) {
            $existingPlan = TeachingPlan::where('is_template', false)
                ->where('parent_template_id', $template->id)
                ->where('institute', $institute->institute_name)
                ->where('class', $targetClass)
                ->where(function ($query) use ($targetSection) {
                    if (filled($targetSection)) {
                        $query->where('section', $targetSection);
                    } else {
                        $query->whereNull('section')
                            ->orWhere('section', '');
                    }
                })
                ->whereNotNull('course_id')
                ->first();

            if ($existingPlan) {
                return $this->syncExistingDeployment(
                    $template,
                    $existingPlan,
                    $institute,
                    $targetLabel,
                    $deployedBy
                );
            }

            $templateCourse = $template->course()->with('courseContents.content')->firstOrFail();

            $sourceCourseContents = $templateCourse->courseContents()
                ->with('content')
                ->where('status', 'active')
                ->orderBy('sort_order')
                ->get()
                ->filter(function ($templateCourseContent) {
                    return $templateCourseContent->content &&
                        $templateCourseContent->content->file_path &&
                        Storage::disk('local')->exists($templateCourseContent->content->file_path);
                });

            if ($sourceCourseContents->isEmpty()) {
                return [
                    'status' => 'skipped',
                    'message' => 'Skipped ' . ($template->title ?? $templateCourse->course_title ?? 'Template') . ' for ' . $institute->institute_name . ': no usable source content files found.',
                ];
            }

            $course = Course::create([
                'institute' => $institute->institute_name,
                'course_title' => $templateCourse->course_title,
                'description' => $templateCourse->description,
                'target' => $templateCourse->target ?? 'Both',
                'assigned_class' => $targetLabel,
                'price' => $templateCourse->price ?? 0,
                'availability_type' => 'Institute',
                'is_active' => 1,
                'is_template_source' => false,
                'certificate_enabled' => $templateCourse->certificate_enabled ?? 1,
                'status' => 1,
            ]);

            $copiedCount = 0;

            foreach ($sourceCourseContents as $templateCourseContent) {
                $templateContent = $templateCourseContent->content;

                if ($this->copyContentToCourse($course, $templateContent, (int) $templateCourseContent->sort_order, $deployedBy, $templateCourseContent)) {
                    $copiedCount++;
                }
            }

            if ($copiedCount === 0) {
                $course->delete();

                return [
                    'status' => 'skipped',
                    'message' => 'Skipped ' . ($template->title ?? $templateCourse->course_title ?? 'Template') . ' for ' . $institute->institute_name . ': content files could not be copied.',
                ];
            }

            $templateStartDate = $template->weeks()
                ->whereNotNull('week_start_date')
                ->orderBy('week_number')
                ->value('week_start_date');

            $plan = app(TeachingPlanBuilderService::class)->buildForCourse(
                $course,
                [
                    'parent_template_id' => $template->id,
                    'title' => $template->title,
                    'institute_id' => $institute->id,
                    'class' => $targetClass,
                    'section' => $targetSection ?: null,
                    'start_date' => $templateStartDate ?: now()->toDateString(),
                    'release_day' => $template->release_day,
                    'contents_per_week' => $template->contents_per_week,
                    'release_policy' => $template->release_policy,
                    'status' => 'active',
                    'remarks' => 'Deployed from Teaching Plan Template #' . $template->id,
                ],
                $deployedBy
            );

            return [
                'status' => 'deployed',
                'message' => 'Deployed ' . ($template->title ?? $course->course_title) . ' to ' . $institute->institute_name . ' / ' . $targetLabel . ' as plan #' . $plan->id,
            ];
        });
    }

    private function syncExistingDeployment(
        TeachingPlan $template,
        TeachingPlan $existingPlan,
        Institute $institute,
        string $targetLabel,
        ?int $deployedBy
    ): array {
        $templateCourse = $template->course()->with('courseContents.content')->firstOrFail();
        $course = $existingPlan->course()->with('courseContents.content')->first();

        if (!$course) {
            return [
                'status' => 'skipped',
                'message' => 'Skipped sync for ' . ($template->title ?? $templateCourse->course_title ?? 'Template') . ' / ' . $targetLabel . ': deployed course is missing.',
            ];
        }

        $sourceCourseContents = $templateCourse->courseContents()
            ->with('content')
            ->where('status', 'active')
            ->orderBy('sort_order')
            ->get()
            ->filter(function ($templateCourseContent) {
                return $templateCourseContent->content &&
                    $templateCourseContent->content->file_path &&
                    Storage::disk('local')->exists($templateCourseContent->content->file_path);
            });

        if ($sourceCourseContents->isEmpty()) {
            return [
                'status' => 'skipped',
                'message' => 'Skipped sync for ' . ($template->title ?? $templateCourse->course_title ?? 'Template') . ' / ' . $targetLabel . ': no usable source content files found.',
            ];
        }

        $syncedCount = 0;

        foreach ($sourceCourseContents as $sourceCourseContent) {
            if ($this->findExistingSyncedCourseContent($course, $sourceCourseContent)) {
                continue;
            }

            $nextSortOrder = ((int) $course->courseContents()->max('sort_order')) + 1;
            $newCourseContent = $this->copyContentToCourse(
                $course,
                $sourceCourseContent->content,
                $nextSortOrder,
                $deployedBy,
                $sourceCourseContent
            );

            if (!$newCourseContent) {
                continue;
            }

            $this->appendCourseContentToPlan($existingPlan, $newCourseContent);
            $syncedCount++;
        }

        if ($syncedCount === 0) {
            return [
                'status' => 'skipped',
                'message' => 'Skipped duplicate deployment: ' . ($template->title ?? $templateCourse->course_title ?? 'Template') . ' for ' . $institute->institute_name . ' / ' . $targetLabel . ' already has all current template content.',
            ];
        }

        if ($existingPlan->status === 'completed') {
            $existingPlan->update(['status' => 'active']);
        }

        return [
            'status' => 'synced',
            'message' => 'Synced ' . $syncedCount . ' new content item' . ($syncedCount === 1 ? '' : 's') . ' from ' . ($template->title ?? $templateCourse->course_title ?? 'Template') . ' to ' . $institute->institute_name . ' / ' . $targetLabel . '.',
        ];
    }

    private function findExistingSyncedCourseContent(Course $course, CourseContent $sourceCourseContent): ?CourseContent
    {
        $directMatch = $course->courseContents()
            ->where('source_template_course_content_id', $sourceCourseContent->id)
            ->first();

        if ($directMatch) {
            return $directMatch;
        }

        $sourceContent = $sourceCourseContent->content;

        if (!$sourceContent) {
            return null;
        }

        $legacyMatch = $course->courseContents()
            ->with('content')
            ->whereNull('source_template_course_content_id')
            ->get()
            ->first(function ($courseContent) use ($sourceCourseContent, $sourceContent) {
                $content = $courseContent->content;

                if (!$content) {
                    return false;
                }

                $sameTitle = trim((string) $content->content_title) === trim((string) $sourceContent->content_title);
                $sameFileName = filled($content->original_file_name) &&
                    filled($sourceContent->original_file_name) &&
                    $content->original_file_name === $sourceContent->original_file_name;
                $sameOriginalOrder = (int) $courseContent->sort_order === (int) $sourceCourseContent->sort_order;

                return $sameTitle && ($sameFileName || $sameOriginalOrder);
            });

        if ($legacyMatch) {
            $legacyMatch->update([
                'source_template_course_content_id' => $sourceCourseContent->id,
                'source_template_content_id' => $sourceContent->id,
            ]);
        }

        return $legacyMatch;
    }

    private function appendCourseContentToPlan(TeachingPlan $plan, CourseContent $courseContent): TeachingPlanItem
    {
        $existingItem = $plan->items()
            ->where(function ($query) use ($courseContent) {
                $query->where('course_content_id', $courseContent->id)
                    ->orWhere('content_id', $courseContent->content_id);
            })
            ->first();

        if ($existingItem) {
            return $existingItem;
        }

        $contentsPerWeek = max(1, (int) ($plan->contents_per_week ?: 1));

        $week = $plan->weeks()
            ->where('status', 'locked')
            ->withCount('items')
            ->orderBy('week_number')
            ->get()
            ->first(fn ($lockedWeek) => (int) $lockedWeek->items_count < $contentsPerWeek);

        if (!$week) {
            $week = $this->createNextLockedWeek($plan);
        }

        return TeachingPlanItem::create([
            'teaching_plan_id' => $plan->id,
            'teaching_plan_week_id' => $week->id,
            'course_id' => $plan->course_id,
            'course_content_id' => $courseContent->id,
            'content_id' => $courseContent->content_id,
            'sort_order' => $courseContent->sort_order,
            'status' => 'locked',
        ]);
    }

    private function createNextLockedWeek(TeachingPlan $plan): TeachingPlanWeek
    {
        $lastWeek = $plan->weeks()
            ->orderByDesc('week_number')
            ->first();

        $weekNumber = $lastWeek ? ((int) $lastWeek->week_number + 1) : 1;
        $weekStart = $lastWeek && $lastWeek->week_end_date
            ? Carbon::parse($lastWeek->week_end_date)->addDay()->startOfDay()
            : Carbon::parse($plan->plan_start_date ?? $plan->start_date ?? now()->toDateString())->addWeeks($weekNumber - 1)->startOfDay();
        return TeachingPlanWeek::create([
            'teaching_plan_id' => $plan->id,
            'week_number' => $weekNumber,
            'week_start_date' => $weekStart->toDateString(),
            'week_end_date' => $weekStart->copy()->addDays(6)->toDateString(),
            'release_date' => $this->releaseDateForWeek($weekStart, $weekNumber, $plan->release_day ?: 'Friday')->toDateString(),
            'status' => 'locked',
        ]);
    }

    private function copyContentToCourse(Course $course, Content $templateContent, int $sortOrder, ?int $createdBy, ?CourseContent $sourceCourseContent = null): ?CourseContent
    {
        if (!$templateContent->file_path || !Storage::disk('local')->exists($templateContent->file_path)) {
            return null;
        }

        $filePath = $templateContent->file_path;
        $previewPath = $templateContent->preview_pdf_path;
        $studentFilePath = $templateContent->student_file_path;
        $studentPreviewPath = $templateContent->student_preview_pdf_path;

        $contentType = $templateContent->content_type ?: strtoupper(pathinfo((string) $filePath, PATHINFO_EXTENSION) ?: 'PDF');

        $content = Content::create([
            'course_id' => $course->id,
            'content_title' => $templateContent->content_title,
            'description' => $templateContent->description,
            'lesson_order' => $sortOrder,
            'content_type' => $contentType,
            'assigned_class' => $course->assigned_class,
            'section' => $templateContent->section,
            'institute' => $course->institute,
            'file_path' => $filePath,
            'preview_pdf_path' => $previewPath,
            'student_file_path' => $studentFilePath,
            'student_preview_pdf_path' => $studentPreviewPath,
            'original_file_name' => $templateContent->original_file_name ?: basename((string) $templateContent->file_path),
            'uploaded_by' => $createdBy,
            'is_released' => 0,
            'status' => 1,
        ]);

        return CourseContent::create([
            'course_id' => $course->id,
            'content_id' => $content->id,
            'sort_order' => $sortOrder,
            'status' => 'active',
            'created_by' => $createdBy,
            'source_template_course_content_id' => $sourceCourseContent?->id,
            'source_template_content_id' => $templateContent->id,
        ]);
    }

    private function normalizeClassLabel(?string $class, ?string $section = null): string
    {
        return preg_replace('/\s+/', ' ', trim((string) $class . ' ' . (string) $section));
    }

    private function releaseDateForWeek(Carbon $weekStart, int $weekNumber, string $releaseDay): Carbon
    {
        if ($weekNumber === 1) {
            return $weekStart->copy();
        }

        if (strtolower($weekStart->format('l')) === strtolower($releaseDay)) {
            return $weekStart->copy()->subWeek();
        }

        return $weekStart->copy()->previous($releaseDay);
    }

    private function matchingDeploymentTargets(TeachingPlan $template, array $selectedClassLabels)
    {
        $templateClass = $this->normalizeClassLabel($template->class);
        $templateSection = trim((string) ($template->section ?? ''));
        $templateLabel = $this->normalizeClassLabel($template->class, $templateSection);

        return collect($selectedClassLabels)
            ->map(fn ($label) => $this->normalizeClassLabel($label))
            ->filter()
            ->filter(function ($selectedLabel) use ($templateClass, $templateSection, $templateLabel) {
                if (filled($templateSection)) {
                    return $selectedLabel === $templateLabel;
                }

                return $selectedLabel === $templateClass ||
                    str_starts_with($selectedLabel, $templateClass . ' ');
            })
            ->map(function ($selectedLabel) use ($templateClass, $templateSection) {
                if (filled($templateSection) || $selectedLabel === $templateClass) {
                    return [
                        'class' => $templateClass,
                        'section' => $templateSection ?: null,
                    ];
                }

                return [
                    'class' => $templateClass,
                    'section' => trim(substr($selectedLabel, strlen($templateClass))),
                ];
            })
            ->unique(fn ($target) => $this->normalizeClassLabel($target['class'], $target['section']))
            ->values();
    }
}
