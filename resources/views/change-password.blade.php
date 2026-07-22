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

    <div class="row justify-content-center">

        <div class="col-md-7 col-lg-6">

            <div class="card shadow border-0">

                <div class="card-body p-4">

                    <div class="d-flex justify-content-between align-items-center gap-3 mb-4">
                        <h3 class="mb-0">
                            Change Password
                        </h3>
                        <button type="button"
                                class="btn btn-outline-secondary btn-sm"
                                onclick="window.history.back();">
                            Back
                        </button>
                    </div>

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

                    <form method="POST"
                          action="{{ $submitRoute ?? route('admin.change.password.submit') }}">

                        @csrf

                        <div class="mb-3">

                            <label class="form-label">
                                Current Password
                            </label>

                            <input type="password"
                                   name="current_password"
                                   class="form-control"
                                   required>

                        </div>

                        <div class="mb-3">

                            <label class="form-label">
                                New Password
                            </label>

                            <input type="password"
                                   name="new_password"
                                   class="form-control"
                                   required>

                        </div>

                        <div class="mb-3">

                            <label class="form-label">
                                Confirm New Password
                            </label>

                            <input type="password"
                                   name="new_password_confirmation"
                                   class="form-control"
                                   required>

                        </div>

                        <button type="submit"
                                class="btn btn-primary">

                            Send Confirmation Email

                        </button>

                    </form>

                </div>

            </div>

        </div>

    </div>

        </div>
    </div>
</div>

@endsection
