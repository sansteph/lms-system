@extends('layouts.app')

@section('content')

<div class="container-fluid">
    <div class="row">

        @include('layouts.teacher-sidebar')

        <div class="col-md-10 col-lg-10 p-4">

            <div class="page-header d-flex justify-content-between align-items-center flex-wrap gap-3 mb-4">

                <div>
                    <h2 class="mb-1">STEM Engineer Dashboard</h2>

                    <p class="text-muted mb-0">
                        Welcome back, {{ $teacherName }}! Manage your classes, content, and assessments.
                    </p>                
                </div>

            </div>

            <div class="row g-4 mb-4">

                <div class="col-md-3">
                    <div class="dashboard-card">
                        <h6>Assigned Classes</h6>
                        <h2>{{ $assignedClasses }}</h2>
                    </div>
                </div>

                <div class="col-md-3">
                    <div class="dashboard-card">
                        <h6>Assigned Students</h6>
                        <h2>{{ $totalStudents }}</h2>
                    </div>
                </div>

                <div class="col-md-3">
                    <div class="dashboard-card">
                        <h6>Your Assessments</h6>
                        <h2>{{ $assessmentCount }}</h2>
                        <small>Monthly: {{ $monthlyAssessmentCount }} | Annual: {{ $annualAssessmentCount }}</small>
                    </div>
                </div>

                <div class="col-md-3">
                    <div class="dashboard-card">
                        <h6>Assigned Content</h6>
                        <h2>{{ $contentCount }}</h2>
                    </div>
                </div>

            </div>

            <div class="row g-4">

                <div class="col-lg-7">

                    <div class="card shadow border-0">
                        <div class="card-body">

                            <h5 class="mb-3">My Classes</h5>

                            <table class="table table-hover align-middle">

                                <thead class="table-light">
                                    <tr>
                                        <th>Class</th>
                                        <th>Section</th>
                                        <th>Students</th>
                                        <th>Status</th>
                                    </tr>
                                </thead>

                                <tbody>

                                    @forelse($classes as $class)

                                    <tr>

                                        <td>{{ $class->class_name }}</td>

                                        <td>{{ $class->section }}</td>

                                        <td>-</td>

                                        <td>

                                            @if($class->status)

                                                <span class="badge bg-success">
                                                    Active
                                                </span>

                                            @else

                                                <span class="badge bg-danger">
                                                    Inactive
                                                </span>

                                            @endif

                                        </td>

                                    </tr>

                                    @empty

                                    <tr>

                                        <td colspan="4" class="text-center">
                                            No classes assigned.
                                        </td>

                                    </tr>

                                    @endforelse

                                </tbody>

                            </table>

                        </div>
                    </div>

                </div>

                <div class="col-lg-5">

                    <div class="card shadow border-0">
                        <div class="card-body">

                            <h5 class="mb-3">Quick Actions</h5>

                            <div class="d-grid gap-2">

                                <a href="{{ route('teacher.content') }}"
                                class="btn btn-primary">
                                    Access Content
                                </a>

                                <a href="{{ route('teacher.assessments') }}"
                                    class="btn btn-primary">
                                    Create Assessment
                                </a>

                                <a href="{{ route('assessment.review') }}"
                                    class="btn btn-primary">
                                    Evaluate Assessments
                                </a>

                                <a href="{{ route('teacher.reports') }}"
                                class="btn btn-primary">
                                    View Reports
                                </a>

                            </div>

                        </div>
                    </div>

                </div>

            </div>

        </div>

    </div>
</div>

@endsection

