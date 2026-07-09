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

            $templates = TeachingPlan::with(['course.courseContents.content', 'weeks.items'])
                ->where('is_template', true)
                ->where('status', 'active')
                ->whereIn('class', $selectedClasses->all())
                ->get();

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
                ->where('course_id', '!=', null)
                ->first();

            if ($existingPlan) {
                return [
                    'status' => 'skipped',
                    'message' => 'Skipped duplicate deployment: ' . ($template->title ?? $template->course->course_title ?? 'Template') . ' for ' . $institute->institute_name . ' / ' . $template->class,
                ];
            }

            $templateCourse = $template->course()->with('courseContents.content')->firstOrFail();

            $course = Course::create([
                'institute' => $institute->institute_name,
                'course_title' => $templateCourse->course_title,
                'description' => $templateCourse->description,
                'target' => $templateCourse->target ?? 'Both',
                'assigned_class' => $template->class,
                'price' => $templateCourse->price ?? 0,
                'availability_type' => 'Institute',
                'is_active' => 1,
                'is_template_source' => false,
                'certificate_enabled' => $templateCourse->certificate_enabled ?? 1,
                'status' => 1,
            ]);

            foreach ($templateCourse->courseContents()->with('content')->where('status', 'active')->orderBy('sort_order')->get() as $templateCourseContent) {
                $templateContent = $templateCourseContent->content;

                if (!$templateContent || !$templateContent->file_path) {
                    continue;
                }

                $this->copyContentToCourse($course, $templateContent, (int) $templateCourseContent->sort_order, $deployedBy);
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
                'message' => 'Deployed ' . ($template->title ?? $course->course_title) . ' to ' . $institute->institute_name . ' / ' . $template->class . ' as plan #' . $plan->id,
            ];
        });
    }

    private function copyContentToCourse(Course $course, Content $templateContent, int $sortOrder, ?int $createdBy): CourseContent
    {
        $filePath = $this->copyStoredFile($templateContent->file_path, 'contents');
        $previewPath = $this->copyStoredFile($templateContent->preview_pdf_path, 'content-previews');
        $studentFilePath = $this->copyStoredFile($templateContent->student_file_path, 'contents');
        $studentPreviewPath = $this->copyStoredFile($templateContent->student_preview_pdf_path, 'content-previews');

        $content = Content::create([
            'course_id' => $course->id,
            'content_title' => $templateContent->content_title,
            'description' => $templateContent->description,
            'lesson_order' => $sortOrder,
            'content_type' => $templateContent->content_type ?: 'PPT',
            'assigned_class' => $course->assigned_class,
            'institute' => $course->institute,
            'file_path' => $filePath,
            'preview_pdf_path' => $previewPath ?: $this->createPreviewPdf($filePath),
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

    private function createPreviewPdf(?string $filePath): ?string
    {
        if (!$filePath) {
            return null;
        }

        $extension = strtolower(pathinfo($filePath, PATHINFO_EXTENSION));

        if (!in_array($extension, ['ppt', 'pptx', 'doc', 'docx'])) {
            return null;
        }

        $inputPath = Storage::disk('local')->path($filePath);
        $outputDir = Storage::disk('local')->path('content-previews');

        if (!file_exists($outputDir)) {
            mkdir($outputDir, 0775, true);
        }

        $command = '"C:\\Program Files\\LibreOffice\\program\\soffice.exe"'
            . ' --headless'
            . ' --convert-to pdf'
            . ' --outdir ' . escapeshellarg($outputDir)
            . ' ' . escapeshellarg($inputPath);

        exec($command, $output, $resultCode);

        $pdfFileName = pathinfo($filePath, PATHINFO_FILENAME) . '.pdf';
        $convertedPdfPath = $outputDir . DIRECTORY_SEPARATOR . $pdfFileName;

        return $resultCode === 0 && file_exists($convertedPdfPath)
            ? 'content-previews/' . $pdfFileName
            : null;
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
}
