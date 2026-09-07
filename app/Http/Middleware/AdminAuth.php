<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AdminAuth
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (
            !session()->has('user_id') ||
            !in_array(session('user_role'), ['Admin', 'InstituteAdmin', 'Manager'])
        ) 
        {
            return redirect()->route('admin.login');
        }

        if (session('user_role') === 'Manager') {
            $allowedManagerRoutes = [
                'admin.change.password',
                'admin.change.password.submit',
                'admin.approvals',
                'admin.question-papers*',
                'admin.certificates*',
                'admin.my-space*',
                'admin.teacher-achievements*',
                'admin.achievements*',
                'admin.assessment.review*',
                'admin.class-session.report*',
                'reports.*',
                'notifications*',
            ];

            if (!$request->routeIs(...$allowedManagerRoutes)) {
                return redirect()->route('manager.dashboard')
                    ->with('error', 'Access denied.');
            }
        }

        return $next($request);
    }
}
