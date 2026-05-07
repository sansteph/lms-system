@extends('layouts.app')

@section('content')

<div class="container-fluid">
    <div class="row">

        @include('layouts.teacher-sidebar')

        <div class="col-md-10 col-lg-10 p-4">

            <div class="page-header mb-4">
                <h2 class="mb-1">Learning Content</h2>
                <p class="text-muted mb-0">
                    Access assigned PPT, PDF, and video lessons in sequence.
                </p>
            </div>

            <div class="row g-4 mb-4">

                <div class="col-md-3">
                    <div class="dashboard-card">
                        <h6>Total Content</h6>
                        <h2>24</h2>
                    </div>
                </div>

                <div class="col-md-3">
                    <div class="dashboard-card">
                        <h6>Completed</h6>
                        <h2>16</h2>
                    </div>
                </div>

                <div class="col-md-3">
                    <div class="dashboard-card">
                        <h6>Pending</h6>
                        <h2>8</h2>
                    </div>
                </div>

                <div class="col-md-3">
                    <div class="dashboard-card">
                        <h6>Locked</h6>
                        <h2>4</h2>
                    </div>
                </div>

            </div>

            <div class="card shadow border-0">
                <div class="card-body">

                    <div class="row mb-3">
                        <div class="col-md-4">
                            <input type="text"
                                   class="form-control"
                                   placeholder="Search content">
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
                                <option>Filter by Type</option>
                                <option>PPT</option>
                                <option>PDF</option>
                                <option>Video</option>
                            </select>
                        </div>
                    </div>

                    <table class="table table-bordered table-hover align-middle">
                        <thead class="table-dark">
                            <tr>
                                <th>Sl. No</th>
                                <th>Title</th>
                                <th>Class</th>
                                <th>Type</th>
                                <th>Priority</th>
                                <th>Access Status</th>
                                <th>Progress</th>
                                <th width="180">Action</th>
                            </tr>
                        </thead>

                        <tbody>
                            <tr>
                                <td>1</td>
                                <td>Introduction to AI</td>
                                <td>VIII - A</td>
                                <td><span class="badge bg-info">PDF</span></td>
                                <td>1</td>
                                <td><span class="badge bg-success">Unlocked</span></td>
                                <td>Completed</td>
                                <td>
                                    <button class="btn btn-sm btn-primary">
                                        Open
                                    </button>
                                </td>
                            </tr>

                            <tr>
                                <td>2</td>
                                <td>Robotics Basics</td>
                                <td>VIII - A</td>
                                <td><span class="badge bg-primary">PPT</span></td>
                                <td>2</td>
                                <td><span class="badge bg-success">Unlocked</span></td>
                                <td>In Progress</td>
                                <td>
                                    <button class="btn btn-sm btn-primary">
                                        Continue
                                    </button>
                                </td>
                            </tr>

                            <tr>
                                <td>3</td>
                                <td>IoT Sensor Demo</td>
                                <td>VIII - A</td>
                                <td><span class="badge bg-danger">Video</span></td>
                                <td>3</td>
                                <td><span class="badge bg-secondary">Locked</span></td>
                                <td>Pending</td>
                                <td>
                                    <button class="btn btn-sm btn-secondary" disabled>
                                        Locked
                                    </button>
                                </td>
                            </tr>
                        </tbody>
                    </table>

                    <div class="alert alert-info mt-3 mb-0">
                        Content unlocks only after the previous lesson is completed.
                    </div>

                </div>
            </div>

        </div>

    </div>
</div>

@endsection