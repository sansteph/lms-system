<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Content;
use App\Models\SchoolClass;
use Illuminate\Support\Facades\DB;
use App\Support\DeletesAssessments;
use Illuminate\Support\Facades\Storage;
use App\Models\Student;
use App\Models\LessonProgress;
use App\Models\UserSession;
use App\Models\Certificate;
use App\Models\StudentAchievement;
use App\Models\AssessmentResult;
use App\Models\Assessment;
use App\Models\ClassTimetable;
use App\Models\ClassContentSession;

class ClassController extends Controller
{
    use DeletesAssessments;
    public function index(Request $request)
    {
        $search = $request->search;


        $classes = SchoolClass::when(
                session('user_role') == 'InstituteAdmin',
                function ($query) {
                    $query->where('institute', session('user_institute'));
                }
            )
            ->when($search, function ($query, $search) {
                $query->where(function ($q) use ($search) {

                    $q->where('class_name', 'like', "%{$search}%")
                    ->orWhere('section', 'like', "%{$search}%")
                    ->orWhere('academic_year', 'like', "%{$search}%");

                });
            })
            ->orderBy('class_name')
            ->get();

        return view(
            'classes',
            compact(
                'classes'
            )
        );
    }


    public function store(Request $request)
    {
        $request->validate([
            'class_name' => 'required|string|max:50',
            'section' => 'required|string|max:20',
            'academic_year' => 'required|string|max:20',
            'status' => 'required|boolean',
            'content_id' => 'nullable|exists:contents,id',
            'institute' => session('user_role') == 'Admin'
                ? 'required|string|max:255'
                : 'nullable|string|max:255',
        ]);

        SchoolClass::create([
            'institute' => session('user_role') == 'InstituteAdmin'
                ? session('user_institute')
                : $request->institute,

            'class_name' => $request->class_name,
            'section' => $request->section,
            'class_teacher' => null,
            'academic_year' => $request->academic_year,
            'content_id' => $request->content_id,
            'status' => $request->status,
        ]);

        return redirect()->back()->with('success', 'Class added successfully');
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'class_name' => 'required|string|max:50',
            'section' => 'required|string|max:20',
            'academic_year' => 'required|string|max:20',
            'content_id' => 'nullable|exists:contents,id',
            'status' => 'required|boolean',
            'institute' => session('user_role') == 'Admin'
                ? 'required|string|max:255'
                : 'nullable|string|max:255',
        ]);

        $class = SchoolClass::findOrFail($id);

        if (
            session('user_role') == 'InstituteAdmin' &&
            $class->institute != session('user_institute')
        ) {
            abort(403, 'Unauthorized action.');
        }

        $class->update([
            'institute' => session('user_role') == 'InstituteAdmin'
                ? session('user_institute')
                : $request->institute,

            'class_name' => $request->class_name,
            'section' => $request->section,
            'class_teacher' => null,
            'academic_year' => $request->academic_year,
            'content_id' => $request->content_id,
            'status' => $request->status,
        ]);

        return redirect()->back()->with('success', 'Class updated successfully');
    }

    public function delete($id)
    {
        $class = SchoolClass::findOrFail($id);

        if (
            session('user_role') == 'InstituteAdmin' &&
            $class->institute != session('user_institute')
        ) {
            abort(403, 'Unauthorized action.');
        }

        DB::transaction(function () use ($class) {

            $students = Student::where('class', $class->class_name)
                ->where('section', $class->section)
                ->where('institute', $class->institute)
                ->get();

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

            $timetables = ClassTimetable::where('class_id', $class->id)->get();

            foreach ($timetables as $timetable) {
                ClassContentSession::where('class_id', $class->id)->delete();
                $timetable->delete();
            }

            $assignedClass = trim($class->class_name . ' ' . $class->section);

            $assessments = Assessment::where('assigned_class', $assignedClass)
                ->where('institute', $class->institute)
                ->get();

            foreach ($assessments as $assessment) {
                $this->deleteAssessmentCompletely($assessment);
            }

            $class->delete();
        });

        return redirect()->back()
            ->with('success', 'Class and all related records deleted successfully.');
    }
}
