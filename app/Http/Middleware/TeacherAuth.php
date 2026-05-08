<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class TeacherAuth
{
    public function handle(Request $request, Closure $next): Response
    {
        if (
            !session()->has('user_id') ||
            session('user_role') != 'Teacher'
        ) {
            return redirect()->route('teacher.login');
        }

        return $next($request);
    }
}