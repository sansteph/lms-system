@extends('layouts.app')

@section('content')

<div class="container-fluid">
    <div class="row">

        @include('layouts.teacher-sidebar')

        <div class="col-md-10 col-lg-10 p-4">

            <div class="page-header mb-4">
                <h2 class="mb-1">My Classes</h2>
                <p class="text-muted mb-0">
                    View assigned classes and student details.
                </p>
            </div>

            <div class="row g-4 mb-4">

                <div class="col-md-3">
                    <div class="dashboard-card">
                        <h6>Total Classes</h6>
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
                        <h6>Active Classes</h6>
                        <h2>4</h2>
                    </div>
                </div>

                <div class="col-md-3">
                    <div class="dashboard-card">
                        <h6>Pending Reviews</h6>
                        <h2>2</h2>
                    </div>
                </div>

            </div>

            <div class="card shadow border-0">
                <div class="card-body">

                    <div class="row mb-3">
                        <div class="col-md-4">
                            <input type="text" class="form-control" placeholder="Search class or section">
                        </div>
                    </div>

                    <table class="table table-bordered table-hover align-middle">
                        <thead class="table-dark">
                            <tr>
                                <th>Sl. No</th>
                                <th>Class</th>
                                <th>Section</th>
                                <th>Total Students</th>
                                <th>Content Progress</th>
                                <th>Status</th>
                                <th width="180">Actions</th>
                            </tr>
                        </thead>

                        <tbody>
                            <tr>
                                <td>1</td>
                                <td>VIII</td>
                                <td>A</td>
                                <td>40</td>
                                <td>75%</td>
                                <td><span class="badge bg-success">Active</span></td>
                                <td>
                                    <button class="btn btn-sm btn-primary">View Students</button>
                                </td>
                            </tr>

                            <tr>
                                <td>2</td>
                                <td>IX</td>
                                <td>B</td>
                                <td>38</td>
                                <td>60%</td>
                                <td><span class="badge bg-success">Active</span></td>
                                <td>
                                    <button class="btn btn-sm btn-primary">View Students</button>
                                </td>
                            </tr>

                            <tr>
                                <td>3</td>
                                <td>X</td>
                                <td>A</td>
                                <td>42</td>
                                <td>40%</td>
                                <td><span class="badge bg-warning text-dark">Pending</span></td>
                                <td>
                                    <button class="btn btn-sm btn-primary">View Students</button>
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