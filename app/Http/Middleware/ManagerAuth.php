<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ManagerAuth
{
    public function handle(Request $request, Closure $next): Response
    {
        if (!session()->has('user_id') || session('user_role') !== 'Manager') {
            return redirect()->route('admin.login');
        }

        return $next($request);
    }
}
