<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\PageController;

Route::get('/', [PageController::class, 'home'])->name('home');
Route::get('/admin-login', [PageController::class, 'adminLogin'])->name('admin.login');
Route::get('/teacher-login', [PageController::class, 'teacherLogin'])->name('teacher.login');
Route::get('/student-assessment', [PageController::class, 'studentAssessment'])->name('student.assessment');
Route::get('/admin-dashboard', [PageController::class, 'adminDashboard'])->name('admin.dashboard');
Route::get('/students', [PageController::class, 'students'])->name('students');
Route::get('/classes', [PageController::class, 'classes'])->name('classes');
Route::get('/content', [PageController::class, 'content'])->name('content');
Route::get('/assessments', [PageController::class, 'assessments'])->name('assessments');
Route::get('/users', [PageController::class, 'users'])->name('users');
Route::get('/institutes', [PageController::class, 'institutes'])->name('institutes');
Route::get('/reports', [PageController::class, 'reports'])->name('reports');
Route::get('/notifications', [PageController::class, 'notifications'])->name('notifications');
Route::post('/students/store', [PageController::class, 'storeStudent'])->name('students.store');
Route::post('/students/update/{id}', [PageController::class, 'updateStudent'])->name('students.update');
Route::get('/students/delete/{id}', [PageController::class, 'deleteStudent'])->name('students.delete');