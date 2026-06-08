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
            !in_array(session('user_role'), ['Admin', 'InstituteAdmin'])
        ) 
        {
            return redirect()->route('admin.login');
        }

        return $next($request);
    }
}