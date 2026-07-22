<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Content;
use App\Models\Course;
use App\Models\CourseContent;
use App\Models\User;
use App\Models\Student;
use App\Models\SchoolClass;
use App\Models\CourseEnrollment;
use Illuminate\Support\Facades\Storage;
use App\Models\Assessment;
use App\Models\AssessmentResult;
use App\Models\LessonProgress;
use App\Models\ClassContentSession;
use App\Models\ClassTimetable;
use App\Models\TeachingPlan;
use App\Models\TeachingPlanItem;
use App\Models\TeachingPlanWeek;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use App\Support\DeletesAssessments;

class ContentController extends Controller
{
    use DeletesAssessments;
    public function index(Request $request)
    {
        $search = $request->search;
        $selectedCourseId = $request->course_id;

        $courses = Course::where('status', 1)
            ->when(session('user_role') == 'InstituteAdmin', function ($query) {
                $query->where('institute', session('user_institute'));
            })
            ->orderBy('course_title')
            ->get();

        $contents = Content::with(['course', 'courseContent', 'aiSummary'])
            ->when(session('user_role') == 'InstituteAdmin', function ($query) {
                $query->where('institute', session('user_institute'));
            })
            ->when($selectedCourseId, function ($query, $courseId) {
                $query->where('course_id', $courseId);
            })
            ->when($search, function ($query, $search) {
                $query->where(function ($q) use ($search) {
                    $q->where('content_title', 'like', "%{$search}%")
                      ->orWhere('content_type', 'like', "%{$search}%")
                      ->orWhere('assigned_class', 'like', "%{$search}%")
                      ->orWhereHas('course', function ($courseQuery) use ($search) {
                          $courseQuery->where('course_title', 'like', "%{$search}%");
                      });
                });
            })
            ->orderBy('course_id')
            ->orderBy('lesson_order')
            ->get();

        return view('content', compact('contents', 'courses', 'selectedCourseId'));
    }

    public function bulkStore(Request $request)
    {
        $request->validate([
            'course_id' => 'required|exists:courses,id',
            'institute' => 'nullable|string|max:255',
            'contents' => 'required|array|min:1',
            'contents.*.content_title' => 'required|string|max:255',
            'contents.*.description' => 'nullable|string',
            'contents.*.content_type' => 'required|string|max:100',
            'contents.*.assigned_class' => 'nullable|string|max:255',
            'contents.*.section' => 'nullable|string|max:50',
            'contents.*.lesson_order' => 'required|integer|min:1',
            'contents.*.status' => 'required|in:active,draft,archived',
            'contents.*.file' => 'required|file|extensions:pdf|max:51200',
            'contents.*.student_file' => 'nullable|file|extensions:pdf|max:51200',
        ]);

        $course = Course::findOrFail($request->course_id);
        $this->authorizeCourseManagement($course);

        if (
            session('user_role') == 'Admin' &&
            !$course->is_template_source &&
            blank($request->institute)
        ) {
            return redirect()->back()
                ->withInput()
                ->withErrors(['institute' => 'Institute is required for institute courses.']);
        }

        $this->validateUniqueSortOrders($course->id, collect($request->input('contents'))->pluck('lesson_order')->all());

        $institute = $course->is_template_source
            ? null
            : (session('user_role') == 'InstituteAdmin'
                ? session('user_institute')
                : $request->institute);

        $storedPaths = [];

        try {
            DB::transaction(function () use ($request, $course, $institute, &$storedPaths) {
                foreach ($request->input('contents', []) as $index => $contentData) {
                    $teacherFile = $request->file("contents.{$index}.file");
                    [$filePath, $previewPdfPath] = $this->storePrivateContentFile($teacherFile);
                    $storedPaths[] = $filePath;
                    $storedPaths[] = $previewPdfPath;

                    $studentFilePath = null;
                    $studentPreviewPdfPath = null;
                    $studentFile = $request->file("contents.{$index}.student_file");

                    if ($studentFile) {
                        [$studentFilePath, $studentPreviewPdfPath] = $this->storePrivateContentFile($studentFile);
                        $storedPaths[] = $studentFilePath;
                        $storedPaths[] = $studentPreviewPdfPath;
                    }

                    $content = Content::create([
                        'course_id' => $course->id,
                        'institute' => $institute,
                        'content_title' => $contentData['content_title'],
                        'description' => $contentData['description'] ?? null,
                        'lesson_order' => (int) $contentData['lesson_order'],
                        'content_type' => $contentData['content_type'],
                        'assigned_class' => $contentData['assigned_class'] ?? $course->assigned_class,
                        'file_path' => $filePath,
                        'preview_pdf_path' => $previewPdfPath,
                        'student_file_path' => $studentFilePath,
                        'student_preview_pdf_path' => $studentPreviewPdfPath,
                        'original_file_name' => $teacherFile->getClientOriginalName(),
                        'uploaded_by' => session('user_id'),
                        'is_released' => 0,
                        'status' => $contentData['status'] == 'active',
                    ]);

                    CourseContent::create([
                        'course_id' => $course->id,
                        'content_id' => $content->id,
                        'sort_order' => (int) $contentData['lesson_order'],
                        'status' => $contentData['status'],
                        'created_by' => session('user_id'),
                    ]);
                }
            });
        } catch (\Throwable $exception) {
            foreach ($storedPaths as $path) {
                $this->deleteContentStoragePath($path);
            }

            return redirect()->back()
                ->withInput()
                ->withErrors(['bulk_upload' => 'Bulk upload failed. Please check the files and try again.']);
        }

        return redirect()->back()
            ->with('success', count($request->input('contents', [])) . ' lessons uploaded successfully.');
    }

    public function updateCourseContentOrder(Request $request, CourseContent $courseContent)
    {
        $courseContent->load('course');
        $this->authorizeCourseManagement($courseContent->course);

        $request->validate([
            'sort_order' => 'required|integer|min:1',
            'status' => 'required|in:active,draft,archived',
        ]);

        $conflict = CourseContent::where('course_id', $courseContent->course_id)
            ->where('sort_order', $request->sort_order)
            ->where('id', '!=', $courseContent->id)
            ->exists();

        if ($conflict) {
            return redirect()->back()
                ->withErrors(['sort_order' => 'Another content item already uses this lesson order for the selected course.']);
        }

        $courseContent->update([
            'sort_order' => $request->sort_order,
            'status' => $request->status,
        ]);

        if ($courseContent->content) {
            $courseContent->content->update([
                'lesson_order' => $request->sort_order,
                'status' => $request->status == 'active',
            ]);
        }

        return redirect()->back()
            ->with('success', 'Course content order updated successfully.');
    }

    public function detachCourseContent(CourseContent $courseContent)
    {
        $courseContent->load(['course', 'content']);
        $this->authorizeCourseManagement($courseContent->course);

        DB::transaction(function () use ($courseContent) {
            if ($courseContent->content) {
                Assessment::where('content_id', $courseContent->content_id)
                    ->update(['content_id' => null]);

                LessonProgress::where('content_id', $courseContent->content_id)->delete();
                ClassContentSession::where('content_id', $courseContent->content_id)->delete();
                $this->deleteTeachingPlanItemsForContent(
                    $courseContent->content_id,
                    $courseContent->id
                );

                $this->deleteContentStoragePath($courseContent->content->file_path, $courseContent->content->id);
                $this->deleteContentStoragePath($courseContent->content->preview_pdf_path, $courseContent->content->id);
                $this->deleteContentStoragePath($courseContent->content->student_file_path, $courseContent->content->id);
                $this->deleteContentStoragePath($courseContent->content->student_preview_pdf_path, $courseContent->content->id);

                $courseContent->content->delete();
            }

            $courseContent->delete();
        });

        return redirect()->back()
            ->with('success', 'Content detached from course successfully.');
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'content_title' => 'required|string|max:255',
            'description' => 'nullable|string|max:2000',
            'course_id' => 'required|exists:courses,id',
            'lesson_order' => 'required|integer|min:1',
            'content_type' => 'required|string|max:100',
            'assigned_class' => 'required|string|max:255',
            'file' => 'nullable|file|extensions:pdf|max:51200',
            'student_file' => 'nullable|file|extensions:pdf|max:51200',
            'status' => 'required|boolean',
            'institute' => 'nullable|string|max:255',
        ]);

        $content = Content::findOrFail($id);
        $course = Course::findOrFail($request->course_id);
        $this->authorizeCourseManagement($course);

        if (
            session('user_role') == 'Admin' &&
            !$course->is_template_source &&
            blank($request->institute)
        ) {
            return redirect()->back()
                ->withInput()
                ->withErrors(['institute' => 'Institute is required for institute course content.']);
        }

        if (
            session('user_role') == 'InstituteAdmin' &&
            $content->institute != session('user_institute')
        ) {
            abort(403, 'Unauthorized action.');
        }

        $filePath = $content->file_path;
        $previewPdfPath = $content->preview_pdf_path;
        $studentFilePath = $content->student_file_path;
        $studentPreviewPdfPath = $content->student_preview_pdf_path;

        if ($request->hasFile('file')) {
            $this->deleteContentStoragePath($content->file_path, $content->id);
            $this->deleteContentStoragePath($content->preview_pdf_path, $content->id);

            [$filePath, $previewPdfPath] = $this->storePrivateContentFile($request->file('file'));
        }

        if ($request->hasFile('student_file')) {
            $this->deleteContentStoragePath($content->student_file_path, $content->id);
            $this->deleteContentStoragePath($content->student_preview_pdf_path, $content->id);

            [$studentFilePath, $studentPreviewPdfPath] = $this->storePrivateContentFile($request->file('student_file'));
        }

        $content->update([
            'institute' => session('user_role') == 'InstituteAdmin'
                ? session('user_institute')
                : ($course->is_template_source ? null : $request->institute),
            'content_title' => $request->content_title,
            'description' => $request->description,
            'course_id' => $request->course_id,
            'lesson_order' => $request->lesson_order,
            'content_type' => $request->content_type,
            'assigned_class' => $request->assigned_class,
            'file_path' => $filePath,
            'preview_pdf_path' => $previewPdfPath,
            'student_file_path' => $studentFilePath,
            'student_preview_pdf_path' => $studentPreviewPdfPath,
            'original_file_name' => $request->hasFile('file')
                ? $request->file('file')->getClientOriginalName()
                : $content->original_file_name,
            'uploaded_by' => $content->uploaded_by ?: session('user_id'),
            'status' => $request->status,
        ]);

        CourseContent::where('content_id', $content->id)->update([
            'sort_order' => $request->lesson_order,
            'status' => $request->status ? 'active' : 'archived',
        ]);

        return redirect()->back()
            ->with('success', 'Content updated successfully');
    }

    public function showPreview(Request $request, Content $content, $audience = 'auto')
    {
        if (!in_array($audience, ['auto', 'teacher', 'student'])) {
            abort(404);
        }

        $audience = $this->resolveContentAudience($audience);

        if (!$this->canViewContent($content, $audience)) {
            abort(403, 'You are not authorized to view this content.');
        }

        $paths = $this->contentPathsForAudience($content, $audience);

        if (!$paths['file']) {
            abort(404);
        }

        $previewPath = $paths['preview'] ?: $paths['file'];
        $extension = strtolower(pathinfo($previewPath, PATHINFO_EXTENSION));

        if ($extension !== 'pdf') {
            $previewUnavailableMessage = 'Preview is available only for PDF content. Please upload a PDF version of this material.';

            return view('content.secure-preview', compact(
                'content',
                'audience',
                'extension',
                'previewUnavailableMessage'
            ));
        }

        $sourceUrl = route('content.preview.stream', [$content->id, $audience]);
        $allowFullscreen = $audience == 'student';

        return view('content.secure-preview', compact(
            'content',
            'audience',
            'extension',
            'sourceUrl',
            'allowFullscreen'
        ));
    }

    public function streamPreview(Request $request, Content $content, $audience = 'auto')
    {
        $fetchDestination = $request->headers->get('sec-fetch-dest');

        if ($fetchDestination && !in_array($fetchDestination, ['iframe', 'embed', 'object', 'empty'])) {
            abort(403, 'Content previews must be viewed inside the LMS.');
        }

        if (!in_array($audience, ['auto', 'teacher', 'student'])) {
            abort(404);
        }

        $audience = $this->resolveContentAudience($audience);

        if (!$this->canViewContent($content, $audience)) {
            abort(403, 'You are not authorized to view this content.');
        }

        $paths = $this->contentPathsForAudience($content, $audience);

        if (!$paths['file']) {
            abort(404);
        }

        $extension = strtolower(pathinfo($paths['file'], PATHINFO_EXTENSION));
        $storagePath = $paths['preview'] ?: $paths['file'];

        if (!$storagePath) {
            abort(404);
        }

        if (strtolower(pathinfo($storagePath, PATHINFO_EXTENSION)) !== 'pdf') {
            abort(404);
        }

        $resolved = $this->resolveContentStoragePath($storagePath);

        if (!$resolved) {
            abort(404);
        }

        $mimeType = mime_content_type($resolved['path']) ?: 'application/octet-stream';

        return response()->file($resolved['path'], [
            'Content-Type' => $mimeType,
            'Content-Disposition' => 'inline',
            'Cache-Control' => 'private, no-store, no-cache, must-revalidate',
            'Pragma' => 'no-cache',
            'X-Content-Type-Options' => 'nosniff',
            'Content-Security-Policy' => "frame-ancestors 'self'",
        ]);
    }

    public function showFile(Request $request, Content $content, $audience = 'auto', $variant = 'file')
    {
        $fetchDestination = $request->headers->get('sec-fetch-dest');

        if ($fetchDestination && $fetchDestination == 'document') {
            abort(403, 'Content files must be viewed inside the LMS.');
        }

        if (in_array($audience, ['file', 'preview'])) {
            $variant = $audience;
            $audience = 'auto';
        }

        if (!in_array($audience, ['auto', 'teacher', 'student'])) {
            abort(404);
        }

        if (!in_array($variant, ['file', 'preview'])) {
            abort(404);
        }

        $audience = $this->resolveContentAudience($audience);

        if (!$this->canViewContent($content, $audience)) {
            abort(403, 'You are not authorized to view this content.');
        }

        $paths = $this->contentPathsForAudience($content, $audience);

        if (!$paths['file']) {
            abort(404);
        }

        if ($variant == 'preview') {
            abort(404);
        }

        $storagePath = $variant == 'preview'
            ? $paths['preview']
            : $paths['file'];

        if (!$storagePath) {
            abort(404);
        }

        $resolved = $this->resolveContentStoragePath($storagePath);

        if (!$resolved) {
            abort(404);
        }

        $mimeType = mime_content_type($resolved['path']) ?: 'application/octet-stream';
        $fileName = basename($resolved['path']);
        $safeFileName = str_replace('"', '', $fileName);

        return response()->file($resolved['path'], [
            'Content-Type' => $mimeType,
            'Content-Disposition' => 'inline; filename="' . $safeFileName . '"',
            'Cache-Control' => 'private, no-store, no-cache, must-revalidate',
            'Pragma' => 'no-cache',
            'X-Content-Type-Options' => 'nosniff',
            'Content-Security-Policy' => "frame-ancestors 'self'",
        ]);
    }

    public function delete($id)
    {
        $content = Content::findOrFail($id);

        if (
            session('user_role') == 'InstituteAdmin' &&
            $content->institute != session('user_institute')
        ) {
            abort(403, 'Unauthorized action.');
        }

        DB::transaction(function () use ($content, $id) {

            Assessment::where('content_id', $id)
                ->update(['content_id' => null]);

            LessonProgress::where('content_id', $id)->delete();

            ClassContentSession::where('content_id', $id)->delete();

            ClassTimetable::where('content_id', $id)->delete();

            $this->deleteTeachingPlanItemsForContent($id);

            CourseContent::where('content_id', $id)->delete();

            $this->deleteContentStoragePath($content->file_path, $content->id);
            $this->deleteContentStoragePath($content->preview_pdf_path, $content->id);
            $this->deleteContentStoragePath($content->student_file_path, $content->id);
            $this->deleteContentStoragePath($content->student_preview_pdf_path, $content->id);

            $content->delete();
        });

        return redirect()->back()
            ->with('success', 'Content and all related records deleted successfully.');
    }

    private function authorizeCourseManagement(Course $course): void
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

        abort(403, 'You are not authorized to manage contents for this course.');
    }

    private function validateUniqueSortOrders(int $courseId, array $sortOrders): void
    {
        $sortOrders = array_map('intval', $sortOrders);

        if (count($sortOrders) !== count(array_unique($sortOrders))) {
            throw ValidationException::withMessages([
                'sort_order' => 'Lesson orders must be unique in this upload.',
            ]);
        }

        $existing = CourseContent::where('course_id', $courseId)
            ->whereIn('sort_order', $sortOrders)
            ->exists();

        if ($existing) {
            throw ValidationException::withMessages([
                'sort_order' => 'One or more lesson orders are already used in the selected course.',
            ]);
        }
    }

    private function storePrivateContentFile($file)
    {
        $fileName = time() . '_' . uniqid() . '_' . $file->getClientOriginalName();
        $filePath = $file->storeAs('contents', $fileName, 'local');

        $previewPdfPath = strtolower($file->getClientOriginalExtension()) === 'pdf'
            ? $filePath
            : null;

        return [$filePath, $previewPdfPath];
    }

    private function deleteTeachingPlanItemsForContent(int $contentId, ?int $courseContentId = null): void
    {
        $items = TeachingPlanItem::where(function ($query) use ($contentId, $courseContentId) {
                $query->where('content_id', $contentId);

                if ($courseContentId) {
                    $query->orWhere('course_content_id', $courseContentId);
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
            ->delete();

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
                    ClassContentSession::where('teaching_plan_id', $plan->id)
                        ->delete();

                    $plan->weeks()->delete();
                    $plan->delete();
                }
            });
    }

    private function resolveContentAudience($audience)
    {
        if ($audience != 'auto') {
            return $audience;
        }

        if (session('student_id') || session('independent_learner_id')) {
            return 'student';
        }

        return 'teacher';
    }

    private function contentPathsForAudience(Content $content, $audience)
    {
        if ($audience == 'student') {
            return [
                'file' => $content->student_file_path ?: $content->file_path,
                'preview' => $content->student_preview_pdf_path ?: $content->preview_pdf_path,
            ];
        }

        return [
            'file' => $content->file_path,
            'preview' => $content->preview_pdf_path,
        ];
    }

    private function canViewContent(Content $content, $audience)
    {
        if (session('user_role') == 'Admin') {
            return true;
        }

        if (session('user_role') == 'InstituteAdmin') {
            return $content->institute == session('user_institute');
        }

        if (session('user_role') == 'Teacher') {
            if ($audience != 'teacher') {
                return false;
            }

            $teacher = User::find(session('user_id'));

            return $teacher && $this->teacherCanViewContent($teacher, $content);
        }

        if (session('student_id')) {
            if ($audience != 'student') {
                return false;
            }

            $student = Student::find(session('student_id'));

            return $student && $this->studentCanViewContent($student, $content);
        }

        if (session('independent_learner_id')) {
            if ($audience != 'student') {
                return false;
            }

            return $content->status == 1 &&
                $content->is_released &&
                ($content->student_file_path || $content->file_path) &&
                CourseEnrollment::where('learner_id', session('independent_learner_id'))
                    ->where('course_id', $content->course_id)
                    ->exists();
        }

        return false;
    }

    private function teacherCanViewContent(User $teacher, Content $content)
    {
        if ($content->institute != $teacher->institute || $content->status != 1) {
            return false;
        }

        return TeachingPlanItem::whereIn('status', ['released', 'completed'])
            ->whereHas('plan', function ($query) use ($teacher) {
                $query->where('institute', $teacher->institute)
                    ->whereIn('status', ['active', 'completed']);
            })
            ->where('content_id', $content->id)
            ->exists();
    }

    private function studentCanViewContent(Student $student, Content $content)
    {
        $contentIds = $this->studentAvailableContentIds($student);

        if (
            $content->status != 1 ||
            !($content->student_file_path || $content->file_path) ||
            !$contentIds->contains((int) $content->id)
        ) {
            return false;
        }

        $previousLesson = Content::where('course_id', $content->course_id)
            ->where('lesson_order', $content->lesson_order - 1)
            ->first();

        if (!$previousLesson) {
            return true;
        }

        return LessonProgress::where('student_id', $student->id)
            ->where('content_id', $previousLesson->id)
            ->where('is_completed', true)
            ->exists();
    }

    private function studentAvailableContentIds(Student $student)
    {
        $assignedClass = preg_replace('/\s+/', ' ', trim($student->class . ' ' . $student->section));

        $teachingPlanContentIds = TeachingPlanItem::where('status', 'completed')
            ->whereNotNull('content_id')
            ->whereHas('plan', function ($query) use ($student, $assignedClass) {
                $query->where('is_template', false)
                    ->where('institute', $student->institute)
                    ->whereIn('status', ['active', 'completed'])
                    ->where(function ($classQuery) use ($assignedClass) {
                        $classQuery
                            ->whereRaw(
                                "REPLACE(TRIM(class), '  ', ' ') = ?",
                                [$assignedClass]
                            )
                            ->orWhereRaw(
                                "REPLACE(TRIM(CONCAT(COALESCE(class, ''), ' ', COALESCE(section, ''))), '  ', ' ') = ?",
                                [$assignedClass]
                            );
                    });
            })
            ->whereHas('content', function ($query) {
                $query->where('status', 1);
            })
            ->pluck('content_id')
            ->unique()
            ->values();

        $legacyCourseIds = Course::where('institute', $student->institute)
            ->whereRaw(
                "REPLACE(TRIM(assigned_class), '  ', ' ') = ?",
                [$assignedClass]
            )
            ->pluck('id');

        $legacyReleasedContentIds = Content::whereIn('course_id', $legacyCourseIds)
            ->where('status', 1)
            ->where('is_released', true)
            ->pluck('id');

        return $teachingPlanContentIds
            ->merge($legacyReleasedContentIds)
            ->unique()
            ->values();
    }

    private function studentAssignedCourse(Student $student)
    {
        $assignedClass = preg_replace('/\s+/', ' ', trim($student->class . ' ' . $student->section));

        return Course::where('institute', $student->institute)
            ->whereRaw(
                "REPLACE(TRIM(assigned_class), '  ', ' ') = ?",
                [$assignedClass]
            )
            ->first();
    }

    private function resolveContentStoragePath($path)
    {
        if (!Storage::disk('local')->exists($path)) {
            logger()->warning('Protected content preview file is missing from storage.', [
                'storage_path' => $path,
            ]);

            return null;
        }

        return [
            'disk' => 'local',
            'path' => Storage::disk('local')->path($path),
        ];
    }

    private function deleteContentStoragePath($path, ?int $exceptContentId = null)
    {
        if (!$path) {
            return;
        }

        $sharedContentExists = Content::where(function ($query) use ($path) {
                $query->where('file_path', $path)
                    ->orWhere('preview_pdf_path', $path)
                    ->orWhere('student_file_path', $path)
                    ->orWhere('student_preview_pdf_path', $path);
            })
            ->when($exceptContentId, fn ($query) => $query->where('id', '!=', $exceptContentId))
            ->exists();

        if ($sharedContentExists) {
            return;
        }

        foreach (['local', 'public'] as $disk) {
            if (Storage::disk($disk)->exists($path)) {
                Storage::disk($disk)->delete($path);
            }
        }
    }
}
