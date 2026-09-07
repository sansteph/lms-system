<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SuperAdminOnly
{
    public function handle(Request $request, Closure $next): Response
    {
        if (session('user_role') !== 'Admin') {

            $fallbackRoute = session('user_role') === 'Manager'
                ? 'manager.dashboard'
                : (session('user_role') === 'Principal' ? 'principal.dashboard' : 'admin.dashboard');

            return redirect()->route($fallbackRoute)
                ->with('error', 'Access denied.');

        }

        return $next($request);
    }
}
