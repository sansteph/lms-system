@extends('layouts.app')

@section('content')

<div class="container-fluid">
    <div class="row">

        @include('layouts.teacher-sidebar')

        <div class="col-md-10 col-lg-10 p-4">

            <div class="page-header d-flex justify-content-between align-items-center flex-wrap gap-3 mb-4">
                <div>
                    <h2 class="mb-1">Reports</h2>
                    <p class="text-muted mb-0">
                        View class performance, student progress, and assessment reports.
                    </p>
                </div>

                <button class="btn report-btn btn-sm btn-warning">
                    Export Report
                </button>
            </div>5

            <div class="row g-4 mb-4">
                <div class="col-md-3">
                    <div class="dashboard-card">
                        <h6>Student Reports</h6>
                        <h2>120</h2>
                    </div>
                </div>

                <div class="col-md-3">
                    <div class="dashboard-card">
                        <h6>Class Reports</h6>
                        <h2>5</h2>
                    </div>
                </div>

                <div class="col-md-3">
                    <div class="dashboard-card">
                        <h6>Average Score</h6>
                        <h2>82%</h2>
                    </div>
                </div>

                <div class="col-md-3">
                    <div class="dashboard-card">
                        <h6>Pending Reviews</h6>
                        <h2>3</h2>
                    </div>
                </div>
            </div>

            <div class="card shadow border-0 mb-4">
                <div class="card-body">
                    <h5 class="mb-1">Generate Report</h5>
                    <p class="text-muted mb-3">Filter reports by class, assessment, and date.</p>

                    <div class="row g-3">
                        <div class="col-md-3">
                            <select class="form-control">
                                <option>Select Report Type</option>
                                <option>Student Performance</option>
                                <option>Class-wise Report</option>
                                <option>Assessment Result</option>
                            </select>
                        </div>

                        <div class="col-md-3">
                            <select class="form-control">
                                <option>Select Class</option>
                                <option>VIII - A</option>
                                <option>IX - B</option>
                                <option>X - A</option>
                            </select>
                        </div>

                        <div class="col-md-3">
                            <select class="form-control">
                                <option>Select Assessment</option>
                                <option>AI Fundamentals Test</option>
                                <option>Robotics Quiz</option>
                                <option>IoT Assessment</option>
                            </select>
                        </div>

                        <div class="col-md-2">
                            <input type="date" class="form-control">
                        </div>

                        <div class="col-md-1">
                            <button class="btn btn-success w-100">
                                Go
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card shadow border-0">
                <div class="card-body">

                    <div class="row mb-3">
                        <div class="col-md-4">
                            <input type="text"
                                   class="form-control"
                                   placeholder="Search reports">
                        </div>
                    </div>

                    <table class="table table-bordered table-hover align-middle">
                        <thead class="table-dark">
                            <tr>
                                <th>Sl. No</th>
                                <th>Report Name</th>
                                <th>Type</th>
                                <th>Class</th>
                                <th>Generated Date</th>
                                <th width="180">Actions</th>
                            </tr>
                        </thead>

                        <tbody>
                            <tr>
                                <td>1</td>
                                <td>VIII-A Student Performance</td>
                                <td><span class="badge bg-primary">Student</span></td>
                                <td>VIII - A</td>
                                <td>05-05-2026</td>
                                <td>
                                    <button class="btn btn-sm btn-primary">View</button>
                                    <button class="btn btn-sm btn-success">Download</button>
                                </td>
                            </tr>

                            <tr>
                                <td>2</td>
                                <td>Robotics Quiz Result</td>
                                <td><span class="badge bg-warning text-dark">Assessment</span></td>
                                <td>IX - B</td>
                                <td>05-05-2026</td>
                                <td>
                                    <button class="btn btn-sm btn-primary">View</button>
                                    <button class="btn btn-sm btn-success">Download</button>
                                </td>
                            </tr>
                        </tbody>
                    </table>

                </div>
            </div>

        </div>

    </div>
</div>

@endsection