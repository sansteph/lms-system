<?php

namespace App\Http\Controllers;

use App\Models\ClassTimetable;
use App\Models\ClassContentSession;
use App\Models\Content;
use App\Models\AssessmentResult;
use  \App\Models\Assessment;

class CoordinatorController extends Controller
{
    public function dashboard()
    {
        $today = now()->format('Y-m-d');

        $todayClasses = ClassTimetable::where('session_date', $today)->count();

        $liveSessions = ClassContentSession::where('status', 'Started')->count();

        $completedToday = ClassTimetable::where('session_date', $today)
            ->where('status', 'Completed')
            ->count();

        $pendingToday = ClassTimetable::where('session_date', $today)
            ->where('status', 'Scheduled')
            ->count();

        $releasedTopics = Content::where('is_released', 1)->count();

        $pendingReviews = AssessmentResult::where('status', 'Pending Review')->count();

        return view('coordinator.dashboard', compact(
            'todayClasses',
            'liveSessions',
            'completedToday',
            'pendingToday',
            'releasedTopics',
            'pendingReviews'
        ));
    }

    public function liveSessions()
    {
        $liveSessions = ClassContentSession::with([
            'schoolClass',
            'content',
            'stemEngineer'
        ])
        ->where('status', 'Started')
        ->latest()
        ->get();

        return view(
            'coordinator.live-sessions',
            compact('liveSessions')
        );
    }

    public function dailyReport()
    {
        $today = now()->format('Y-m-d');

        $dailyReports = \App\Models\User::where('role', 'Teacher')
            ->where('status', 1)
            ->orderBy('name')
            ->get()
            ->map(function ($teacher) use ($today) {

                $scheduled = ClassTimetable::where('session_date', $today)
                    ->whereHas('schoolClass', function ($q) use ($teacher) {
                        $q->where('institute', $teacher->institute);
                    })
                    ->count();

                $completed = ClassContentSession::where('stem_engineer_id', $teacher->id)
                    ->whereDate('started_at', $today)
                    ->where('status', 'Completed')
                    ->count();

                $live = ClassContentSession::where('stem_engineer_id', $teacher->id)
                    ->whereDate('started_at', $today)
                    ->where('status', 'Started')
                    ->count();

                $pending = max($scheduled - $completed - $live, 0);

                $durationSeconds = ClassContentSession::where('stem_engineer_id', $teacher->id)
                    ->whereDate('started_at', $today)
                    ->sum('duration_seconds');

                return [
                    'teacher' => $teacher,
                    'scheduled' => $scheduled,
                    'completed' => $completed,
                    'live' => $live,
                    'pending' => $pending,
                    'duration_hours' => round($durationSeconds / 3600, 1),
                ];
            });

        return view('coordinator.daily-report', compact('dailyReports'));
    }

    public function contentTracker()
    {
        $contents = Content::with('course')
            ->orderBy('lesson_order')
            ->latest()
            ->get();

        return view(
            'coordinator.content-tracker',
            compact('contents')
        );
    }

    public function assessmentMonitoring()
    {
        $assessments = Assessment::with([
                'questions',
                'results'
            ])
            ->latest()
            ->get();

        return view(
            'coordinator.assessment-monitoring',
            compact('assessments')
        );
    }
}