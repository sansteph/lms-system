<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\{User, Student, UserSession, UserActivityLog};
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class MobileActivityController extends Controller
{
    public function update(Request $request)
    {
        $actor = $request->user();
        $type = $actor instanceof Student ? 'Student' : ($actor instanceof User && in_array($actor->role, ['Teacher','STEM Engineer'], true) ? 'Teacher' : null);
        abort_unless($type, 403);
        $data = $request->validate(['action' => 'required|in:start,heartbeat,stop', 'id' => 'nullable|integer', 'section' => 'nullable|string|max:100']);
        $key = 'mobile-activity:'.hash('sha256', $request->bearerToken() ?: "$type:$actor->id");
        if ($data['action'] === 'start') {
            $previous = UserActivityLog::where('user_type', $type)->where('user_id', $actor->id)->find(Cache::get($key.':active'));
            if ($previous && !$previous->ended_at) $previous->update(['ended_at' => $previous->updated_at]);
            $sessionId = Cache::get($key);
            $session = UserSession::where('user_type', $type)->where('user_id', $actor->id)->find($sessionId);
            if (!$session) {
                $session = UserSession::create(['user_type' => $type, 'user_id' => $actor->id, 'login_time' => now(), 'total_duration_seconds' => 0, 'ip_address' => $request->ip(), 'browser' => 'Flutter mobile']);
                Cache::put($key, $session->id, now()->addDay());
            }
            $section = $data['section'] ?? 'Dashboard';
            $session->update(['logout_time' => null]);
            $route = match ($section) {
                'Learning Content Preview' => 'content.preview',
                'Learning Content' => $type === 'Student' ? 'student.content' : 'teacher.content',
                default => 'mobile',
            };
            $log = UserActivityLog::create(['user_session_id' => $session->id, 'user_type' => $type, 'user_id' => $actor->id,
                'section_name' => $section, 'route_name' => $route, 'page_url' => 'mobile', 'started_at' => now(), 'ended_at' => null, 'duration_seconds' => 0]);
            Cache::put($key.':active', $log->id, now()->addDay());
            return response()->json(['id' => $log->id]);
        }
        return DB::transaction(function () use ($type, $actor, $key, $data) {
        $log = UserActivityLog::where('user_type', $type)->where('user_id', $actor->id)->where('user_session_id', Cache::get($key))->lockForUpdate()->findOrFail($data['id'] ?? 0);
        if ((int) Cache::get($key.':active') !== (int) $log->id) {
            return response()->json(['success' => true, 'active' => false]);
        }
        // Heartbeats bound elapsed time; a killed or disconnected app cannot accrue hours of activity.
        $elapsed = max(0, min(45, (int) Carbon::parse($log->updated_at)->diffInSeconds(now())));
        $log->update(['ended_at' => $data['action'] === 'stop' ? now() : null, 'duration_seconds' => (int) $log->duration_seconds + $elapsed]);
        $session = UserSession::findOrFail($log->user_session_id);
        $session->increment('total_duration_seconds', $elapsed);
        $session->update(['logout_time' => $data['action'] === 'stop' ? now() : null]);
        if ($data['action'] === 'stop') Cache::forget($key.':active');
        return response()->json(['success' => true]);
        });
    }
}
