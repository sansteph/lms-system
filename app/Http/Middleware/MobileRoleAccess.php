<?php

namespace App\Http\Middleware;

use App\Models\IndependentLearner;
use App\Models\Student;
use App\Models\User;
use Closure;
use Illuminate\Http\Request;

class MobileRoleAccess
{
    public function handle(Request $request, Closure $next)
    {
        $account = $request->user();
        abort_unless($account, 401, 'Please sign in again.');
        abort_if(isset($account->status) && ! (bool) $account->status, 403, 'This account is inactive.');

        if ($request->is('api/engineer/*')) {
            abort_unless($account instanceof User && in_array($account->role, ['Teacher', 'STEM Engineer'], true), 403);
        } elseif ($request->is('api/student/*')) {
            abort_unless($account instanceof Student, 403);
        } elseif ($request->is('api/hybrid/*')) {
            abort_unless($account instanceof IndependentLearner, 403);
        } elseif ($request->is('api/admin/*')) {
            abort_unless($account instanceof User && in_array($account->role, ['Admin', 'InstituteAdmin'], true), 403);
            if ($request->is('api/admin/independent-learners*')) {
                abort_unless($account->role === 'Admin', 403);
            }
        }

        return $next($request);
    }
}
