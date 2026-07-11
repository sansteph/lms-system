<?php

namespace App\Services;

use App\Models\Content;
use App\Models\Course;
use App\Models\CourseContent;
use App\Models\Institute;
use App\Models\TeachingPlan;
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
                ->all();

            $templates = TeachingPlan::with(['course.courseContents.content', 'weeks.items'])
                ->where('is_template', true)
                ->where('status', 'active')
                ->get()
                ->filter(function (TeachingPlan $template) use ($selectedClassLabels) {
                    return in_array(
                        $this->normalizeClassLabel($template->class, $template->section),
                        $selectedClassLabels,
                        true
                    );
                });

            if ($templates->isEmpty()) {
                $summary['skipped']++;
                $summary['messages'][] = 'Skipped ' . $institute->institute_name . ': no matching active templates found.';
                continue;
            }

            foreach ($templates as $template) {
                $result = $this->deployTemplate($template, $institute, $deployedBy);
                $summary[$result['status'] === 'deployed' ? 'deployed' : 'skipped']++;
                $summary['messages'][] = $result['message'];
            }
        }

        return $summary;
    }

    public function deployTemplate(TeachingPlan $template, Institute $institute, ?int $deployedBy = null): array
    {
        if (!$template->is_template) {
            return [
                'status' => 'skipped',
                'message' => 'Skipped non-template Teaching Plan: ' . ($template->title ?? $template->id),
            ];
        }

        return DB::transaction(function () use ($template, $institute, $deployedBy) {
            $existingPlan = TeachingPlan::where('is_template', false)
                ->where('parent_template_id', $template->id)
                ->where('institute', $institute->institute_name)
                ->where('class', $template->class)
                ->where(function ($query) use ($template) {
                    if (filled($template->section)) {
                        $query->where('section', $template->section);
                    } else {
                        $query->whereNull('section')
                            ->orWhere('section', '');
                    }
                })
                ->whereNotNull('course_id')
                ->first();

            if ($existingPlan) {
                return [
                    'status' => 'skipped',
                    'message' => 'Skipped duplicate deployment: ' . ($template->title ?? $template->course->course_title ?? 'Template') . ' for ' . $institute->institute_name . ' / ' . $template->class,
                ];
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
                'assigned_class' => $this->normalizeClassLabel($template->class, $template->section),
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

                if ($this->copyContentToCourse($course, $templateContent, (int) $templateCourseContent->sort_order, $deployedBy)) {
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

            $plan = app(TeachingPlanBuilderService::class)->buildForCourse(
                $course,
                [
                    'parent_template_id' => $template->id,
                    'title' => $template->title,
                    'institute_id' => $institute->id,
                    'class' => $template->class,
                    'section' => $template->section,
                    'start_date' => now()->toDateString(),
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
                'message' => 'Deployed ' . ($template->title ?? $course->course_title) . ' to ' . $institute->institute_name . ' / ' . $this->normalizeClassLabel($template->class, $template->section) . ' as plan #' . $plan->id,
            ];
        });
    }

    private function copyContentToCourse(Course $course, Content $templateContent, int $sortOrder, ?int $createdBy): ?CourseContent
    {
        $filePath = $this->copyStoredFile($templateContent->file_path, 'contents');

        if (!$filePath) {
            return null;
        }

        $previewPath = $templateContent->preview_pdf_path === $templateContent->file_path
            ? $filePath
            : $this->copyStoredFile($templateContent->preview_pdf_path, 'content-previews');
        $studentFilePath = $this->copyStoredFile($templateContent->student_file_path, 'contents');
        $studentPreviewPath = $templateContent->student_preview_pdf_path === $templateContent->student_file_path
            ? $studentFilePath
            : $this->copyStoredFile($templateContent->student_preview_pdf_path, 'content-previews');

        $content = Content::create([
            'course_id' => $course->id,
            'content_title' => $templateContent->content_title,
            'description' => $templateContent->description,
            'lesson_order' => $sortOrder,
            'content_type' => $templateContent->content_type ?: 'PPT',
            'assigned_class' => $course->assigned_class,
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
        ]);
    }

    private function copyStoredFile(?string $sourcePath, string $targetDirectory): ?string
    {
        if (!$sourcePath || !Storage::disk('local')->exists($sourcePath)) {
            return null;
        }

        $newPath = trim($targetDirectory, '/') . '/' . time() . '_' . uniqid() . '_' . basename($sourcePath);
        Storage::disk('local')->copy($sourcePath, $newPath);

        return $newPath;
    }

    private function normalizeClassLabel(?string $class, ?string $section = null): string
    {
        return preg_replace('/\s+/', ' ', trim((string) $class . ' ' . (string) $section));
    }
}
