<?php

namespace App\Http\Controllers;

use App\Models\Course;
use App\Models\CourseContent;
use App\Models\Institute;
use App\Models\SchoolClass;
use App\Models\TeachingPlan;
use App\Models\TeachingPlanItem;
use App\Models\TeachingPlanWeek;
use App\Services\TeachingPlanBuilderService;
use App\Services\TeachingPlanReleaseService;
use App\Services\TeachingPlanTemplateDeploymentService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class TeachingPlanController extends Controller
{
    public function index(Request $request)
    {
        $institutes = Institute::where('status', 1)
            ->orderBy('institute_name')
            ->get();

        $teachingPlanSectionPager = null;
        $currentInstituteName = session('user_role') == 'InstituteAdmin'
            ? session('user_institute')
            : null;

        if (session('user_role') == 'Admin') {
            $sections = collect([[
                    'label' => 'Teaching Plan Templates',
                    'type' => 'templates',
                    'value' => '__templates',
                ]])
                ->merge($institutes->map(fn ($institute) => [
                    'label' => $institute->institute_name,
                    'type' => 'institute',
                    'value' => $institute->institute_name,
                ]))
                ->values();

            $currentPage = max(1, min((int) $request->query('page', 1), max($sections->count(), 1)));
            $currentSection = $sections->get($currentPage - 1, $sections->first());

            if (($currentSection['type'] ?? null) == 'institute') {
                $currentInstituteName = $currentSection['value'];
            }

            $teachingPlanSectionPager = [
                'current_page' => $currentPage,
                'last_page' => $sections->count(),
                'current_type' => $currentSection['type'] ?? 'templates',
                'current_label' => $currentSection['label'] ?? 'Teaching Plans',
                'previous_url' => $currentPage > 1 ? route('teaching-plans', ['page' => $currentPage - 1]) : null,
                'next_url' => $currentPage < $sections->count() ? route('teaching-plans', ['page' => $currentPage + 1]) : null,
                'previous_label' => $currentPage > 1 ? ($sections->get($currentPage - 2)['label'] ?? 'Previous') : null,
                'next_label' => $currentPage < $sections->count() ? ($sections->get($currentPage)['label'] ?? 'Next') : null,
            ];
        }

        $plans = collect();

        if (session('user_role') == 'InstituteAdmin' || $currentInstituteName) {
            $plans = TeachingPlan::with([
                    'course.courseContents.content',
                    'parentTemplate',
                    'weeks.items.content',
                ])
                ->where('is_template', false)
                ->where('institute', $currentInstituteName)
                ->latest()
                ->get();
        }

        $templates = TeachingPlan::with([
                'course.courseContents.content',
                'weeks.items.content',
            ])
            ->withCount('deployedPlans')
            ->where('is_template', true)
            ->when(session('user_role') == 'InstituteAdmin' || (session('user_role') == 'Admin' && $currentInstituteName), function ($query) {
                $query->whereRaw('1 = 0');
            })
            ->latest()
            ->get();

        $courses = Course::withCount('courseContents')
            ->where('status', 1)
            ->where(function ($query) {
                $query->where('is_template_source', false)
                    ->orWhereNull('is_template_source');
            })
            ->when(session('user_role') == 'InstituteAdmin', function ($query) {
                $query->where('institute', session('user_institute'));
            })
            ->when(session('user_role') == 'Admin', function ($query) use ($currentInstituteName) {
                if ($currentInstituteName) {
                    $query->where('institute', $currentInstituteName);
                } else {
                    $query->whereRaw('1 = 0');
                }
            })
            ->orderBy('course_title')
            ->get();

        $templateCourses = Course::withCount([
                'courseContents as active_course_contents_count' => function ($query) {
                    $query->where('status', 'active');
                },
            ])
            ->withCount('courseContents')
            ->where('status', 1)
            ->where('is_template_source', true)
            ->whereHas('courseContents', function ($query) {
                $query->where('status', 'active');
            })
            ->orderBy('course_title')
            ->get();

        $classesByInstitute = SchoolClass::where('status', 1)
            ->orderBy('class_name')
            ->orderBy('section')
            ->get()
            ->groupBy('institute')
            ->map(function ($classes) {
                return $classes
                    ->map(fn ($class) => trim($class->class_name . ' ' . ($class->section ?? '')))
                    ->filter()
                    ->unique()
                    ->values();
            });

        return view('teaching-plans', compact(
            'plans',
            'templates',
            'courses',
            'templateCourses',
            'institutes',
            'classesByInstitute',
            'teachingPlanSectionPager',
            'currentInstituteName'
        ));
    }

    public function store(Request $request)
    {
        $request->validate([
            'course_id' => 'required|exists:courses,id',
            'class' => 'required|string|max:255',
            'section' => 'nullable|string|max:50',
            'start_date' => 'required|date',
            'release_day' => 'required|string|max:20',
            'contents_per_week' => 'required|integer|min:1|max:10',
            'release_policy' => 'required|in:release_next_only_if_previous_completed',
            'status' => 'required|in:active,inactive',
            'remarks' => 'nullable|string|max:2000',
        ]);

        $course = Course::with(['courseContents.content'])
            ->findOrFail($request->course_id);
        $this->authorizeCourse($course);

        if (!$course->courseContents()->where('status', 'active')->exists()) {
            return redirect()->back()
                ->withErrors(['course_id' => 'Add active course content before generating a Teaching Plan.']);
        }

        DB::transaction(function () use ($request, $course) {
            app(TeachingPlanBuilderService::class)->buildForCourse($course, [
                'class' => $request->class,
                'section' => $request->section,
                'start_date' => $request->start_date,
                'release_day' => $request->release_day,
                'contents_per_week' => $request->contents_per_week,
                'release_policy' => $request->release_policy,
                'status' => $request->status,
                'remarks' => $request->remarks,
            ], session('user_id'));
        });

        return redirect()->back()
            ->with('success', 'Weekly Teaching Plan generated successfully.');
    }

    public function storeTemplate(Request $request)
    {
        $this->authorizeSuperAdmin();

        $request->validate([
            'course_id' => 'required|exists:courses,id',
            'title' => 'required|string|max:255',
            'class' => 'required|string|max:255',
            'section' => 'nullable|string|max:50',
            'release_day' => 'required|string|max:20',
            'contents_per_week' => 'required|integer|min:1|max:10',
            'release_policy' => 'required|in:release_next_only_if_previous_completed',
            'status' => 'required|in:active,inactive',
            'remarks' => 'nullable|string|max:2000',
        ]);

        $course = Course::with(['courseContents.content'])
            ->findOrFail($request->course_id);

        if ($course->status != 1 || !$course->is_template_source) {
            return redirect()->back()
                ->withErrors(['course_id' => 'Select an active Template Source course to create a reusable Teaching Plan Template.']);
        }

        if (!$course->courseContents()->where('status', 'active')->exists()) {
            return redirect()->back()
                ->withErrors(['course_id' => 'Add active course content before creating a Teaching Plan Template.']);
        }

        DB::transaction(function () use ($request, $course) {
            app(TeachingPlanBuilderService::class)->buildForCourse($course, [
                'is_template' => true,
                'title' => $request->title,
                'class' => $request->class,
                'section' => $request->section,
                'release_day' => $request->release_day,
                'contents_per_week' => $request->contents_per_week,
                'release_policy' => $request->release_policy,
                'status' => $request->status,
                'remarks' => $request->remarks,
            ], session('user_id'));
        });

        return redirect()->back()
            ->with('success', 'Teaching Plan Template created successfully.');
    }

    public function deployTemplates(Request $request, TeachingPlanTemplateDeploymentService $deploymentService)
    {
        $this->authorizeSuperAdmin();

        $request->validate([
            'selected_institute_ids' => 'required|array|min:1',
            'selected_institute_ids.*' => 'exists:institutes,id',
            'classes_by_institute' => 'nullable|array',
            'custom_classes_by_institute' => 'nullable|array',
        ]);

        $classesByInstitute = [];

        foreach ($request->selected_institute_ids as $instituteId) {
            $selectedClasses = collect($request->input("classes_by_institute.{$instituteId}", []));
            $customClasses = collect(explode(',', (string) $request->input("custom_classes_by_institute.{$instituteId}", '')));

            $classesByInstitute[(string) $instituteId] = $selectedClasses
                ->merge($customClasses)
                ->map(fn ($class) => trim((string) $class))
                ->filter()
                ->unique()
                ->values()
                ->all();
        }

        $summary = $deploymentService->deployTemplatesToInstituteClasses(
            $request->selected_institute_ids,
            $classesByInstitute,
            session('user_id')
        );

        return redirect()->back()
            ->with('success', "Deployment finished. {$summary['deployed']} deployed, {$summary['synced']} synced, {$summary['skipped']} skipped.")
            ->with('deployment_messages', $summary['messages']);
    }

    public function update(Request $request, $id)
    {
        $plan = TeachingPlan::with('course')->findOrFail($id);
        $this->authorizePlan($plan);

        $request->validate([
            'status' => 'required|in:active,inactive,completed',
            'remarks' => 'nullable|string|max:2000',
        ]);

        $plan->update([
            'status' => $request->status,
            'remarks' => $request->remarks,
        ]);

        return redirect()->back()
            ->with('success', 'Teaching Plan updated successfully.');
    }

    public function releaseNext($id, TeachingPlanReleaseService $releaseService)
    {
        $plan = TeachingPlan::findOrFail($id);
        $this->authorizePlan($plan);

        $week = $releaseService->releaseNextWeek($plan, 'manual_release');

        return redirect()->back()
            ->with($week ? 'success' : 'error', $week ? 'Next week released.' : 'No locked week is available to release.');
    }

    public function storeLaggedContent(Request $request, $id)
    {
        $plan = TeachingPlan::with(['course.courseContents.content', 'weeks'])->findOrFail($id);
        $this->authorizePlan($plan);

        if ($plan->is_template) {
            abort(403, 'Lagged content can only be added to live institute Teaching Plans.');
        }

        $request->validate([
            'course_content_ids' => 'required|array|min:1',
            'course_content_ids.*' => 'integer|exists:course_contents,id',
        ]);

        $courseContents = CourseContent::with('content')
            ->where('course_id', $plan->course_id)
            ->where('status', 'active')
            ->whereIn('id', $request->course_content_ids)
            ->get();

        if ($courseContents->isEmpty()) {
            return redirect()->back()
                ->with('error', 'Select active content from this Teaching Plan course.');
        }

        $createdCount = 0;
        $laggedWeek = null;

        DB::transaction(function () use ($plan, $courseContents, &$createdCount, &$laggedWeek) {
            $laggedWeek = $plan->weeks()
                ->where('status', 'released')
                ->where('release_reason', 'lagged_content')
                ->orderByDesc('week_number')
                ->first();

            if (!$laggedWeek) {
                $nextWeekNumber = ((int) $plan->weeks()->max('week_number')) + 1;

                $laggedWeek = TeachingPlanWeek::create([
                    'teaching_plan_id' => $plan->id,
                    'week_number' => $nextWeekNumber,
                    'week_start_date' => now()->toDateString(),
                    'week_end_date' => now()->toDateString(),
                    'release_date' => now()->toDateString(),
                    'status' => 'released',
                    'released_at' => now(),
                    'release_reason' => 'lagged_content',
                ]);
            }

            $nextSortOrder = ((int) $laggedWeek->items()->max('sort_order')) + 1;

            foreach ($courseContents as $courseContent) {
                $alreadyPending = TeachingPlanItem::where('teaching_plan_id', $plan->id)
                    ->where('course_content_id', $courseContent->id)
                    ->where('status', 'released')
                    ->whereHas('week', function ($query) {
                        $query->where('release_reason', 'lagged_content');
                    })
                    ->exists();

                if ($alreadyPending) {
                    continue;
                }

                TeachingPlanItem::create([
                    'teaching_plan_id' => $plan->id,
                    'teaching_plan_week_id' => $laggedWeek->id,
                    'course_id' => $plan->course_id,
                    'course_content_id' => $courseContent->id,
                    'content_id' => $courseContent->content_id,
                    'sort_order' => $nextSortOrder++,
                    'status' => 'released',
                    'released_at' => now(),
                ]);

                $createdCount++;
            }
        });

        if ($createdCount === 0) {
            return redirect()->back()
                ->with('error', 'Selected content is already pending as lagged content for this plan.');
        }

        app(\App\Services\LmsNotificationService::class)
            ->notifyTeachersOfReleasedWeek($laggedWeek->fresh(['plan.course', 'items.content']));

        return redirect()->back()
            ->with('success', "{$createdCount} lagged content item(s) added to Pending Sessions.");
    }

    public function updateWeek(Request $request, $id, TeachingPlanWeek $week)
    {
        $plan = TeachingPlan::findOrFail($id);
        $this->authorizePlan($plan);

        if ($week->teaching_plan_id != $plan->id) {
            abort(404);
        }

        $request->validate([
            'status' => 'required|in:locked,released,completed,skipped',
        ]);

        if (
            $request->status == 'completed' &&
            $week->items()->where('status', '!=', 'completed')->exists()
        ) {
            return redirect()->back()
                ->with('error', 'A week can only be marked completed after its topics are completed through STEM Engineer sessions.');
        }

        $week->update([
            'status' => $request->status,
            'released_at' => $request->status == 'released' ? ($week->released_at ?: now()) : $week->released_at,
            'completed_at' => $request->status == 'completed' ? ($week->completed_at ?: now()) : null,
        ]);

        if (in_array($request->status, ['locked', 'released', 'skipped'], true)) {
            $week->items()->update([
                'status' => $request->status,
                'released_at' => $request->status == 'released' ? now() : null,
                'completed_at' => null,
            ]);
        }

        return redirect()->back()
            ->with('success', 'Teaching Plan week updated.');
    }

    public function runReleaseCheck(TeachingPlanReleaseService $releaseService)
    {
        if (app()->environment('production')) {
            abort(403, 'Manual release check is disabled in production.');
        }

        $released = $releaseService->runFridayRelease(now());

        return redirect()->back()
            ->with('success', "Manual release check completed. Released {$released} week(s).");
    }

    public function delete($id)
    {
        $plan = TeachingPlan::findOrFail($id);
        $this->authorizePlan($plan);

        DB::transaction(function () use ($plan) {
            if ($plan->is_template) {
                TeachingPlan::where('parent_template_id', $plan->id)
                    ->update(['parent_template_id' => null]);
            }

            $plan->sessions()->delete();

            TeachingPlanItem::where('teaching_plan_id', $plan->id)->delete();
            TeachingPlanWeek::where('teaching_plan_id', $plan->id)->delete();
            $plan->delete();
        });

        return redirect()->back()
            ->with('success', 'Teaching Plan deleted. Session history was preserved.');
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

        abort(403, 'You are not authorized to manage this Teaching Plan.');
    }

    private function authorizeSuperAdmin(): void
    {
        if (session('user_role') !== 'Admin') {
            abort(403, 'Only Super Admin can manage Teaching Plan Templates.');
        }
    }

    private function authorizePlan(TeachingPlan $plan): void
    {
        if (session('user_role') == 'Admin') {
            return;
        }

        if (
            session('user_role') == 'InstituteAdmin' &&
            $plan->institute == session('user_institute')
        ) {
            return;
        }

        abort(403, 'You are not authorized to manage this Teaching Plan.');
    }
}
