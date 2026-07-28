<?php

namespace App\Http\Controllers;

use App\Models\AiContentSummary;
use App\Models\Content;
use App\Models\Course;
use App\Models\Student;
use App\Models\TeachingPlanItem;
use App\Models\User;
use App\Services\Ai\GeminiAiService;
use Illuminate\Http\Request;
use Throwable;

class AiChatController extends Controller
{
    public function ask(Request $request, GeminiAiService $geminiAiService)
    {
        $validated = $request->validate([
            'message' => ['required', 'string', 'max:1200'],
        ]);

        $viewer = $this->viewerContext();
        $contextItems = $this->contextItemsForViewer($viewer);

        try {
            $answer = $geminiAiService->answerChatQuestion(
                $validated['message'],
                $contextItems,
                $viewer['label']
            );
        } catch (Throwable $exception) {
            report($exception);

            return response()->json([
                'message' => 'AI assistant is temporarily unavailable. Please try again shortly.',
            ], 503);
        }

        return response()->json($answer);
    }

    private function viewerContext(): array
    {
        if (session('student_id')) {
            $student = Student::find(session('student_id'));

            return [
                'type' => 'student',
                'label' => 'Student',
                'model' => $student,
                'institute' => $student?->institute,
            ];
        }

        if (session('user_id') && session('user_role') == 'Teacher') {
            $teacher = User::find(session('user_id'));

            return [
                'type' => 'teacher',
                'label' => 'STEM Engineer',
                'model' => $teacher,
                'institute' => $teacher?->institute,
            ];
        }

        if (session('user_id') && in_array(session('user_role'), ['Admin', 'InstituteAdmin'], true)) {
            return [
                'type' => session('user_role') == 'Admin' ? 'admin' : 'institute_admin',
                'label' => session('user_role') == 'Admin' ? 'Admin' : 'Institute Admin',
                'model' => User::find(session('user_id')),
                'institute' => session('user_role') == 'InstituteAdmin' ? session('user_institute') : null,
            ];
        }

        return [
            'type' => 'guest',
            'label' => 'Website Visitor',
            'model' => null,
            'institute' => null,
        ];
    }

    private function contextItemsForViewer(array $viewer): array
    {
        $contentIds = match ($viewer['type']) {
            'student' => $this->studentContentIds($viewer['model']),
            'teacher' => $this->teacherContentIds($viewer['model']),
            'institute_admin' => $this->instituteContentIds($viewer['institute']),
            'admin' => Content::where('status', 1)->pluck('id'),
            default => collect(),
        };

        if ($contentIds->isEmpty()) {
            return [];
        }

        return AiContentSummary::with('content')
            ->whereIn('content_id', $contentIds->unique()->values())
            ->where('status', 'generated')
            ->latest('generated_at')
            ->take(12)
            ->get()
            ->map(function (AiContentSummary $summary) {
                return [
                    'title' => $summary->content?->content_title ?? 'Lesson',
                    'summary' => mb_substr((string) $summary->summary, 0, 1400),
                    'key_points' => array_slice($summary->key_points ?? [], 0, 6),
                ];
            })
            ->values()
            ->all();
    }

    private function studentContentIds(?Student $student)
    {
        if (!$student) {
            return collect();
        }

        $assignedClass = $this->studentClassName($student);

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
            ->pluck('content_id');

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

    private function teacherContentIds(?User $teacher)
    {
        if (!$teacher) {
            return collect();
        }

        return TeachingPlanItem::whereIn('status', ['released', 'completed'])
            ->whereNotNull('content_id')
            ->whereHas('plan', function ($query) use ($teacher) {
                $query->where('is_template', false)
                    ->where('institute', $teacher->institute)
                    ->whereIn('status', ['active', 'completed']);
            })
            ->whereHas('content', function ($query) {
                $query->where('status', 1);
            })
            ->pluck('content_id')
            ->unique()
            ->values();
    }

    private function instituteContentIds(?string $institute)
    {
        if (blank($institute)) {
            return collect();
        }

        return Content::where('status', 1)
            ->where('institute', $institute)
            ->pluck('id');
    }

    private function studentClassName(Student $student): string
    {
        return preg_replace('/\s+/', ' ', trim($student->class . ' ' . $student->section));
    }
}
