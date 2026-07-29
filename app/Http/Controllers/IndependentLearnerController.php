<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\IndependentLearner;
use Illuminate\Support\Facades\Hash;
use App\Models\Course;
use App\Models\CourseEnrollment;
use App\Models\Certificate;

class IndependentLearnerController extends Controller
{
    public function adminIndex(Request $request)
    {
        $search = $request->search;

        $learners = IndependentLearner::withCount([
            'enrollments',
            'certificates'
        ])
        ->when($search, function ($query, $search) {

            $query->where('name', 'like', "%{$search}%")
                ->orWhere('email', 'like', "%{$search}%");

        })
        ->latest()
        ->paginate(30)
        ->withQueryString();

        return view(
            'independent-learners',
            compact('learners')
        );
    }

    public function toggleStatus($id)
    {
        $learner = IndependentLearner::findOrFail($id);

        $learner->update([
            'status' => !$learner->status,
        ]);

        return redirect()->back()
            ->with('success', 'Learner status updated successfully.');
    }

    public function showLearner($id)
    {
        $learner = IndependentLearner::with([
            'enrollments.course',
            'certificates.course'
        ])->findOrFail($id);

        return view('independent-learner-details', compact('learner'));
    }
    public function register()
    {
        return view('independent.register');
    }

    public function registerSubmit(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:independent_learners,email',
            'phone' => 'nullable|string|max:20',
            'password' => 'required|min:6|confirmed',
        ]);

        IndependentLearner::create([
            'name' => $request->name,
            'email' => $request->email,
            'phone' => $request->phone,
            'password' => Hash::make($request->password),
            'status' => true,
        ]);

        return redirect()
            ->route('independent.login')
            ->with('success', 'Registration successful. Please login.');
    }

    public function login()
    {
        return view('independent.login');
    }

    public function loginSubmit(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required',
        ]);

        $learner = IndependentLearner::where('email', $request->email)
            ->where('status', true)
            ->first();

        if ($learner && Hash::check($request->password, $learner->password)) {

            session([
                'independent_learner_id' => $learner->id,
                'independent_learner_name' => $learner->name,
            ]);

            return redirect()->route('independent.dashboard');
        }

        return redirect()->back()
            ->with('error', 'Invalid login credentials.');
    }

    public function dashboard()
    {
        $learnerId = session('independent_learner_id');

        $enrollments = CourseEnrollment::with('course')
            ->where('learner_id', $learnerId)
            ->latest()
            ->get();

        return view('independent.dashboard', compact('enrollments'));
    }

    public function courses()
    {
        $courses = Course::where('is_active', 1)
            ->whereIn('availability_type', ['Independent', 'Both'])
            ->latest()
            ->get();

        return view('independent.courses', compact('courses'));
    }

    public function courseDetails($id)
    {
        $course = Course::where('is_active', 1)
            ->whereIn('availability_type', ['Independent', 'Both'])
            ->findOrFail($id);

        return view('independent.course-details', compact('course'));
    }

    public function enroll($id)
    {
        $learnerId = session('independent_learner_id');

        if (!$learnerId) {
            return redirect()->route('independent.login')
                ->with('error', 'Please login to enroll in this course.');
        }

        $course = Course::where('is_active', 1)
            ->whereIn('availability_type', ['Independent', 'Both'])
            ->findOrFail($id);

        $alreadyEnrolled = CourseEnrollment::where('learner_id', $learnerId)
            ->where('course_id', $course->id)
            ->exists();

        if ($alreadyEnrolled) {
            return redirect()->route('independent.dashboard')
                ->with('success', 'You are already enrolled in this course.');
        }

        CourseEnrollment::create([
            'learner_id' => $learnerId,
            'course_id' => $course->id,
            'payment_status' => 'Pending',
            'enrolled_at' => now(),
        ]);

        return redirect()->route('independent.dashboard')
            ->with('success', 'Course enrolled successfully. Payment integration will be added next.');
    }

    public function myEnrollments()
    {
        $enrollments = CourseEnrollment::with('course')
            ->where('learner_id', session('independent_learner_id'))
            ->latest()
            ->get();

        return view('independent.my-enrollments', compact('enrollments'));
    }

    public function learnCourse($id)
    {
        $learnerId = session('independent_learner_id');

        $enrollment = CourseEnrollment::where('learner_id', $learnerId)
            ->where('course_id', $id)
            ->first();

        if (!$enrollment) {
            return redirect()
                ->route('independent.courses.show', $id)
                ->with('error', 'Please enroll before accessing this course.');
        }

        $course = Course::findOrFail($id);

        $contents = \App\Models\Content::where('course_id', $id)
            ->where('status', 1)
            ->where('is_released', true)
            ->where(function ($query) {
                $query->whereNotNull('student_file_path')
                    ->orWhereNotNull('file_path');
            })
            ->orderBy('lesson_order')
            ->get();

        $totalLessons = $contents->count();

        $completedLessons = \App\Models\LessonProgress::where('independent_learner_id', $learnerId)
            ->whereIn('content_id', $contents->pluck('id'))
            ->where('is_completed', true)
            ->count();

        $progressPercentage = $totalLessons > 0
            ? round(($completedLessons / $totalLessons) * 100)
            : 0;
        if ($totalLessons > 0 && $completedLessons == $totalLessons) {

            $enrollment->update([
                'is_completed' => true,
                'completed_at' => now(),
            ]);

            $existingCertificate = Certificate::where('certificate_type', 'Independent')
                ->where('independent_learner_id', $learnerId)
                ->where('course_id', $course->id)
                ->first();

            if (!$existingCertificate) {

                Certificate::create([
                    'certificate_type' => 'Independent',
                    'student_id' => null,
                    'course_id' => $course->id,
                    'independent_learner_id' => $learnerId,
                    'certificate_code' => 'IND-' . strtoupper(uniqid()),
                    'badge_count' => 0,
                    'issued_date' => now(),
                    'status' => 'Issued',
                ]);

            }

        }

        return view('independent.learn-course', compact(
            'course',
            'contents',
            'enrollment',
            'totalLessons',
            'completedLessons',
            'progressPercentage'
        ));
    }

    public function markLessonComplete($contentId)
    {
        $learnerId = session('independent_learner_id');

        if (!$learnerId) {
            return redirect()->route('independent.login');
        }

        \App\Models\LessonProgress::updateOrCreate(
            [
                'independent_learner_id' => $learnerId,
                'content_id' => $contentId,
            ],
            [
                'is_completed' => true,
                'completed_at' => now(),
            ]
        );

        return redirect()->back()
            ->with('success', 'Lesson marked as completed.');
    }

    public function certificates()
    {
        $certificates = Certificate::with('course')
            ->where('independent_learner_id', session('independent_learner_id'))
            ->where('certificate_type', 'Independent')
            ->latest()
            ->get();

        return view('independent.certificates', compact('certificates'));
    }
}
