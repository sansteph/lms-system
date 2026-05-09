@extends('layouts.app')

@section('content')

<div class="container-fluid">
    <div class="row">

        @include('layouts.student-sidebar')

        <div class="col-md-10 col-lg-10 p-4">

            <div class="page-header d-flex justify-content-between align-items-center flex-wrap gap-3 mb-4">
                <div>
                    <h2 class="mb-1">Student Dashboard</h2>
                    <p class="text-muted mb-0">
                        View assessments, results, certificates, and updates.
                    </p>
                </div>

                <button class="btn btn-primary btn-sm">
                    Take Assessment
                </button>
            </div>

            <div class="row g-4 mb-4">
                <div class="col-md-3">
                    <div class="dashboard-card">
                        <h6>Total Assessments</h6>
                        <h2>6</h2>
                    </div>
                </div>

                <div class="col-md-3">
                    <div class="dashboard-card">
                        <h6>Completed</h6>
                        <h2>4</h2>
                    </div>
                </div>

                <div class="col-md-3">
                    <div class="dashboard-card">
                        <h6>Pending</h6>
                        <h2>2</h2>
                    </div>
                </div>

                <div class="col-md-3">
                    <div class="dashboard-card">
                        <h6>Badges Earned</h6>
                        <h2>12</h2>
                    </div>
                </div>
            </div>

            <div class="row g-4">

                <div class="col-lg-8">
                    <div class="card shadow border-0">
                        <div class="card-body">

                            <h5 class="mb-3">Upcoming Assessments</h5>

                            <table class="table table-bordered table-hover align-middle">
                                <thead class="table-light">
                                    <tr>
                                        <th>Sl. No</th>
                                        <th>Assessment</th>
                                        <th>Subject</th>
                                        <th>Duration</th>
                                        <th>Status</th>
                                        <th width="160">Action</th>
                                    </tr>
                                </thead>

                                <tbody>
                                    <tr>
                                        <td>1</td>
                                        <td>AI Fundamentals Test</td>
                                        <td>Artificial Intelligence</td>
                                        <td>45 mins</td>
                                        <td><span class="badge bg-warning text-dark">Pending</span></td>
                                        <td>
                                            <button class="btn btn-sm btn-primary">
                                                Start
                                            </button>
                                        </td>
                                    </tr>

                                    <tr>
                                        <td>2</td>
                                        <td>Robotics Basics Quiz</td>
                                        <td>Robotics</td>
                                        <td>30 mins</td>
                                        <td><span class="badge bg-success">Completed</span></td>
                                        <td>
                                            <button class="btn btn-sm btn-primary">
                                                View Result
                                            </button>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>

                        </div>
                    </div>
                </div>

                <div class="col-lg-4">
                    <div class="card shadow border-0 mb-4">
                        <div class="card-body">
                            <h5 class="mb-3">Quick Actions</h5>

                            <div class="d-grid gap-2">
                                <button class="btn btn-primary">Take Assessment</button>
                                <button class="btn btn-success">View Results</button>
                                <a href="{{ route('student.badges') }}" class="btn btn-warning">
                                    View Achievements
                                </a>
                            </div>
                        </div>
                    </div>

                    <div class="card shadow border-0">
                        <div class="card-body">
                            <h5 class="mb-3">Latest Updates</h5>

                            <div class="border-bottom pb-2 mb-2">
                                <strong>Assessment Reminder</strong>
                                <p class="text-muted mb-0 small">
                                    Complete your pending AI assessment.
                                </p>
                            </div>

                            <div>
                                <strong>Certificate Available</strong>
                                <p class="text-muted mb-0 small">
                                    Robotics quiz certificate is ready to download.
                                </p>
                            </div>

                        </div>
                    </div>
                </div>

            </div>

        </div>

    </div>
</div>

@endsection