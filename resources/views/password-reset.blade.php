@extends('layouts.app')

@section('content')

<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-md-7 col-lg-6">
            <div class="card shadow border-0">
                <div class="card-body p-5">
                    <div class="d-flex align-items-start justify-content-between gap-3 mb-3 flex-wrap">
                        <div>
                            <p class="text-uppercase text-primary fw-semibold mb-1">Password Reset</p>
                            <h3 class="mb-1">Set a new password</h3>
                            <p class="text-muted mb-0">
                                {{ $user->name ?? 'Your account' }} can now choose a new password securely.
                            </p>
                        </div>

                        <a href="{{ $dashboardRoute ?? route('admin.login') }}" class="btn btn-outline-secondary btn-sm">
                            Back to Login
                        </a>
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
                        This reset link will expire at {{ $expiresAt ?? 'the scheduled expiry time' }}.
                    </div>

                    <form method="POST" action="{{ $submitRoute }}">
                        @csrf

                        <div class="mb-3">
                            <label class="form-label">New Password</label>
                            <input type="password" name="new_password" class="form-control" required>
                        </div>

                        <div class="mb-4">
                            <label class="form-label">Confirm New Password</label>
                            <input type="password" name="new_password_confirmation" class="form-control" required>
                        </div>

                        <button type="submit" class="btn btn-primary">
                            Update Password
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

@endsection
