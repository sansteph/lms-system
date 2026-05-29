<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\IndependentLearner;
use Illuminate\Support\Facades\Hash;
use App\Models\Course;

class IndependentLearnerController extends Controller
{
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
        return view('independent.dashboard');
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
}