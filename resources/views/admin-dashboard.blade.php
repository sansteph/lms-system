@extends('layouts.app')

@section('content')

<div class="container-fluid">
    <div class="row">

        @include('layouts.sidebar')

        <main class="col-md-10 col-lg-10 p-4">

            <div class="page-header mb-4">
                <h2>Admin Dashboard</h2>
                <p>Welcome back! Manage your LMS from one place.</p>
            </div>

            @if(session('user_role') == 'Admin' && is_null(session('password_changed_at')))

                <div class="alert alert-warning shadow-sm border-0 mb-4">

                    <strong>Security Reminder:</strong>
                    You are using a system-generated password.
                    Please change your password for better account security.

                </div>

            @endif

            <div class="row g-4">

                <div class="col-md-3">
                    <div class="dashboard-card">
                        <p>Total Students</p>
                        <h2>{{ $studentCount }}</h2>
                    </div>
                </div>

                <div class="col-md-3">
                    <div class="dashboard-card">
                        <p>Total STEM Engineers</p>
                        <h2>{{ $teacherCount }}</h2>
                    </div>
                </div>

                <div class="col-md-3">
                    <div class="dashboard-card">
                        <p>Total Classes</p>
                        <h2>{{ $classCount }}</h2>
                    </div>
                </div>

            </div>

            <div class="card shadow-sm border-0 mt-4 p-4">
                <h4 class="mb-2">Quick Actions</h4>
                <p class="text-muted">Use these shortcuts to manage important LMS tasks.</p>

                <div class="mt-3 d-flex flex-wrap gap-2">
                    <a href="{{ route('students') }}" class="btn btn-primary">Add Student</a>
                    <a href="{{ route('users') }}" class="btn btn-success">Add STEM Engineer</a>
                    <a href="{{ route('content') }}" class="btn btn-warning">Upload Content</a>
                </div>
            </div>
        </main>

    </div>
</div>

@endsection