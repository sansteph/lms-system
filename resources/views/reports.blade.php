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

                <button class="btn btn-primary btn-sm">
                    Download Summary
                </button>
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
                        <h6>Teachers</h6>
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

            <div class="card shadow border-0 mb-4">
                <div class="card-body">
                    <h5 class="mb-1">Generate Report</h5>
                    <p class="text-muted mb-3">Choose report type and filters to view or download.</p>

                    <div class="row g-3">
                        <div class="col-md-3">
                            <select class="form-control">
                                <option>Select Report Type</option>
                                <option>Student Performance</option>
                                <option>Teacher Performance</option>
                                <option>Class-wise Report</option>
                                <option>MIS Report</option>
                            </select>
                        </div>

                        <div class="col-md-3">
                            <select class="form-control">
                                <option>Select Institute</option>
                                <option>ABC School</option>
                                <option>Bright Future Academy</option>
                            </select>
                        </div>

                        <div class="col-md-2">
                            <select class="form-control">
                                <option>Select Class</option>
                                <option>VIII - A</option>
                                <option>IX - B</option>
                                <option>X - A</option>
                            </select>
                        </div>

                        <div class="col-md-2">
                            <input type="date" class="form-control">
                        </div>

                        <div class="col-md-2">
                            <button class="btn btn-success w-100">Generate</button>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card shadow border-0">
                <div class="card-body">

                    <table class="table table-bordered table-hover align-middle">
                        <thead class="table-light">
                            <tr>
                                <th>Sl. No</th>
                                <th>Report Name</th>
                                <th>Report Type</th>
                                <th>Description</th>
                                <th width="180">Actions</th>
                            </tr>
                        </thead>

                        <tbody>
                            <tr>
                                <td>1</td>
                                <td>Student Performance Report</td>
                                <td><span class="badge bg-primary">Student</span></td>
                                <td>View student count, class-wise student details, and performance summary.</td>
                                <td>
                                    <button class="btn btn-sm btn-outline-primary">View</button>
                                    <button class="btn btn-sm btn-outline-success">Download</button>
                                </td>
                            </tr>

                            <tr>
                                <td>2</td>
                                <td>Teacher Performance Report</td>
                                <td><span class="badge bg-warning text-dark">Teacher</span></td>
                                <td>View teacher count, assigned classes, and assessment participation.</td>
                                <td>
                                    <button class="btn btn-sm btn-outline-primary">View</button>
                                    <button class="btn btn-sm btn-outline-success">Download</button>
                                </td>
                            </tr>

                            <tr>
                                <td>3</td>
                                <td>Class-wise Report</td>
                                <td><span class="badge bg-info">Class</span></td>
                                <td>View class count, sections, assigned teachers, and student distribution.</td>
                                <td>
                                    <button class="btn btn-sm btn-outline-primary">View</button>
                                    <button class="btn btn-sm btn-outline-success">Download</button>
                                </td>
                            </tr>

                            <tr>
                                <td>4</td>
                                <td>MIS Report</td>
                                <td><span class="badge bg-success">MIS</span></td>
                                <td>View overall LMS summary including institutes, content, assessments, and notifications.</td>
                                <td>
                                    <button class="btn btn-sm btn-outline-primary">View</button>
                                    <button class="btn btn-sm btn-outline-success">Download</button>
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