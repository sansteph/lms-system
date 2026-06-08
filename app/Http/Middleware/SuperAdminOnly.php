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

            return redirect()->route('admin.dashboard')
                ->with('error', 'Access denied.');

        }

        return $next($request);
    }
}