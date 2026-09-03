<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Content;
use App\Models\Course;
use App\Models\CourseContent;
use App\Models\SchoolClass;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;
use Illuminate\Validation\ValidationException;

class CourseAuthoringController extends Controller
{
    private function course(Request $request, int $id): Course
    {
        $user = $request->user();
        abort_unless($user instanceof User && in_array($user->role, ['Admin', 'InstituteAdmin'], true), 403);
        $course = Course::findOrFail($id);
        abort_unless($user->role === 'Admin' || (filled($user->institute) && $course->institute === $user->institute), 403);

        return $course;
    }

    public function index(Request $request, int $id)
    {
        $course = $this->course($request, $id);
        $classes = SchoolClass::query()->when($course->institute, fn ($query) => $query->where('institute', $course->institute))->get(['class_name', 'section']);

        return response()->json([
            'course' => $course->only(['id', 'course_title', 'assigned_class', 'institute', 'is_template_source']),
            'classes' => $classes,
            'lessons' => $course->courseContents()->with('content')->orderBy('sort_order')->get()->map(function ($link) {
                $content = $link->content;

                return [
                    'id' => $link->id, 'content_id' => $link->content_id, 'title' => $content?->content_title,
                    'description' => $content?->description, 'content_type' => $content?->content_type,
                    'assigned_class' => $content?->assigned_class, 'section' => $content?->section,
                    'sort_order' => $link->sort_order, 'status' => $link->status,
                    'has_file' => filled($content?->file_path), 'has_student_file' => filled($content?->student_file_path),
                ];
            }),
        ]);
    }

    public function save(Request $request, int $id, ?int $lessonId = null)
    {
        $course = $this->course($request, $id);
        $link = $lessonId ? $course->courseContents()->with('content')->findOrFail($lessonId) : null;
        abort_if($link && ! $link->content, 404, 'The lesson content no longer exists.');
        $request->validate([
            'lessons' => 'required|array|list|min:1|max:20', 'lessons.*.title' => 'required|string|max:255',
            'lessons.*.description' => 'nullable|string|max:2000', 'lessons.*.content_type' => 'required|string|max:100',
            'lessons.*.assigned_class' => 'nullable|string|max:255', 'lessons.*.section' => 'nullable|string|max:50',
            'lessons.*.sort_order' => 'required|integer|min:1', 'lessons.*.status' => 'required|in:active,draft,archived',
            'lessons.*.file' => ($link ? 'nullable' : 'required').'|file|mimes:pdf|max:51200',
            'lessons.*.student_file' => 'nullable|file|mimes:pdf|max:51200',
            'expected_uploads' => 'sometimes|array|list|max:40',
            'expected_uploads.*' => ['required', 'string', 'distinct', 'regex:/^lessons\.[0-9]+\.(file|student_file)$/'],
        ]);
        foreach ($request->input('expected_uploads', []) as $field) {
            if (! $request->hasFile($field)) {
                throw ValidationException::withMessages(['lessons' => 'An upload did not reach the server. Select fewer files per batch and try again.']);
            }
        }
        $rows = array_values($request->input('lessons'));
        if ($link && count($rows) !== 1) {
            throw ValidationException::withMessages(['lessons' => 'Edit one lesson at a time.']);
        }
        $stored = [];
        $old = [];
        try {
            DB::transaction(function () use ($request, $course, $link, $rows, &$stored, &$old) {
                // Serialise order allocation for this course, including bulk uploads.
                Course::whereKey($course->id)->lockForUpdate()->firstOrFail();
                $orders = array_map(fn ($row) => (int) $row['sort_order'], $rows);
                if (count($orders) !== count(array_unique($orders)) || $course->courseContents()->when($link, fn ($q) => $q->where('id', '!=', $link->id))->whereIn('sort_order', $orders)->exists()) {
                    throw ValidationException::withMessages(['lessons' => 'Lesson orders must be unique within the course.']);
                }
                foreach ($rows as $index => $row) {
                    $class = $row['assigned_class'] ?? $course->assigned_class;
                    $section = $row['section'] ?? null;
                    if ($section && ! $class) {
                        throw ValidationException::withMessages(["lessons.{$index}.section" => 'Choose a class before choosing a section.']);
                    }
                    if ($class && ! SchoolClass::query()->when($course->institute, fn ($q) => $q->where('institute', $course->institute))->where('class_name', $class)->when($section, fn ($q) => $q->where('section', $section))->exists()) {
                        throw ValidationException::withMessages(["lessons.{$index}.assigned_class" => 'Choose an existing class and section for this institute.']);
                    }
                    $data = [
                        'course_id' => $course->id, 'institute' => $course->institute, 'content_title' => $row['title'],
                        'description' => $row['description'] ?? null, 'content_type' => $row['content_type'],
                        'lesson_order' => $row['sort_order'], 'assigned_class' => $class, 'section' => $section,
                        'status' => $row['status'] === 'active',
                    ];
                    foreach (['file' => ['file_path', 'preview_pdf_path'], 'student_file' => ['student_file_path', 'student_preview_pdf_path']] as $field => $columns) {
                        if ($file = $request->file("lessons.{$index}.{$field}")) {
                            $path = $file->store('contents', 'local');
                            $stored[] = $path;
                            foreach ($columns as $column) {
                                if ($link?->content?->{$column}) {
                                    $old[] = $link->content->{$column};
                                }
                                $data[$column] = $path;
                            }
                            if ($field === 'file') {
                                $data['original_file_name'] = $file->getClientOriginalName();
                            }
                        }
                    }
                    if ($link) {
                        $link->content->update($data);
                        $link->update(['sort_order' => $row['sort_order'], 'status' => $row['status']]);
                    } else {
                        $content = Content::create($data + ['uploaded_by' => $request->user()->id, 'is_released' => 0]);
                        $course->courseContents()->create(['content_id' => $content->id, 'sort_order' => $row['sort_order'], 'status' => $row['status'], 'created_by' => $request->user()->id]);
                    }
                }
            });
        } catch (\Throwable $error) {
            foreach ($stored as $path) {
                Storage::disk('local')->delete($path);
            }
            throw $error;
        }
        foreach (array_unique($old) as $path) {
            $shared = Content::where('file_path', $path)->orWhere('preview_pdf_path', $path)->orWhere('student_file_path', $path)->orWhere('student_preview_pdf_path', $path)->exists();
            if (! $shared) {
                foreach (['local', 'public'] as $disk) {
                    Storage::disk($disk)->delete($path);
                }
            }
        }

        return response()->json(['message' => $link ? 'Lesson updated.' : count($rows).' lesson(s) uploaded.']);
    }

    public function preview(Request $request, int $id, int $lessonId, string $audience)
    {
        $course = $this->course($request, $id);
        $link = $course->courseContents()->findOrFail($lessonId);
        abort_unless(in_array($audience, ['teacher', 'student'], true), 404);

        return response()->json(['url' => URL::temporarySignedRoute('mobile.authoring-preview', now()->addMinutes(5), ['accountId' => $request->user()->id, 'lessonId' => $link->id, 'audience' => $audience])]);
    }

    public function file(int $accountId, int $lessonId, string $audience)
    {
        $user = User::findOrFail($accountId);
        $link = CourseContent::with(['course', 'content'])->findOrFail($lessonId);
        abort_unless($user->role === 'Admin' || ($user->role === 'InstituteAdmin' && filled($user->institute) && $user->institute === $link->course?->institute), 403);
        abort_unless(in_array($audience, ['teacher', 'student'], true), 404);
        $path = $audience === 'student' ? $link->content?->student_file_path : $link->content?->file_path;
        abort_unless($path, 404);
        foreach (['local', 'public'] as $disk) {
            if (Storage::disk($disk)->exists($path)) {
                return response()->file(Storage::disk($disk)->path($path), ['Content-Type' => 'application/pdf', 'Cache-Control' => 'private, no-store', 'X-Content-Type-Options' => 'nosniff']);
            }
        }
        abort(404);
    }
}
