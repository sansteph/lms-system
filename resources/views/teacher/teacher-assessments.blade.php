@extends('layouts.app')

@section('content')

<div class="container-fluid">
    <div class="row">

        @include('layouts.teacher-sidebar')

        <div class="col-md-10 col-lg-10 p-4">

            <div class="page-header d-flex justify-content-between align-items-center flex-wrap gap-3 mb-4">

                <div>
                    <h2 class="mb-1">Assessments</h2>

                    <p class="text-muted mb-0">
                        Manage student assessments, marks, and performance tracking.
                    </p>
                </div>

                <button class="btn btn-primary btn-sm">
                    Create Assessment
                </button>

            </div>

            <div class="row g-4 mb-4">

                <div class="col-md-3">
                    <div class="dashboard-card">
                        <h6>Total Assessments</h6>
                        <h2>18</h2>
                    </div>
                </div>

                <div class="col-md-3">
                    <div class="dashboard-card">
                        <h6>Completed</h6>
                        <h2>12</h2>
                    </div>
                </div>

                <div class="col-md-3">
                    <div class="dashboard-card">
                        <h6>Pending</h6>
                        <h2>6</h2>
                    </div>
                </div>

                <div class="col-md-3">
                    <div class="dashboard-card">
                        <h6>Average Score</h6>
                        <h2>82%</h2>
                    </div>
                </div>

            </div>

            <div class="card shadow border-0">
                <div class="card-body">

                    <div class="row mb-3">

                        <div class="col-md-4">
                            <input type="text"
                                   class="form-control"
                                   placeholder="Search assessment">
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
                                <option>Completed</option>
                                <option>Pending</option>
                            </select>
                        </div>

                    </div>

                    <table class="table table-bordered table-hover align-middle">

                        <thead class="table-dark">
                            <tr>
                                <th>Sl. No</th>
                                <th>Assessment Title</th>
                                <th>Class</th>
                                <th>Total Marks</th>
                                <th>Students Appeared</th>
                                <th>Status</th>
                                <th width="220">Actions</th>
                            </tr>
                        </thead>

                        <tbody>

                            <tr>
                                <td>1</td>
                                <td>AI Fundamentals Test</td>
                                <td>VIII - A</td>
                                <td>50</td>
                                <td>38</td>
                                <td>
                                    <span class="badge bg-success">
                                        Completed
                                    </span>
                                </td>
                                <td>
                                    <button class="btn btn-sm btn-primary">
                                        View Results
                                    </button>
                                </td>
                            </tr>

                            <tr>
                                <td>2</td>
                                <td>Robotics Quiz</td>
                                <td>IX - B</td>
                                <td>25</td>
                                <td>30</td>
                                <td>
                                    <span class="badge bg-warning text-dark">
                                        Pending
                                    </span>
                                </td>
                                <td>
                                    <button class="btn btn-sm btn-success">
                                        Enter Marks
                                    </button>
                                </td>
                            </tr>

                            <tr>
                                <td>3</td>
                                <td>IoT Assessment</td>
                                <td>X - A</td>
                                <td>100</td>
                                <td>42</td>
                                <td>
                                    <span class="badge bg-info">
                                        Ongoing
                                    </span>
                                </td>
                                <td>
                                    <button class="btn btn-sm btn-warning">
                                        Generate Link
                                    </button>
                                </td>
                            </tr>

                        </tbody>

                    </table>

                    <div class="alert alert-info mt-3 mb-0">
                        Teachers can generate student assessment links and track results here.
                    </div>

                </div>
            </div>

        </div>

    </div>
</div>

@endsection