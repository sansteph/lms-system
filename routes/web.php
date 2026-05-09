<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\PageController;
use App\Http\Controllers\ClassController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\InstituteController;
use App\Http\Controllers\ContentController;
use App\Http\Controllers\AssessmentController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\ReportController;

// Public pages
Route::get('/', [PageController::class, 'home'])->name('home');

Route::get('/admin-login', [PageController::class, 'adminLogin'])->name('admin.login');
Route::post('/admin-login', [UserController::class, 'adminLogin'])->name('admin.login.submit');

Route::get('/teacher-login', [PageController::class, 'teacherLogin'])->name('teacher.login');
Route::post('/teacher-login', [UserController::class, 'teacherLogin'])->name('teacher.login.submit');

Route::get('/student-assessment', [PageController::class, 'studentAssessment'])->name('student.assessment.public');

Route::get('/logout', [UserController::class, 'logout'])->name('logout');


// Admin protected routes
Route::middleware(['admin.auth'])->group(function () {

    Route::get('/admin-dashboard', [PageController::class, 'adminDashboard'])->name('admin.dashboard');

    Route::get('/students', [PageController::class, 'students'])->name('students');
    Route::post('/students/store', [PageController::class, 'storeStudent'])->name('students.store');
    Route::post('/students/update/{id}', [PageController::class, 'updateStudent'])->name('students.update');
    Route::get('/students/delete/{id}', [PageController::class, 'deleteStudent'])->name('students.delete');

    Route::get('/classes', [ClassController::class, 'index'])->name('classes');
    Route::post('/classes/store', [ClassController::class, 'store'])->name('classes.store');
    Route::post('/classes/update/{id}', [ClassController::class, 'update'])->name('classes.update');
    Route::get('/classes/delete/{id}', [ClassController::class, 'delete'])->name('classes.delete');

    Route::get('/users', [UserController::class, 'index'])->name('users');
    Route::post('/users/store', [UserController::class, 'store'])->name('users.store');
    Route::post('/users/update/{id}', [UserController::class, 'update'])->name('users.update');
    Route::get('/users/delete/{id}', [UserController::class, 'delete'])->name('users.delete');

    Route::get('/institutes', [InstituteController::class, 'index'])->name('institutes');
    Route::post('/institutes/store', [InstituteController::class, 'store'])->name('institutes.store');
    Route::post('/institutes/update/{id}', [InstituteController::class, 'update'])->name('institutes.update');
    Route::get('/institutes/delete/{id}', [InstituteController::class, 'delete'])->name('institutes.delete');

    Route::get('/content', [ContentController::class, 'index'])->name('content');
    Route::post('/content/store', [ContentController::class, 'store'])->name('content.store');
    Route::post('/content/update/{id}', [ContentController::class, 'update'])->name('content.update');
    Route::get('/content/delete/{id}', [ContentController::class, 'delete'])->name('content.delete');

    Route::get('/assessments', [AssessmentController::class, 'index'])->name('assessments');
    Route::post('/assessments/store', [AssessmentController::class, 'store'])->name('assessments.store');
    Route::post('/assessments/update/{id}', [AssessmentController::class, 'update'])->name('assessments.update');
    Route::get('/assessments/delete/{id}', [AssessmentController::class, 'delete'])->name('assessments.delete');

    Route::get('/notifications', [NotificationController::class, 'index'])->name('notifications');
    Route::post('/notifications/store', [NotificationController::class, 'store'])->name('notifications.store');
    Route::post('/notifications/update/{id}', [NotificationController::class, 'update'])->name('notifications.update');
    Route::get('/notifications/delete/{id}', [NotificationController::class, 'delete'])->name('notifications.delete');

    Route::get('/reports', [ReportController::class, 'index'])->name('reports');
});


// Teacher protected routes
Route::middleware(['teacher.auth'])->group(function () {

    Route::get('/teacher-dashboard', [PageController::class, 'teacherDashboard'])->name('teacher.dashboard');

    Route::get('/teacher/my-classes', [PageController::class, 'teacherClasses'])->name('teacher.classes');

    Route::get('/teacher/content', [PageController::class, 'teacherContent'])->name('teacher.content');

    Route::get('/teacher/assessments', [PageController::class, 'teacherAssessments'])->name('teacher.assessments');

    Route::get('/teacher/reports', [PageController::class, 'teacherReports'])->name('teacher.reports');

    Route::get('/teacher/certificates', [PageController::class, 'teacherCertificates'])->name('teacher.certificates');

    Route::get('/teacher/notifications', [PageController::class, 'teacherNotifications'])->name('teacher.notifications');

    Route::get('/teacher/profile', [PageController::class, 'teacherProfile'])->name('teacher.profile');
});


// Student protected routes
Route::get('/student-dashboard', [PageController::class, 'studentDashboard'])->name('student.dashboard');
Route::get('/student/take-assessment', [PageController::class, 'studentTakeAssessment'])->name('student.assessment');
Route::get('/student/history', [PageController::class, 'studentHistory'])->name('student.history');
Route::get('/student/badges', [PageController::class, 'studentBadges'])->name('student.badges');
Route::get('/student/notifications', [PageController::class, 'studentNotifications'])->name('student.notifications');
Route::get('/student/profile', [PageController::class, 'studentProfile'])->name('student.profile');