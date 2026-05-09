@extends('layouts.app')

@section('content')

<div class="container-fluid">
    <div class="row">

        @include('layouts.student-sidebar')

        <div class="col-md-10 col-lg-10 p-4">

            <div class="page-header mb-4">
                <h2 class="mb-1">Assessment History</h2>
                <p class="text-muted mb-0">
                    View completed assessments, scores, and result status.
                </p>
            </div>

            <div class="row g-4 mb-4">
                <div class="col-md-3">
                    <div class="dashboard-card">
                        <h6>Total Attempted</h6>
                        <h2>4</h2>
                    </div>
                </div>

                <div class="col-md-3">
                    <div class="dashboard-card">
                        <h6>Passed</h6>
                        <h2>3</h2>
                    </div>
                </div>

                <div class="col-md-3">
                    <div class="dashboard-card">
                        <h6>Average Score</h6>
                        <h2>86%</h2>
                    </div>
                </div>

                <div class="col-md-3">
                    <div class="dashboard-card">
                        <h6>Certificates</h6>
                        <h2>3</h2>
                    </div>
                </div>
            </div>

            <div class="card shadow border-0">
                <div class="card-body">

                    <table class="table table-bordered table-hover align-middle">
                        <thead class="table-light">
                            <tr>
                                <th>Sl. No</th>
                                <th>Assessment</th>
                                <th>Subject</th>
                                <th>Date</th>
                                <th>Score</th>
                                <th>Status</th>
                                <th width="180">Action</th>
                            </tr>
                        </thead>

                        <tbody>
                            <tr>
                                <td>1</td>
                                <td>Robotics Basics Quiz</td>
                                <td>Robotics</td>
                                <td>05-05-2026</td>
                                <td>88%</td>
                                <td><span class="badge bg-success">Passed</span></td>
                                <td>
                                    <button class="btn btn-sm btn-primary">
                                        View Result
                                    </button>
                                </td>
                            </tr>

                            <tr>
                                <td>2</td>
                                <td>AI Fundamentals Test</td>
                                <td>Artificial Intelligence</td>
                                <td>03-05-2026</td>
                                <td>72%</td>
                                <td><span class="badge bg-success">Passed</span></td>
                                <td>
                                    <button class="btn btn-sm btn-primary">
                                        View Result
                                    </button>
                                </td>
                            </tr>

                            <tr>
                                <td>3</td>
                                <td>IoT Sensor Assessment</td>
                                <td>IoT</td>
                                <td>01-05-2026</td>
                                <td>45%</td>
                                <td><span class="badge bg-danger">Needs Improvement</span></td>
                                <td>
                                    <button class="btn btn-sm btn-primary">
                                        View Result
                                    </button>
                                </td>
                            </tr>
                        </tbody>
                    </table>

                    <div class="alert alert-info mt-3 mb-0">
                        Actual history and scores will be connected after result storage is implemented.
                    </div>

                </div>
            </div>

        </div>

    </div>
</div>

@endsection