<?php

namespace App\Services;

use App\Models\Content;
use App\Models\Course;
use App\Models\CourseEnrollment;
use App\Models\IndependentLearner;
use App\Models\LessonProgress;
use App\Models\Student;
use App\Models\TeachingPlanItem;
use App\Models\User;
use Illuminate\Support\Collection;

class MobileContentAccess
{
    public function availableIds($account): Collection
    {
        if ($account instanceof IndependentLearner) {
            return Content::where('status', 1)->where('is_released', true)
                ->whereIn('course_id', CourseEnrollment::where('learner_id', $account->id)->select('course_id'))->pluck('id');
        }
        if ($account instanceof User && $account->role === 'Admin') {
            return Content::where('status', 1)->pluck('id');
        }
        if ($account instanceof User && $account->role === 'InstituteAdmin') {
            return Content::where('status', 1)->where('institute', $account->institute)->pluck('id');
        }
        if (! ($account instanceof Student) && ! ($account instanceof User && in_array($account->role, ['Teacher', 'STEM Engineer'], true))) {
            return collect();
        }
        $student = $account instanceof Student;
        $class = $student ? preg_replace('/\s+/', ' ', trim($account->class.' '.$account->section)) : null;
        $ids = TeachingPlanItem::whereIn('status', $student ? ['completed'] : ['released', 'completed'])
            ->whereHas('content', fn ($q) => $q->where('status', 1))
            ->whereHas('plan', function ($q) use ($account, $class, $student) {
                $q->where('is_template', false)->where('institute', $account->institute)->whereIn('status', ['active', 'completed']);
                if ($student) {
                    $q->where(function ($q) use ($class, $account) {
                        $q->whereRaw("REPLACE(TRIM(class), '  ', ' ') = ?", [$class])
                            ->orWhere(function ($q) use ($account) {
                                $q->whereRaw("TRIM(class) = ?", [trim($account->class)])
                                    ->whereRaw("TRIM(COALESCE(section, '')) = ?", [trim((string) $account->section)]);
                            });
                    });
                }
            })->pluck('content_id');
        if ($student) {
            $legacy = Course::where('institute', $account->institute)
                ->whereRaw("REPLACE(TRIM(assigned_class), '  ', ' ') = ?", [$class])->select('id');
            $ids = $ids->merge(Content::whereIn('course_id', $legacy)->where('status', 1)->where('is_released', true)->pluck('id'));
        }
        return $ids->filter()->map(fn ($id) => (int) $id)->unique()->values();
    }

    public function unlockedIds($account): Collection
    {
        $ids = $this->availableIds($account);
        if (!($account instanceof Student)) return $ids;
        $completed = LessonProgress::where('student_id', $account->id)->where('is_completed', true)
            ->pluck('content_id')->map(fn ($id) => (int) $id);
        $lessons = Content::whereIn('id', $ids)->orderBy('lesson_order')->orderBy('id')->get(['id', 'course_id', 'lesson_order']);
        return $lessons->groupBy('course_id')->flatMap(function ($course) use ($completed) {
            $previous = null;
            return $course->filter(function ($lesson) use (&$previous, $completed) {
                $allowed = $previous === null || $completed->contains((int) $previous->id);
                $previous = $lesson;
                return $allowed;
            })->pluck('id');
        })->values();
    }

    public function authorize($account, Content $content): void
    {
        abort_if(isset($account->status) && ! (bool) $account->status, 403, 'This account is inactive.');
        abort_unless($this->unlockedIds($account)->contains($content->id), 403, 'This lesson is unavailable or requires completion of the previous lesson.');
    }

    public function pdfPath(Content $content, string $audience): ?string
    {
        $paths = $audience === 'teacher'
            ? [$content->preview_pdf_path, $content->file_path]
            : [$content->student_preview_pdf_path, $content->student_file_path, $content->preview_pdf_path, $content->file_path];
        foreach ($paths as $path) {
            if ($path && strtolower(pathinfo($path, PATHINFO_EXTENSION)) === 'pdf') {
                return $path;
            }
        }
        return null;
    }
}
