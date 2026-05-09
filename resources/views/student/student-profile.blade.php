@extends('layouts.app')

@section('content')

<div class="container-fluid">
    <div class="row">

        @include('layouts.student-sidebar')

        <div class="col-md-10 col-lg-10 p-4">

            <div class="page-header mb-4">
                <h2 class="mb-1">Profile</h2>
                <p class="text-muted mb-0">
                    View student account and academic details.
                </p>
            </div>

            <div class="row g-4">
                <div class="col-lg-4">
                    <div class="card shadow border-0">
                        <div class="card-body text-center p-4">

                            <div class="rounded-circle bg-primary text-white mx-auto mb-3 d-flex align-items-center justify-content-center"
                                 style="width: 90px; height: 90px; font-size: 32px; font-weight: 700;">
                                S
                            </div>

                            <h4 class="mb-1">Student Name</h4>
                            <p class="text-muted mb-2">Class VIII - A</p>

                            <span class="badge bg-success">Active</span>

                        </div>
                    </div>
                </div>

                <div class="col-lg-8">
                    <div class="card shadow border-0">
                        <div class="card-body p-4">

                            <h5 class="mb-3">Profile Details</h5>

                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label class="form-label">Student ID</label>
                                    <input type="text" class="form-control" value="STU001" readonly>
                                </div>

                                <div class="col-md-6">
                                    <label class="form-label">Full Name</label>
                                    <input type="text" class="form-control" value="Student Name" readonly>
                                </div>

                                <div class="col-md-6">
                                    <label class="form-label">Institute</label>
                                    <input type="text" class="form-control" value="ABC School" readonly>
                                </div>

                                <div class="col-md-3">
                                    <label class="form-label">Class</label>
                                    <input type="text" class="form-control" value="VIII" readonly>
                                </div>

                                <div class="col-md-3">
                                    <label class="form-label">Section</label>
                                    <input type="text" class="form-control" value="A" readonly>
                                </div>

                                <div class="col-md-6">
                                    <label class="form-label">Contact</label>
                                    <input type="text" class="form-control" value="9876543210" readonly>
                                </div>

                                <div class="col-md-6">
                                    <label class="form-label">Badge Count</label>
                                    <input type="text" class="form-control" value="12 Badges" readonly>
                                </div>
                            </div>

                        </div>
                    </div>
                </div>
            </div>

        </div>

    </div>
</div>

@endsection