@extends('layouts.app')

@section('content')

<div class="container-fluid">
    <div class="row">

        @include('layouts.teacher-sidebar')

        <div class="col-md-10 col-lg-10 p-4">

            <div class="page-header mb-4">
                <h2 class="mb-1">Profile</h2>
                <p class="text-muted mb-0">
                    Manage STEM Engineer account and personal details.
                </p>
            </div>

            <div class="row g-4">

                <div class="col-lg-4">
                    <div class="card shadow border-0">
                        <div class="card-body text-center p-4">

                            <div class="rounded-circle bg-primary text-white mx-auto mb-3 d-flex align-items-center justify-content-center"
                                 style="width: 90px; height: 90px; font-size: 32px; font-weight: 700;">
                                {{ strtoupper(substr($teacher->name, 0, 1)) }}
                            </div>

                            <h4 class="mb-1">{{ $teacher->name }}</h4>
                            <p class="text-muted mb-2">STEM Engineer</p>

                            @if($teacher->status == 1)
                                <span class="badge bg-success">Active</span>
                            @else
                                <span class="badge bg-danger">Inactive</span>
                            @endif

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
                                        <label class="form-label">STEM Engineer ID</label>
                                        <input type="text" class="form-control" value="{{ $teacher->user_id }}" readonly>
                                    </div>

                                    <div class="col-md-6">
                                        <label class="form-label">Full Name</label>
                                        <input type="text" class="form-control" value="{{ $teacher->name }}" readonly>
                                    </div>

                                    <div class="col-md-6">
                                        <label class="form-label">Email Address</label>
                                        <input type="email" class="form-control" value="{{ $teacher->email }}" readonly>
                                    </div>

                                    <div class="col-md-6">
                                        <label class="form-label">Role</label>
                                        <input type="text" class="form-control" value="{{ $teacher->role == 'Teacher' ? 'STEM Engineer' : $teacher->role }}" readonly>
                                    </div>

                                    <div class="col-md-6">
                                        <label class="form-label">Account Status</label>
                                        <input type="text"
                                               class="form-control"
                                               value="{{ $teacher->status == 1 ? 'Active' : 'Inactive' }}"
                                               readonly>
                                    </div>

                                    <div class="col-md-6">
                                        <label class="form-label">Joined On</label>
                                        <input type="text"
                                               class="form-control"
                                               value="{{ $teacher->created_at->format('d-m-Y') }}"
                                               readonly>
                                    </div>

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