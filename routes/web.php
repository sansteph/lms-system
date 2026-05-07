<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\PageController;
use App\Http\Controllers\ClassController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\InstituteController;

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

// UI-only pages for now
Route::get('/content', [PageController::class, 'content'])->name('content');
Route::get('/assessments', [PageController::class, 'assessments'])->name('assessments');
Route::get('/reports', [PageController::class, 'reports'])->name('reports');
Route::get('/notifications', [PageController::class, 'notifications'])->name('notifications');


// Teachers module
Route::get('/teacher-dashboard', [PageController::class, 'teacherDashboard'])->name('teacher.dashboard');
Route::get('/teacher/my-classes', [PageController::class, 'teacherClasses'])->name('teacher.classes');
Route::get('/teacher/teacher-contentt', [PageController::class, 'teacherContent'])->name('teacher.content');
Route::get('/teacher/teacher-assessments', [PageController::class, 'teacherAssessments'])->name('teacher.assessments');
Route::get('/teacher/teacher-reports', [PageController::class, 'teacherReports'])->name('teacher.reports');
Route::get('/teacher/teacher-certificates', [PageController::class, 'teacherCertificates'])->name('teacher.certificates');
Route::get('/teacher/teacher-notifications', [PageController::class, 'teacherNotifications'])->name('teacher.notifications');
Route::get('/teacher/teacher-profile', [PageController::class, 'teacherProfile'])->name('teacher.profile');