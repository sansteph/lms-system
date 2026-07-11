<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Course;
use Illuminate\Support\Facades\DB;
use App\Support\DeletesAssessments;
use Illuminate\Support\Facades\Storage;
use App\Models\Content;
use App\Models\CourseContent;
use App\Models\Assessment;
use App\Models\AssessmentResult;
use App\Models\LessonProgress;
use App\Models\ClassTimetable;
use App\Models\ClassContentSession;
use App\Models\TeachingPlan;
use App\Models\TeachingPlanItem;
use App\Models\TeachingPlanWeek;
use App\Models\Institute;
use Illuminate\Validation\ValidationException;

class CourseController extends Controller
{
    use DeletesAssessments;
    public function index(Request $request)
    {
        $courses = Course::with(['courseContents.content'])
            ->when(session('user_role') == 'InstituteAdmin', function ($query) {
                $query->where('institute', session('user_institute'));
            })
            ->when(
                session('user_role') == 'Admin' && $request->filled('institute'),
                function ($query) use ($request) {
                    if ($request->institute == '__template_sources') {
                        $query->where('is_template_source', true);
                    } else {
                        $query->where('institute', $request->institute);
                    }
                }
            )
            ->latest()
            ->get();

        $institutes = session('user_role') == 'Admin'
            ? Institute::where('status', 1)->orderBy('institute_name')->get()
            : collect();

        return view('courses', compact('courses', 'institutes'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'course_title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'target' => 'required|string',
            'assigned_class' => 'nullable|string',
            'price' => 'required|numeric|min:0',
            'availability_type' => 'required',
            'is_active' => 'required',
            'institute' => session('user_role') == 'Admin'
                ? 'required_unless:is_template_source,1|nullable|string|max:255'
                : 'nullable|string|max:255',
            'is_template_source' => 'nullable|boolean',
            'contents' => 'nullable|array',
            'contents.*.title' => 'nullable|string|max:255',
            'contents.*.description' => 'nullable|string|max:2000',
            'contents.*.content_type' => 'nullable|string|max:100',
            'contents.*.sort_order' => 'nullable|integer|min:1',
            'contents.*.status' => 'nullable|in:active,draft,archived',
            'contents.*.file' => 'nullable|file|extensions:pdf|max:51200',
            'contents.*.student_file' => 'nullable|file|extensions:pdf|max:51200',
        ]);

        $this->ensureCourseContentFilesWereReceived($request, false);

        DB::transaction(function () use ($request) {
            $isTemplateSource = session('user_role') == 'Admin' && $request->boolean('is_template_source');

            $course = Course::create([
                'institute' => $isTemplateSource
                    ? null
                    : (session('user_role') == 'InstituteAdmin'
                        ? session('user_institute')
                        : $request->institute),
                'course_title' => $request->course_title,
                'description' => $request->description,
                'target' => $request->target,
                'assigned_class' => $request->assigned_class,
                'price' => $request->price,
                'availability_type' => $request->availability_type,
                'is_active' => $request->is_active,
                'is_template_source' => $isTemplateSource,
                'certificate_enabled' => 1,
                'status' => 1,
            ]);

            $nextOrder = 1;

            foreach ($request->input('contents', []) as $index => $contentData) {
                if (!$request->hasFile("contents.{$index}.file")) {
                    continue;
                }

                $this->createUploadedCourseContent($request, $course, $contentData, $index, $nextOrder);
                $nextOrder++;
            }
        });

        return redirect()->back()
            ->with('success', 'Course created successfully');
    }

    public function update(Request $request, $id)
    {
        $course = Course::findOrFail($id);

        if (
            session('user_role') == 'InstituteAdmin' &&
            $course->institute != session('user_institute')
        ) {
            abort(403, 'Unauthorized action.');
        }

        $request->validate([
            'course_title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'target' => 'required|string',
            'assigned_class' => 'nullable|string',
            'price' => 'required|numeric|min:0',
            'availability_type' => 'required',
            'is_active' => 'required',
            'institute' => session('user_role') == 'Admin'
                ? 'required_unless:is_template_source,1|nullable|string|max:255'
                : 'nullable|string|max:255',
            'is_template_source' => 'nullable|boolean',
        ]);

        $isTemplateSource = session('user_role') == 'Admin' && $request->boolean('is_template_source');

        $course->update([
            'institute' => $isTemplateSource
                ? null
                : (session('user_role') == 'InstituteAdmin'
                    ? session('user_institute')
                    : $request->institute),
            'course_title' => $request->course_title,
            'description' => $request->description,
            'target' => $request->target,
            'assigned_class' => $request->assigned_class,
            'price' => $request->price,
            'availability_type' => $request->availability_type,
            'is_active' => $request->is_active,
            'is_template_source' => $isTemplateSource,
            'status' => $request->is_active,
        ]);

        return redirect()->back()
            ->with('success', 'Course updated successfully');
    }

    public function delete($id)
    {
        $course = Course::findOrFail($id);

        if (
            session('user_role') == 'InstituteAdmin' &&
            $course->institute != session('user_institute')
        ) {
            abort(403, 'Unauthorized action.');
        }

        DB::transaction(function () use ($course, $id) {

            $contents = Content::where('course_id', $id)->get();

            foreach ($contents as $content) {

                Assessment::where('content_id', $content->id)
                    ->update(['content_id' => null]);

                LessonProgress::where('content_id', $content->id)->delete();

                ClassContentSession::where('content_id', $content->id)->delete();

                ClassTimetable::where('content_id', $content->id)->delete();

                $this->deleteTeachingPlansByContent($content->id);

                CourseContent::where('content_id', $content->id)->delete();

                $this->deleteContentStoragePath($content->file_path);

                $this->deleteContentStoragePath($content->preview_pdf_path);

                $this->deleteContentStoragePath($content->student_file_path);

                $this->deleteContentStoragePath($content->student_preview_pdf_path);

                $content->delete();
            }

            CourseContent::where('course_id', $id)->delete();

            $this->deleteTeachingPlansByCourse($id);

            $course->delete();
        });

        return redirect()->back()
            ->with('success', 'Course and all related content deleted successfully.');
    }

    public function uploadCourseContent(Request $request, $id)
    {
        $course = Course::findOrFail($id);
        $this->authorizeCourse($course);

        $request->validate([
            'contents' => 'required|array|min:1',
            'contents.*.title' => 'nullable|string|max:255',
            'contents.*.description' => 'nullable|string|max:2000',
            'contents.*.content_type' => 'nullable|string|max:100',
            'contents.*.assigned_class' => 'nullable|string|max:255',
            'contents.*.section' => 'nullable|string|max:100',
            'contents.*.sort_order' => 'nullable|integer|min:1',
            'contents.*.status' => 'required|in:active,draft,archived',
            'contents.*.file' => 'required|file|extensions:pdf|max:51200',
            'contents.*.student_file' => 'nullable|file|extensions:pdf|max:51200',
        ]);

        $this->ensureCourseContentFilesWereReceived($request, true);

        DB::transaction(function () use ($request, $course) {
            $nextOrder = ((int) CourseContent::where('course_id', $course->id)->max('sort_order')) + 1;

            foreach ($request->contents as $index => $contentData) {
                $this->createUploadedCourseContent($request, $course, $contentData, $index, $nextOrder);

                $nextOrder = ((int) CourseContent::where('course_id', $course->id)->max('sort_order')) + 1;
            }
        });

        return redirect()->back()
            ->with('success', 'Content uploaded and added to course.');
    }

    public function updateContentOrder(Request $request, $id, CourseContent $courseContent)
    {
        $course = Course::findOrFail($id);
        $this->authorizeCourse($course);

        if ($courseContent->course_id != $course->id) {
            abort(404);
        }

        $request->validate([
            'sort_order' => 'required|integer|min:1',
            'status' => 'required|in:active,draft,archived',
        ]);

        $exists = CourseContent::where('course_id', $course->id)
            ->where('sort_order', $request->sort_order)
            ->where('id', '!=', $courseContent->id)
            ->exists();

        if ($exists) {
            throw ValidationException::withMessages([
                'sort_order' => 'That lesson order is already used in this course.',
            ]);
        }

        $courseContent->update([
            'sort_order' => $request->sort_order,
            'status' => $request->status,
        ]);

        if ($courseContent->content) {
            $courseContent->content->update([
                'lesson_order' => $request->sort_order,
                'status' => $request->status == 'active' ? 1 : 0,
            ]);
        }

        return redirect()->back()
            ->with('success', 'Course content updated.');
    }

    public function detachContent($id, CourseContent $courseContent)
    {
        $course = Course::findOrFail($id);
        $this->authorizeCourse($course);

        if ($courseContent->course_id != $course->id) {
            abort(404);
        }

        DB::transaction(function () use ($courseContent) {
            $this->deleteTeachingPlansByCourseContent($courseContent->id);

            if ($courseContent->content_id) {
                Assessment::where('content_id', $courseContent->content_id)
                    ->update(['content_id' => null]);
                LessonProgress::where('content_id', $courseContent->content_id)->delete();
                ClassContentSession::where('content_id', $courseContent->content_id)->delete();
                $this->deleteTeachingPlansByContent($courseContent->content_id);

                if ($courseContent->content) {
                    $this->deleteContentStoragePath($courseContent->content->file_path);
                    $this->deleteContentStoragePath($courseContent->content->preview_pdf_path);
                    $this->deleteContentStoragePath($courseContent->content->student_file_path);
                    $this->deleteContentStoragePath($courseContent->content->student_preview_pdf_path);
                    $courseContent->content->delete();
                }
            }

            $courseContent->delete();
        });

        return redirect()->back()
            ->with('success', 'Content removed from course.');
    }

    private function deleteContentStoragePath($path)
    {
        if (!$path) {
            return;
        }

        foreach (['local', 'public'] as $disk) {
            if (Storage::disk($disk)->exists($path)) {
                Storage::disk($disk)->delete($path);
            }
        }
    }

    private function authorizeCourse(Course $course): void
    {
        if (session('user_role') == 'Admin') {
            return;
        }

        if (
            session('user_role') == 'InstituteAdmin' &&
            $course->institute == session('user_institute')
        ) {
            return;
        }

        abort(403, 'You are not authorized to manage this course.');
    }

    private function deleteTeachingPlansByCourse(int $courseId): void
    {
        TeachingPlan::where('course_id', $courseId)
            ->get()
            ->each(function (TeachingPlan $plan) {
                $this->deleteTeachingPlanGraph($plan);
            });
    }

    private function deleteTeachingPlansByCourseContent(int $courseContentId): void
    {
        TeachingPlan::where('course_content_id', $courseContentId)
            ->get()
            ->each(function (TeachingPlan $plan) {
                $this->deleteTeachingPlanGraph($plan);
            });

        $this->deleteTeachingPlanItemsForContent(null, $courseContentId);
    }

    private function deleteTeachingPlansByContent(int $contentId): void
    {
        TeachingPlan::where('content_id', $contentId)
            ->get()
            ->each(function (TeachingPlan $plan) {
                $this->deleteTeachingPlanGraph($plan);
            });

        $this->deleteTeachingPlanItemsForContent($contentId);
    }

    private function deleteTeachingPlanGraph(TeachingPlan $plan): void
    {
        ClassContentSession::where('teaching_plan_id', $plan->id)
            ->update([
                'teaching_plan_id' => null,
                'teaching_plan_week_id' => null,
                'teaching_plan_item_id' => null,
            ]);

        TeachingPlanItem::where('teaching_plan_id', $plan->id)->delete();
        TeachingPlanWeek::where('teaching_plan_id', $plan->id)->delete();
        $plan->delete();
    }

    private function deleteTeachingPlanItemsForContent(?int $contentId = null, ?int $courseContentId = null): void
    {
        $items = TeachingPlanItem::where(function ($query) use ($contentId, $courseContentId) {
                if ($contentId) {
                    $query->where('content_id', $contentId);
                }

                if ($courseContentId) {
                    $method = $contentId ? 'orWhere' : 'where';
                    $query->{$method}('course_content_id', $courseContentId);
                }
            })
            ->get();

        if ($items->isEmpty()) {
            return;
        }

        $itemIds = $items->pluck('id');
        $weekIds = $items->pluck('teaching_plan_week_id')->filter()->unique();
        $planIds = $items->pluck('teaching_plan_id')->filter()->unique();

        ClassContentSession::whereIn('teaching_plan_item_id', $itemIds)
            ->update([
                'teaching_plan_item_id' => null,
                'teaching_plan_week_id' => null,
            ]);

        TeachingPlanItem::whereIn('id', $itemIds)->delete();

        TeachingPlanWeek::whereIn('id', $weekIds)
            ->get()
            ->each(function (TeachingPlanWeek $week) {
                if (!$week->items()->exists()) {
                    $week->delete();
                }
            });

        TeachingPlan::whereIn('id', $planIds)
            ->get()
            ->each(function (TeachingPlan $plan) {
                if (!$plan->items()->exists()) {
                    $this->deleteTeachingPlanGraph($plan);
                }
            });
    }

    private function storePrivateContentFile($file): array
    {
        $fileName = time() . '_' . uniqid() . '_' . $file->getClientOriginalName();
        $filePath = $file->storeAs('contents', $fileName, 'local');

        $previewPdfPath = strtolower($file->getClientOriginalExtension()) === 'pdf'
            ? $filePath
            : null;

        return [$filePath, $previewPdfPath];
    }

    private function ensureCourseContentFilesWereReceived(Request $request, bool $fileRequired): void
    {
        $rows = collect($request->input('contents', []));

        if ($rows->isEmpty()) {
            return;
        }

        $missingRows = [];

        foreach ($rows as $index => $contentData) {
            $hasMetadata = collect($contentData)
                ->except(['file', 'student_file', 'status'])
                ->filter(fn ($value) => filled($value))
                ->isNotEmpty();

            if (($fileRequired || $hasMetadata) && !$request->hasFile("contents.{$index}.file")) {
                $missingRows[] = ((int) $index) + 1;
            }
        }

        if (!empty($missingRows)) {
            throw ValidationException::withMessages([
                'contents' => 'Some selected lesson files were not received by the server. Missing file in row(s): '
                    . implode(', ', $missingRows)
                    . '. If you selected more than 20 files, increase PHP max_file_uploads or upload in smaller batches.',
            ]);
        }
    }

    private function createUploadedCourseContent(Request $request, Course $course, array $contentData, int|string $index, int $fallbackOrder): CourseContent
    {
        $teacherFile = $request->file("contents.{$index}.file");
        [$filePath, $previewPdfPath] = $this->storePrivateContentFile($teacherFile);

        $studentFile = $request->file("contents.{$index}.student_file");
        $studentFilePath = null;
        $studentPreviewPdfPath = null;

        if ($studentFile) {
            [$studentFilePath, $studentPreviewPdfPath] = $this->storePrivateContentFile($studentFile);
        }

        $sortOrder = !empty($contentData['sort_order']) ? (int) $contentData['sort_order'] : $fallbackOrder;
        $title = trim((string) ($contentData['title'] ?? ''));
        $type = trim((string) ($contentData['content_type'] ?? ''));
        $status = $contentData['status'] ?? 'active';

        $content = Content::create([
            'course_id' => $course->id,
            'content_title' => $title !== '' ? $title : $this->titleFromFileName($teacherFile->getClientOriginalName()),
            'description' => $contentData['description'] ?? null,
            'lesson_order' => $sortOrder,
            'content_type' => $type !== '' ? $type : strtoupper($teacherFile->getClientOriginalExtension()),
            'assigned_class' => $contentData['assigned_class'] ?? $course->assigned_class,
            'institute' => $course->institute,
            'file_path' => $filePath,
            'preview_pdf_path' => $previewPdfPath,
            'student_file_path' => $studentFilePath,
            'student_preview_pdf_path' => $studentPreviewPdfPath,
            'original_file_name' => $teacherFile->getClientOriginalName(),
            'uploaded_by' => session('user_id'),
            'is_released' => 0,
            'status' => $status == 'active',
        ]);

        return CourseContent::create([
            'course_id' => $course->id,
            'content_id' => $content->id,
            'sort_order' => $sortOrder,
            'status' => $status,
            'created_by' => session('user_id'),
        ]);
    }

    private function titleFromFileName(string $fileName): string
    {
        $title = pathinfo($fileName, PATHINFO_FILENAME);
        $title = preg_replace('/^\s*\d+[\s._-]*/', '', $title);
        $title = str_replace(['_', '-'], ' ', $title);

        return trim($title) ?: pathinfo($fileName, PATHINFO_FILENAME);
    }

}
