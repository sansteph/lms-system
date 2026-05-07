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
Route::get('/teacher-login', [PageController::class, 'teacherLogin'])->name('teacher.login');
Route::get('/student-assessment', [PageController::class, 'studentAssessment'])->name('student.assessment');

// Dashboard
Route::get('/admin-dashboard', [PageController::class, 'adminDashboard'])->name('admin.dashboard');

// Student Management
Route::get('/students', [PageController::class, 'students'])->name('students');
Route::post('/students/store', [PageController::class, 'storeStudent'])->name('students.store');
Route::post('/students/update/{id}', [PageController::class, 'updateStudent'])->name('students.update');
Route::get('/students/delete/{id}', [PageController::class, 'deleteStudent'])->name('students.delete');

// Class Management
Route::get('/classes', [ClassController::class, 'index'])->name('classes');
Route::post('/classes/store', [ClassController::class, 'store'])->name('classes.store');
Route::post('/classes/update/{id}', [ClassController::class, 'update'])->name('classes.update');
Route::get('/classes/delete/{id}', [ClassController::class, 'delete'])->name('classes.delete');

// User Management
Route::get('/users', [UserController::class, 'index'])->name('users');
Route::post('/users/store', [UserController::class, 'store'])->name('users.store');
Route::post('/users/update/{id}', [UserController::class, 'update'])->name('users.update');
Route::get('/users/delete/{id}', [UserController::class, 'delete'])->name('users.delete');

// Institute Management
Route::get('/institutes', [InstituteController::class, 'index'])->name('institutes');
Route::post('/institutes/store', [InstituteController::class, 'store'])->name('institutes.store');
Route::post('/institutes/update/{id}', [InstituteController::class, 'update'])->name('institutes.update');
Route::get('/institutes/delete/{id}', [InstituteController::class, 'delete'])->name('institutes.delete');

// Content Management
Route::get('/content', [ContentController::class, 'index'])->name('content');
Route::post('/content/store', [ContentController::class, 'store'])->name('content.store');
Route::post('/content/update/{id}', [ContentController::class, 'update'])->name('content.update');
Route::get('/content/delete/{id}', [ContentController::class, 'delete'])->name('content.delete');

// Assessment Management
Route::get('/assessments', [AssessmentController::class, 'index'])->name('assessments');
Route::post('/assessments/store', [AssessmentController::class, 'store'])->name('assessments.store');
Route::post('/assessments/update/{id}', [AssessmentController::class, 'update'])->name('assessments.update');
Route::get('/assessments/delete/{id}', [AssessmentController::class, 'delete'])->name('assessments.delete');

// Notification Management
Route::get('/notifications', [NotificationController::class, 'index'])->name('notifications');
Route::post('/notifications/store', [NotificationController::class, 'store'])->name('notifications.store');
Route::get('/notifications/delete/{id}', [NotificationController::class, 'delete'])->name('notifications.delete');
Route::post('/notifications/update/{id}', [NotificationController::class, 'update'])->name('notifications.update');

// Reports
Route::get('/reports', [ReportController::class, 'index'])->name('reports');



// Teachers module
Route::get('/teacher-dashboard', [PageController::class, 'teacherDashboard'])->name('teacher.dashboard');
Route::get('/teacher/my-classes', [PageController::class, 'teacherClasses'])->name('teacher.classes');
Route::get('/teacher/teacher-contentt', [PageController::class, 'teacherContent'])->name('teacher.content');
Route::get('/teacher/teacher-assessments', [PageController::class, 'teacherAssessments'])->name('teacher.assessments');
Route::get('/teacher/teacher-reports', [PageController::class, 'teacherReports'])->name('teacher.reports');
Route::get('/teacher/teacher-certificates', [PageController::class, 'teacherCertificates'])->name('teacher.certificates');
Route::get('/teacher/teacher-notifications', [PageController::class, 'teacherNotifications'])->name('teacher.notifications');
Route::get('/teacher/teacher-profile', [PageController::class, 'teacherProfile'])->name('teacher.profile');