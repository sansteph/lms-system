<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;

// API Version 1 Group
Route::prefix('v1')->group(function () {

    // Public Routes
    Route::post('/auth/login', [AuthController::class, 'login']);

    // Protected Routes (Require Bearer Token)
    Route::middleware('auth:sanctum')->group(function () {
        Route::get('/auth/profile', [AuthController::class, 'profile']);
        Route::post('/auth/logout', [AuthController::class, 'logout']);

        // Future student, teacher, and community routes go here...
    });
});