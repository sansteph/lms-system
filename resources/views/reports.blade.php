@extends('layouts.app')

@section('content')

<div class="container-fluid">
    <div class="row">

        @include('layouts.sidebar')

        <div class="col-md-10 col-lg-10 p-4">

            <div class="page-header d-flex justify-content-between align-items-center flex-wrap gap-3 mb-4">
                <div>
                    <h2 class="mb-1">Reports</h2>
                    <p class="text-muted mb-0">
                        Generate, view, and download LMS performance reports.
                    </p>
                </div>

            </div>

            <div class="row g-4 mb-4">
                <div class="col-md-3">
                    <div class="dashboard-card">
                        <h6>Students</h6>
                        <h2>{{ $studentCount }}</h2>
                    </div>
                </div>

                <div class="col-md-3">
                    <div class="dashboard-card">
                        <h6>STEM Engineers</h6>
                        <h2>{{ $teacherCount }}</h2>
                    </div>
                </div>

                <div class="col-md-3">
                    <div class="dashboard-card">
                        <h6>Classes</h6>
                        <h2>{{ $classCount }}</h2>
                    </div>
                </div>

                <div class="col-md-3">
                    <div class="dashboard-card">
                        <h6>Assessments</h6>
                        <h2>{{ $assessmentCount }}</h2>
                    </div>
                </div>
            </div>

            <div class="row g-4 mb-4">

                <div class="col-md-3">
                    <div class="dashboard-card">
                        <h6>Today's Sessions</h6>
                        <h2>{{ $todayClassCount }}</h2>
                    </div>
                </div>

                <div class="col-md-3">
                    <div class="dashboard-card">
                        <h6>Completed Today</h6>
                        <h2>{{ $todayCompletedSessions }}</h2>
                    </div>
                </div>

                <div class="col-md-3">
                    <div class="dashboard-card">
                        <h6>Active Sessions</h6>
                        <h2>{{ $activeSessions }}</h2>
                    </div>
                </div>

                <div class="col-md-3">
                    <div class="dashboard-card">
                        <h6>Teaching Hours</h6>
                        <h2>{{ $totalTeachingHours }}</h2>
                    </div>
                </div>

            </div>

            <div class="row g-4 mb-4">

                <div class="col-md-3">
                    <div class="dashboard-card">
                        <h6>Active Plans</h6>
                        <h2>{{ $approvedTeachingPlans }}</h2>
                    </div>
                </div>

                <div class="col-md-3">
                    <div class="dashboard-card">
                        <h6>Inactive Plans</h6>
                        <h2>{{ $pendingTeachingPlans }}</h2>
                    </div>
                </div>

                <div class="col-md-3">
                    <div class="dashboard-card">
                        <h6>Released Lessons</h6>
                        <h2>{{ $contentReleasedCount }}</h2>
                    </div>
                </div>

                <div class="col-md-3">
                    <div class="dashboard-card">
                        <h6>Certificates Issued</h6>
                        <h2>{{ $certificateCount }}</h2>
                    </div>
                </div>

            </div>

            <div class="row g-4 mb-4">

                <div class="col-md-3">
                    <div class="dashboard-card">
                        <h6>Released Weeks</h6>
                        <h2>{{ $releasedTeachingWeeks }}</h2>
                    </div>
                </div>

                <div class="col-md-3">
                    <div class="dashboard-card">
                        <h6>Locked Weeks</h6>
                        <h2>{{ $lockedTeachingWeeks }}</h2>
                    </div>
                </div>

                <div class="col-md-3">
                    <div class="dashboard-card">
                        <h6>Completed Weeks</h6>
                        <h2>{{ $completedTeachingWeeks }}</h2>
                    </div>
                </div>

                <div class="col-md-3">
                    <div class="dashboard-card">
                        <h6>Pending Plan Items</h6>
                        <h2>{{ $pendingTeachingItems }}</h2>
                    </div>
                </div>

            </div>

            <div class="card shadow border-0">

                <div class="card-body">

                    <h5 class="mb-4">
                        Daily Operations Summary
                    </h5>

                    <table class="table table-bordered table-hover align-middle">

                        <thead class="table-light">

                            <tr>
                                <th>Metric</th>
                                <th>Value</th>
                                <th>Status</th>
                            </tr>

                        </thead>

                        <tbody>

                            <tr>
                                <td>Classes Scheduled Today</td>
                                <td>{{ $todayClassCount }}</td>
                                <td>
                                    <span class="badge bg-primary">
                                        Scheduled
                                    </span>
                                </td>
                            </tr>

                            <tr>
                                <td>Sessions Completed Today</td>
                                <td>{{ $todayCompletedSessions }}</td>
                                <td>
                                    <span class="badge bg-success">
                                        Completed
                                    </span>
                                </td>
                            </tr>

                            <tr>
                                <td>Active Live Sessions</td>
                                <td>{{ $activeSessions }}</td>
                                <td>
                                    <span class="badge bg-warning text-dark">
                                        Live
                                    </span>
                                </td>
                            </tr>

                            <tr>
                                <td>Total Teaching Hours Delivered</td>
                                <td>{{ $totalTeachingHours }}</td>
                                <td>
                                    <span class="badge bg-info">
                                        Tracked
                                    </span>
                                </td>
                            </tr>

                            <tr>
                                <td>Released Learning Content</td>
                                <td>{{ $contentReleasedCount }}</td>
                                <td>
                                    <span class="badge bg-success">
                                        Available
                                    </span>
                                </td>
                            </tr>

                            <tr>
                                <td>Certificates Issued</td>
                                <td>{{ $certificateCount }}</td>
                                <td>
                                    <span class="badge bg-success">
                                        Issued
                                    </span>
                                </td>
                            </tr>

                        </tbody>

                    </table>

                </div>

            </div>

            <div class="card shadow border-0 mt-4">
                <div class="card-body">

                    <h5 class="mb-4">Live LMS Report Summary</h5>

                    <table class="table table-bordered table-hover align-middle">
                        <thead class="table-light">
                            <tr>
                                <th>Sl. No</th>
                                <th>Report Area</th>
                                <th>Metric</th>
                                <th>Current Value</th>
                                <th>Status</th>
                            </tr>
                        </thead>

                        <tbody>
                            <tr>
                                <td>1</td>
                                <td>Students</td>
                                <td>Total Registered Students</td>
                                <td>{{ $studentCount }}</td>
                                <td><span class="badge bg-primary">Live</span></td>
                            </tr>

                            <tr>
                                <td>2</td>
                                <td>STEM Engineers</td>
                                <td>Total STEM Engineers</td>
                                <td>{{ $teacherCount }}</td>
                                <td><span class="badge bg-primary">Live</span></td>
                            </tr>

                            <tr>
                                <td>3</td>
                                <td>Classes</td>
                                <td>Total Classes</td>
                                <td>{{ $classCount }}</td>
                                <td><span class="badge bg-info">Live</span></td>
                            </tr>

                            <tr>
                                <td>4</td>
                                <td>Content</td>
                                <td>Total Uploaded Content</td>
                                <td>{{ $contentCount }}</td>
                                <td><span class="badge bg-info">Live</span></td>
                            </tr>

                            <tr>
                                <td>5</td>
                                <td>Assessments</td>
                                <td>Total Assessments</td>
                                <td>{{ $assessmentCount }}</td>
                                <td><span class="badge bg-warning text-dark">Live</span></td>
                            </tr>

                            <tr>
                                <td>6</td>
                                <td>Assessment Results</td>
                                <td>Completed Results</td>
                                <td>{{ $completedResults }}</td>
                                <td><span class="badge bg-success">Completed</span></td>
                            </tr>

                            <tr>
                                <td>7</td>
                                <td>Assessment Results</td>
                                <td>Pending Manual Review</td>
                                <td>{{ $pendingReviewResults }}</td>
                                <td><span class="badge bg-warning text-dark">Pending</span></td>
                            </tr>

                            <tr>
                                <td>8</td>
                                <td>Performance</td>
                                <td>Average Score</td>
                                <td>{{ number_format($averageScore, 2) }}%</td>
                                <td><span class="badge bg-success">Calculated</span></td>
                            </tr>

                            <tr>
                                <td>9</td>
                                <td>Certificates</td>
                                <td>Total Certificates</td>
                                <td>{{ $certificateCount }}</td>
                                <td><span class="badge bg-success">Live</span></td>
                            </tr>

                            <tr>
                                <td>10</td>
                                <td>Class Sessions</td>
                                <td>Total Sessions Conducted</td>
                                <td>{{ $classSessionCount }}</td>
                                <td><span class="badge bg-secondary">Tracked</span></td>
                            </tr>
                        </tbody>
                    </table>

                </div>
            </div>

        </div>
    </div>
</div>

@endsection
