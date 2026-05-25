@extends('layouts.app')

@section('content')

<div class="auth-page">

    <div class="auth-bg-glow glow-1"></div>
    <div class="auth-bg-glow glow-2"></div>

    <div class="auth-card">

        <div class="auth-logo">

            <div class="auth-logo-icon">
                <i class="fa fa-user-shield"></i>
            </div>

            <div>
                <h5>TinkEdge LMS</h5>
                <span>Admin Access Portal</span>
            </div>

        </div>

        <div class="auth-badge">
            SYSTEM ADMINISTRATION
        </div>

        <h1 class="auth-title">
            Welcome Back
        </h1>

        <p class="auth-subtitle">
            Sign in securely to manage institutions,
            learners, analytics and LMS operations.
        </p>

        @if(session('error'))

            <div class="alert alert-danger auth-alert">
                {{ session('error') }}
            </div>

        @endif

        <form method="POST"
              action="{{ route('admin.login.submit') }}">

            @csrf

            <div class="mb-4">

                <label class="auth-label">
                    Email Address
                </label>

                <div class="auth-input-group">

                    <span class="auth-input-icon">
                        <i class="fa fa-envelope"></i>
                    </span>

                    <input type="email"
                           name="email"
                           class="form-control auth-input"
                           placeholder="Enter admin email"
                           required>

                </div>

            </div>

            <div class="mb-4">

                <label class="auth-label">
                    Password
                </label>

                <div class="auth-input-group">

                    <span class="auth-input-icon">
                        <i class="fa fa-lock"></i>
                    </span>

                    <input type="password"
                           name="password"
                           class="form-control auth-input"
                           placeholder="Enter password"
                           required>

                </div>

            </div>

            <button type="submit"
                    class="btn auth-btn w-100">

                <i class="fa fa-right-to-bracket"></i>

                Login to Admin Panel

            </button>

        </form>

        <div class="auth-footer-link">

            <a href="{{ route('home') }}">

                <i class="fa fa-arrow-left"></i>

                Back to Website

            </a>

        </div>

    </div>

</div>

@endsection