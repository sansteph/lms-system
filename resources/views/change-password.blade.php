@extends('layouts.app')

@section('content')

<div class="container-fluid">
    <div class="row">
        @if(($sidebar ?? 'admin') == 'teacher')
            @include('layouts.teacher-sidebar')
        @else
            @include('layouts.sidebar')
        @endif

        <div class="col-md-10 col-lg-10 p-4">
            <div class="page-header d-flex justify-content-between align-items-center flex-wrap gap-3 mb-4">
                <div>
                    <h2 class="mb-1">Change Password</h2>
                    <p class="text-muted mb-0">Update your login password with email confirmation.</p>
                </div>
                <button type="button" class="btn btn-outline-secondary btn-sm" onclick="window.history.back();">Back</button>
            </div>

            <div class="row justify-content-center">
                <div class="col-md-7 col-lg-6">
                    <div class="card shadow border-0">
                        <div class="card-body p-4">
                            @if(session('success'))
                                <div class="alert alert-success">
                                    {{ session('success') }}
                                </div>
                            @endif

                            @if(session('error'))
                                <div class="alert alert-danger">
                                    {{ session('error') }}
                                </div>
                            @endif

                            <div class="alert alert-info">
                                Your password will not change immediately. We will send a confirmation email to your login email address, and the update will complete only after you click <strong>Yes, it is me</strong>.
                            </div>

                            <form method="POST" action="{{ $submitRoute ?? route('admin.change.password.submit') }}">
                                @csrf

                                <div class="mb-3">
                                    <label class="form-label">Current Password</label>
                                    <input type="password" name="current_password" class="form-control" required>
                                </div>

                                <div class="mb-3">
                                    <label class="form-label">New Password</label>
                                    <input type="password" name="new_password" class="form-control" required>
                                </div>

                                <div class="mb-3">
                                    <label class="form-label">Confirm New Password</label>
                                    <input type="password" name="new_password_confirmation" class="form-control" required>
                                </div>

                                <button type="submit" class="btn btn-primary">Send Confirmation Email</button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

@endsection
