@extends('layouts.app')

@section('content')

<div class="container-fluid">
    <div class="row">

        @include('layouts.sidebar')

        <!-- Main Content -->
        <div class="col-md-9 col-lg-10 p-4">
            <h2 class="mb-4">Admin Dashboard</h2>

            <div class="row g-4">

                <div class="col-md-3">
                    <div class="card shadow border-0 p-3">
                        <h6>Total Students</h6>
                        <h2>250</h2>
                    </div>
                </div>

                <div class="col-md-3">
                    <div class="card shadow border-0 p-3">
                        <h6>Total Teachers</h6>
                        <h2>25</h2>
                    </div>
                </div>

                <div class="col-md-3">
                    <div class="card shadow border-0 p-3">
                        <h6>Total Classes</h6>
                        <h2>10</h2>
                    </div>
                </div>

                <div class="col-md-3">
                    <div class="card shadow border-0 p-3">
                        <h6>Assessments</h6>
                        <h2>18</h2>
                    </div>
                </div>

            </div>

            <div class="card shadow border-0 mt-4 p-4">
                <h4>Quick Actions</h4>

                <div class="mt-3">
                    <a href="{{ route('students') }}" class="btn btn-primary me-2">Add Student</a>
                    <a href="{{ route('users') }}" class="btn btn-success me-2">Add Teacher</a>
                    <a href="{{ route('content') }}" class="btn btn-warning me-2">Upload Content</a>
                    <a href="{{ route('assessments') }}" class="btn btn-info">Create Assessment</a>
                </div>
            </div>
        </div>
    </div>
</div>

@endsection