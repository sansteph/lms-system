@extends('layouts.app')

@section('content')

<div class="auth-page {{ $role === 'teacher' ? 'teacher-theme' : 'admin-auth-page' }}">

    <div class="auth-bg-glow glow-1"></div>
    <div class="auth-bg-glow glow-2"></div>

    <div class="auth-card">

        <div class="auth-logo">

            <div class="auth-logo-icon">
                <i class="fa fa-key"></i>
            </div>

            <div>
                <h5>{{ $role === 'teacher' ? 'STEM Engineer' : 'InnovatEdge' }}</h5>
                <span>{{ $role === 'teacher' ? 'STEM Engineer Access Portal' : 'Admin Access Portal' }}</span>
            </div>

        </div>

        <div class="auth-badge">
            PASSWORD RESET
        </div>

        <h1 class="auth-title">
            Forgot Password
        </h1>

        <p class="auth-subtitle">
            Enter your login email and we will send a secure link to reset your password.
        </p>

        @if(session('success'))
            <div class="alert alert-success auth-alert">
                {{ session('success') }}
            </div>
        @endif

        @if(session('error'))
            <div class="alert alert-danger auth-alert">
                {{ session('error') }}
            </div>
        @endif

        <form method="POST" action="{{ $submitRoute }}">
            @csrf

            <div class="mb-4">
                <label class="auth-label">Email Address</label>

                <div class="auth-input-group">
                    <span class="auth-input-icon">
                        <i class="fa fa-envelope"></i>
                    </span>

                    <input type="email"
                           name="email"
                           class="form-control auth-input"
                           placeholder="Enter your login email"
                           required>
                </div>
            </div>

            <button type="submit" class="btn auth-btn w-100">
                <i class="fa fa-paper-plane"></i>
                Send Reset Link
            </button>
        </form>

        <div class="auth-footer-link">
            <a href="{{ $loginRoute }}">
                <i class="fa fa-arrow-left"></i>
                Back to Login
            </a>
        </div>

    </div>

</div>

@endsection
