@extends('layouts.app')

@section('content')

<div class="container-fluid">
    <div class="row">

        @include('layouts.teacher-sidebar')

        <div class="col-md-10 col-lg-10 p-4">

            <div class="page-header mb-4">
                <h2 class="mb-1">Profile</h2>
                <p class="text-muted mb-0">
                    Manage teacher account and personal details.
                </p>
            </div>

            <div class="row g-4">

                <div class="col-lg-4">
                    <div class="card shadow border-0">
                        <div class="card-body text-center p-4">

                            <div class="rounded-circle bg-primary text-white mx-auto mb-3 d-flex align-items-center justify-content-center"
                                 style="width: 90px; height: 90px; font-size: 32px; font-weight: 700;">
                                T
                            </div>

                            <h4 class="mb-1">Priya Nair</h4>
                            <p class="text-muted mb-2">Teacher</p>

                            <span class="badge bg-success">Active</span>

                        </div>
                    </div>
                </div>

                <div class="col-lg-8">
                    <div class="card shadow border-0">
                        <div class="card-body p-4">

                            <h5 class="mb-3">Profile Details</h5>

                            <form>
                                <div class="row g-3">

                                    <div class="col-md-6">
                                        <label class="form-label">Teacher ID</label>
                                        <input type="text" class="form-control" value="TCH001">
                                    </div>

                                    <div class="col-md-6">
                                        <label class="form-label">Full Name</label>
                                        <input type="text" class="form-control" value="Priya Nair">
                                    </div>

                                    <div class="col-md-6">
                                        <label class="form-label">Email Address</label>
                                        <input type="email" class="form-control" value="priya@example.com">
                                    </div>

                                    <div class="col-md-6">
                                        <label class="form-label">Phone Number</label>
                                        <input type="text" class="form-control" value="9876543210">
                                    </div>

                                    <div class="col-md-6">
                                        <label class="form-label">Assigned Department</label>
                                        <input type="text" class="form-control" value="Robotics / AI">
                                    </div>

                                    <div class="col-md-6">
                                        <label class="form-label">Assigned Classes</label>
                                        <input type="text" class="form-control" value="VIII-A, IX-B, X-A">
                                    </div>

                                    <div class="col-12">
                                        <label class="form-label">Address</label>
                                        <textarea class="form-control" rows="3">Bangalore, Karnataka</textarea>
                                    </div>

                                </div>

                                <div class="mt-4">
                                    <button type="button" class="btn btn-success">
                                        Update Profile
                                    </button>
                                </div>
                            </form>

                        </div>
                    </div>
                </div>

            </div>

        </div>

    </div>
</div>

@endsection