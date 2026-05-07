@extends('layouts.app')

@section('content')

<div class="container-fluid">
    <div class="row">

        @include('layouts.teacher-sidebar')

        <div class="col-md-10 col-lg-10 p-4">

            <div class="page-header d-flex justify-content-between align-items-center flex-wrap gap-3 mb-4">

                <div>
                    <h2 class="mb-1">Teacher Dashboard</h2>

                    <p class="text-muted mb-0">
                        Welcome back! Manage classes, content, and assessments.
                    </p>
                </div>

                <button class="btn btn-primary btn-sm">
                    View Schedule
                </button>

            </div>

            <div class="row g-4 mb-4">

                <div class="col-md-3">
                    <div class="dashboard-card">
                        <h6>Assigned Classes</h6>
                        <h2>5</h2>
                    </div>
                </div>

                <div class="col-md-3">
                    <div class="dashboard-card">
                        <h6>Total Students</h6>
                        <h2>180</h2>
                    </div>
                </div>

                <div class="col-md-3">
                    <div class="dashboard-card">
                        <h6>Pending Assessments</h6>
                        <h2>3</h2>
                    </div>
                </div>

                <div class="col-md-3">
                    <div class="dashboard-card">
                        <h6>Completed Content</h6>
                        <h2>24</h2>
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

                                    <tr>
                                        <td>VIII</td>
                                        <td>A</td>
                                        <td>40</td>
                                        <td>
                                            <span class="badge bg-success">
                                                Active
                                            </span>
                                        </td>
                                    </tr>

                                    <tr>
                                        <td>IX</td>
                                        <td>B</td>
                                        <td>38</td>
                                        <td>
                                            <span class="badge bg-success">
                                                Active
                                            </span>
                                        </td>
                                    </tr>

                                    <tr>
                                        <td>X</td>
                                        <td>A</td>
                                        <td>42</td>
                                        <td>
                                            <span class="badge bg-warning text-dark">
                                                Pending
                                            </span>
                                        </td>
                                    </tr>

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

                                <button class="btn btn-primary">
                                    Upload Content
                                </button>

                                <button class="btn btn-success">
                                    Create Assessment
                                </button>

                                <button class="btn btn-warning">
                                    Enter Marks
                                </button>

                                <button class="btn report-btn">
                                    View Reports
                                </button>

                            </div>

                        </div>
                    </div>

                </div>

            </div>

        </div>

    </div>
</div>

@endsection