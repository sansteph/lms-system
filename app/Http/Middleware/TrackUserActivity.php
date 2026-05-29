<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use App\Models\UserActivityLog;
use Symfony\Component\HttpFoundation\Response;

class TrackUserActivity
{
    public function handle(Request $request, Closure $next): Response
    {
        if (
            session('tracking_session_id') &&
            $request->isMethod('get')
        ) {
            $userType = session('user_role') ?? 'Student';

            if ($userType !== 'Admin') {

                $lastLog = UserActivityLog::where('user_session_id', session('tracking_session_id'))
                    ->whereNull('ended_at')
                    ->latest()
                    ->first();

                if ($lastLog) {
                    $endedAt = now();

                    $lastLog->update([
                        'ended_at' => $endedAt,
                        'duration_seconds' => max(0, \Carbon\Carbon::parse($lastLog->started_at)->diffInSeconds($endedAt)),
                    ]);
                }

                UserActivityLog::create([
                    'user_session_id' => session('tracking_session_id'),
                    'user_type' => $userType,
                    'user_id' => session('user_id') ?? session('student_id'),
                    'section_name' => $this->getSectionName($request->route()?->getName()),
                    'route_name' => $request->route()?->getName() ?? 'unknown',
                    'page_url' => $request->path(),
                    'started_at' => now(),
                    'ended_at' => null,
                    'duration_seconds' => 0,
                ]);
            }
        }

        return $next($request);
    }

    private function getSectionName($routeName)
    {
        return match ($routeName) {
            'teacher.dashboard', 'student.dashboard' => 'Dashboard',
            'teacher.content' => 'Content',
            'teacher.assessments', 'student.assessment' => 'Assessment',
            'teacher.results', 'student.history' => 'Results / History',
            'teacher.reports' => 'Reports',
            'teacher.notifications', 'student.notifications' => 'Notifications',
            'teacher.profile', 'student.student-profile' => 'Profile',
            'student.badges' => 'Achievements',
            'student.certificate' => 'Certificate',
            default => $routeName ?? 'Unknown Section',
        };
    }
}