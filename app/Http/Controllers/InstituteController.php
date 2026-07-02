<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Institute;
use Illuminate\Support\Facades\DB;
use App\Support\DeletesAssessments;
use Illuminate\Support\Facades\Storage;
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
use App\Models\Notification;
use App\Models\InstituteRegistrationRequest;

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
            'institute_name' => 'required|string|max:150',
            'location' => 'required|string|max:100',
            'contact_person' => 'required|string|max:100',
            'email' => 'required|email|unique:institutes,email',
            'phone' => 'required|string|max:20',
            'status' => 'required|boolean',
        ]);

        Institute::create($request->all());

        return redirect()->back()->with('success', 'Institute added successfully');
    }
    public function update(Request $request, $id)
    {
        $request->validate([
            'institute_id' => 'required|string|max:50|unique:institutes,institute_id,' . $id,
            'institute_name' => 'required|string|max:150',
            'location' => 'required|string|max:100',
            'contact_person' => 'required|string|max:100',
            'email' => 'required|email|unique:institutes,email,' . $id,
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

                AssessmentResult::where('student_id', $student->id)->delete();

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

                $content->delete();
            }

            $assessments = Assessment::where('institute', $instituteName)->get();

            foreach ($assessments as $assessment) {
                $this->deleteAssessmentCompletely($assessment);
            }

            $classes = SchoolClass::where('institute', $instituteName)->get();

            foreach ($classes as $class) {

                ClassContentSession::where('class_id', $class->id)->delete();

                ClassTimetable::where('class_id', $class->id)->delete();

                $class->delete();
            }

            User::where('institute', $instituteName)
                ->whereIn('role', ['Teacher', 'InstituteAdmin'])
                ->delete();

            Course::where('institute', $instituteName)->delete();

            Notification::where('institute', $instituteName)->delete();

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

}
