<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Institute;
use Illuminate\Support\Facades\DB;
use App\Support\DeletesAssessments;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Hash;
use App\Models\User;
use App\Models\Student;
use App\Models\SchoolClass;
use App\Models\Course;
use App\Models\Content;
use App\Models\Assessment;
use App\Models\AssessmentResult;
use App\Models\Certificate;
use App\Models\StudentAchievement;
use App\Models\LessonProgress;
use App\Models\UserSession;
use App\Models\ClassTimetable;
use App\Models\ClassContentSession;
use App\Models\InstituteRegistrationRequest;
use App\Models\CourseContent;
use App\Models\TeachingPlan;
use App\Models\TeachingPlanItem;
use App\Models\TeachingPlanWeek;

class InstituteController extends Controller
{
    use DeletesAssessments;
    public function index(Request $request)
    {
        $search = $request->search;

        $institutes = Institute::when($search, function ($query, $search) {
            return $query->where('institute_id', 'like', "%{$search}%")
                        ->orWhere('institute_name', 'like', "%{$search}%")
                        ->orWhere('location', 'like', "%{$search}%")
                        ->orWhere('contact_person', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%");
        })->get();

        $studentCount = Student::count();

        return view('institutes', compact('institutes', 'studentCount'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'institute_id' => 'required|string|max:50|unique:institutes,institute_id',
            'institute_name' => 'required|string|max:150|unique:institutes,institute_name',
            'location' => 'required|string|max:100',
            'contact_person' => 'required|string|max:100',
            'email' => 'required|email',
            'phone' => 'required|string|max:20',
            'status' => 'required|boolean',
            'admin_name' => 'nullable|string|max:100',
            'admin_email' => 'required_with:admin_password|nullable|email|max:255',
            'admin_password' => 'nullable|string|min:6',
        ]);

        DB::transaction(function () use ($request) {
            Institute::create($request->only([
                'institute_id',
                'institute_name',
                'location',
                'contact_person',
                'email',
                'phone',
                'status',
            ]));

            if ($request->filled('admin_password')) {
                do {
                    $adminUserId = 'ADM' . rand(100000, 999999);
                } while (User::where('user_id', $adminUserId)->exists());

                User::create([
                    'user_id' => $adminUserId,
                    'name' => $request->admin_name ?: $request->contact_person,
                    'email' => $request->admin_email,
                    'phone' => $request->phone,
                    'institute' => $request->institute_name,
                    'role' => 'InstituteAdmin',
                    'password' => Hash::make($request->admin_password),
                    'status' => $request->status,
                    'password_changed_at' => now(),
                ]);
            }
        });

        return redirect()->back()->with('success', 'Institute added successfully');
    }
    public function update(Request $request, $id)
    {
        $request->validate([
            'institute_id' => 'required|string|max:50|unique:institutes,institute_id,' . $id,
            'institute_name' => 'required|string|max:150|unique:institutes,institute_name,' . $id,
            'location' => 'required|string|max:100',
            'contact_person' => 'required|string|max:100',
            'email' => 'required|email',
            'phone' => 'required|string|max:20',
            'status' => 'required|boolean',
        ]);

        $institute = Institute::findOrFail($id);

        $institute->update($request->all());

        return redirect()->back()->with('success', 'Institute updated successfully');
    }
    public function delete($id)
    {
        $institute = Institute::findOrFail($id);

        DB::transaction(function () use ($institute) {

            $instituteName = $institute->institute_name;

            $students = Student::where('institute', $instituteName)->get();

            foreach ($students as $student) {

                $this->deleteAssessmentResultsForStudent($student->id);

                LessonProgress::where('student_id', $student->id)->delete();

                UserSession::where('user_type', 'Student')
                    ->where('user_id', $student->id)
                    ->delete();

                Certificate::where('student_id', $student->id)->delete();

                $achievements = StudentAchievement::where('student_id', $student->id)->get();

                foreach ($achievements as $achievement) {

                    if (
                        $achievement->certificate_file &&
                        Storage::disk('public')->exists($achievement->certificate_file)
                    ) {
                        Storage::disk('public')->delete($achievement->certificate_file);
                    }

                    $achievement->delete();
                }

                $student->delete();
            }

            $contents = Content::where('institute', $instituteName)->get();

            foreach ($contents as $content) {

                $this->deleteContentStoragePath($content->file_path);

                $this->deleteContentStoragePath($content->preview_pdf_path);

                $this->deleteContentStoragePath($content->student_file_path);

                $this->deleteContentStoragePath($content->student_preview_pdf_path);

                LessonProgress::where('content_id', $content->id)->delete();

                ClassContentSession::where('content_id', $content->id)->delete();

                ClassTimetable::where('content_id', $content->id)->delete();

                $this->deleteTeachingPlansByContent($content->id);

                CourseContent::where('content_id', $content->id)->delete();

                $content->delete();
            }

            $assessments = Assessment::where('institute', $instituteName)->get();

            foreach ($assessments as $assessment) {
                $this->deleteAssessmentCompletely($assessment);
            }

            $classes = SchoolClass::where('institute', $instituteName)->get();

            foreach ($classes as $class) {

                $this->deleteClassSessionsForSchoolClass($class);

                ClassTimetable::where('class_id', $class->id)->delete();

                $class->delete();
            }

            User::where('institute', $instituteName)
                ->whereIn('role', ['Teacher', 'InstituteAdmin'])
                ->delete();

            $courses = Course::where('institute', $instituteName)->get();

            foreach ($courses as $course) {
                $this->deleteTeachingPlansByCourse($course->id);
                CourseContent::where('course_id', $course->id)->delete();
                $course->delete();
            }

            InstituteRegistrationRequest::where('institute_name', $instituteName)->delete();

            $institute->delete();
        });

        return redirect()->back()
            ->with('success', 'Institute and all related records deleted successfully.');
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

    private function deleteTeachingPlansByCourse(int $courseId): void
    {
        TeachingPlan::where('course_id', $courseId)
            ->get()
            ->each(function (TeachingPlan $plan) {
                $this->deleteTeachingPlanGraph($plan);
            });
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

    private function deleteTeachingPlanItemsForContent(int $contentId): void
    {
        $items = TeachingPlanItem::where('content_id', $contentId)->get();

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

    private function deleteClassSessionsForSchoolClass(SchoolClass $class): void
    {
        ClassContentSession::where(function ($query) use ($class) {
                $query->where('class_id', $class->id)
                    ->orWhere(function ($nested) use ($class) {
                        $nested->where('institute', $class->institute)
                            ->where('class', $class->class_name)
                            ->where('section', $class->section);
                    });
            })
            ->delete();
    }

}
