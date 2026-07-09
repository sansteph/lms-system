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
    public function index()
    {
        $plans = TeachingPlan::with([
                'course',
                'parentTemplate',
                'weeks.items.content',
            ])
            ->where('is_template', false)
            ->when(session('user_role') == 'InstituteAdmin', function ($query) {
                $query->where('institute', session('user_institute'));
            })
            ->latest()
            ->get();

        $templates = TeachingPlan::with([
                'course.courseContents.content',
                'deployedPlans',
                'weeks.items.content',
            ])
            ->where('is_template', true)
            ->when(session('user_role') == 'InstituteAdmin', function ($query) {
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
            ->orderBy('course_title')
            ->get();

        $templateCourses = Course::withCount('courseContents')
            ->where('status', 1)
            ->where('is_template_source', true)
            ->orderBy('course_title')
            ->get();

        $institutes = Institute::where('status', 1)
            ->orderBy('institute_name')
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

        return view('teaching-plans', compact('plans', 'templates', 'courses', 'templateCourses', 'institutes', 'classesByInstitute'));
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

        if (!$course->is_template_source) {
            return redirect()->back()
                ->withErrors(['course_id' => 'Select a Template Source course to create a reusable Teaching Plan Template.']);
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
            ->with('success', "Deployment finished. {$summary['deployed']} deployed, {$summary['skipped']} skipped.")
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

        $week->update([
            'status' => $request->status,
            'released_at' => $request->status == 'released' ? ($week->released_at ?: now()) : $week->released_at,
            'completed_at' => $request->status == 'completed' ? ($week->completed_at ?: now()) : null,
        ]);

        if ($request->status == 'released') {
            $week->items()->update([
                'status' => 'released',
                'released_at' => now(),
                'completed_at' => null,
            ]);
        }

        if ($request->status == 'completed') {
            $week->items()->update([
                'status' => 'completed',
                'completed_at' => now(),
            ]);
        }

        return redirect()->back()
            ->with('success', 'Teaching Plan week updated.');
    }

    public function completeItem($id, TeachingPlanItem $item, TeachingPlanReleaseService $releaseService)
    {
        $plan = TeachingPlan::findOrFail($id);
        $this->authorizePlan($plan);

        if ($item->teaching_plan_id != $plan->id) {
            abort(404);
        }

        $releaseService->markItemCompleted($item);

        return redirect()->back()
            ->with('success', 'Teaching Plan item marked completed.');
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

            $plan->sessions()->update([
                'teaching_plan_id' => null,
                'teaching_plan_week_id' => null,
                'teaching_plan_item_id' => null,
            ]);

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
