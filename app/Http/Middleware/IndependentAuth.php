<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class IndependentAuth
{
    public function handle(Request $request, Closure $next)
    {
        if (!session('independent_learner_id')) {
            return redirect()
                ->route('independent.login')
                ->with('error', 'Please login to continue.');
        }

        return $next($request);
    }
}