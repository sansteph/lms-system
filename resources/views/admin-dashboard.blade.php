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

            <div class="row g-4">

                <div class="col-md-3">
                    <div class="dashboard-card">
                        <p>Total Students</p>
                        <h2>250</h2>
                    </div>
                </div>

                <div class="col-md-3">
                    <div class="dashboard-card">
                        <p>Total Teachers</p>
                        <h2>25</h2>
                    </div>
                </div>

                <div class="col-md-3">
                    <div class="dashboard-card">
                        <p>Total Classes</p>
                        <h2>10</h2>
                    </div>
                </div>

                <div class="col-md-3">
                    <div class="dashboard-card">
                        <p>Assessments</p>
                        <h2>18</h2>
                    </div>
                </div>

            </div>

            <div class="card shadow-sm border-0 mt-4 p-4">
                <h4 class="mb-2">Quick Actions</h4>
                <p class="text-muted">Use these shortcuts to manage important LMS tasks.</p>

                <div class="mt-3 d-flex flex-wrap gap-2">
                    <a href="{{ route('students') }}" class="btn btn-primary">Add Student</a>
                    <a href="{{ route('users') }}" class="btn btn-success">Add Teacher</a>
                    <a href="{{ route('content') }}" class="btn btn-warning">Upload Content</a>
                    <a href="{{ route('assessments') }}" class="btn btn-secondary text-white">Create Assessment</a>
                </div>
            </div>
        </main>

    </div>
</div>

@endsection