<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'admin.auth' => \App\Http\Middleware\AdminAuth::class,
            'teacher.auth' => \App\Http\Middleware\TeacherAuth::class,
            'student.auth' => \App\Http\Middleware\StudentAuth::class,
            'track.activity' => \App\Http\Middleware\TrackUserActivity::class,
            'super.admin' => \App\Http\Middleware\SuperAdminOnly::class,
            'independent.auth' => \App\Http\Middleware\IndependentAuth::class,

        ]);

        $middleware->validateCsrfTokens(except: [
            'ai-chat/ask',
        ]);

    })


    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();

    
