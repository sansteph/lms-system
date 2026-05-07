@extends('layouts.app')

@section('content')

<div class="container-fluid">
    <div class="row">

        @include('layouts.teacher-sidebar')

        <div class="col-md-10 col-lg-10 p-4">

            <div class="page-header d-flex justify-content-between align-items-center flex-wrap gap-3 mb-4">
                <div>
                    <h2 class="mb-1">Certificates</h2>
                    <p class="text-muted mb-0">
                        Generate and print certificates for eligible students.
                    </p>
                </div>

                <button class="btn btn-primary btn-sm">
                    Generate Certificate
                </button>
            </div>

            <div class="row g-4 mb-4">
                <div class="col-md-3">
                    <div class="dashboard-card">
                        <h6>Total Certificates</h6>
                        <h2>85</h2>
                    </div>
                </div>

                <div class="col-md-3">
                    <div class="dashboard-card">
                        <h6>Generated Today</h6>
                        <h2>6</h2>
                    </div>
                </div>

                <div class="col-md-3">
                    <div class="dashboard-card">
                        <h6>Pending</h6>
                        <h2>12</h2>
                    </div>
                </div>

                <div class="col-md-3">
                    <div class="dashboard-card">
                        <h6>Eligible Students</h6>
                        <h2>97</h2>
                    </div>
                </div>
            </div>

            <div class="card shadow border-0">
                <div class="card-body">

                    <div class="row mb-3">
                        <div class="col-md-4">
                            <input type="text"
                                   class="form-control"
                                   placeholder="Search student or certificate">
                        </div>

                        <div class="col-md-3">
                            <select class="form-control">
                                <option>Filter by Class</option>
                                <option>VIII - A</option>
                                <option>IX - B</option>
                                <option>X - A</option>
                            </select>
                        </div>

                        <div class="col-md-3">
                            <select class="form-control">
                                <option>Filter by Status</option>
                                <option>Generated</option>
                                <option>Pending</option>
                            </select>
                        </div>
                    </div>

                    <table class="table table-bordered table-hover align-middle">
                        <thead class="table-dark">
                            <tr>
                                <th>Sl. No</th>
                                <th>Student Name</th>
                                <th>Class</th>
                                <th>Assessment</th>
                                <th>Score</th>
                                <th>Status</th>
                                <th width="200">Actions</th>
                            </tr>
                        </thead>

                        <tbody>
                            <tr>
                                <td>1</td>
                                <td>Ananya Rao</td>
                                <td>VIII - A</td>
                                <td>AI Fundamentals Test</td>
                                <td>92%</td>
                                <td><span class="badge bg-success">Generated</span></td>
                                <td>
                                    <button class="btn btn-sm btn-primary">View</button>
                                    <button class="btn btn-sm btn-success">Print</button>
                                </td>
                            </tr>
                            <tr>
                                <td>2</td>
                                <td>Rahul Kumar</td>
                                <td>IX - B</td>
                                <td>Robotics Quiz</td>
                                <td>88%</td>
                                <td><span class="badge bg-warning text-dark">Pending</span></td>
                                <td>
                                    <button class="btn btn-sm btn-warning">Generate</button>
                                </td>
                            </tr>

                            <tr>
                                <td>3</td>
                                <td>Meera Nair</td>
                                <td>X - A</td>
                                <td>IoT Assessment</td>
                                <td>95%</td>
                                <td><span class="badge bg-success">Generated</span></td>
                                <td>
                                    <button class="btn btn-sm btn-primary">View</button>
                                    <button class="btn btn-sm btn-success">Print</button>
                                </td>
                            </tr>
                        </tbody>
                    </table>

                    <div class="alert alert-info mt-3 mb-0">
                        Certificates can be generated after assessment results are finalized.
                    </div>

                </div>
            </div>
        </div>
    </div>
</div>

@endsection