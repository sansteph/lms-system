@extends('layouts.app')

@section('content')

<div class="auth-page">

    <div class="auth-bg-glow glow-1"></div>
    <div class="auth-bg-glow glow-2"></div>

    <div class="auth-card">

        <div class="auth-logo">
            <div class="auth-logo-icon">
                <i class="fa fa-user-graduate"></i>
            </div>

            <div>
                <h5>TinkEdge LMS</h5>
                <span>Hybrid Learning</span>
            </div>
        </div>

        <div class="auth-badge">
            HYBRID LEARNER
        </div>

        <h1 class="auth-title">
            Get Started
        </h1>

        <p class="auth-subtitle">
            Create your account to explore premium courses and learn independently.
        </p>

        @if ($errors->any())
            <div class="alert alert-danger auth-alert">
                <ul class="mb-0">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form method="POST"
              action="{{ route('independent.register.submit') }}">

            @csrf

            <div class="mb-4">
                <label class="auth-label">Full Name</label>

                <div class="auth-input-group">
                    <span class="auth-input-icon">
                        <i class="fa fa-user"></i>
                    </span>

                    <input type="text"
                           name="name"
                           class="form-control auth-input"
                           placeholder="Enter your full name"
                           value="{{ old('name') }}"
                           required>
                </div>
            </div>

            <div class="mb-4">
                <label class="auth-label">Email Address</label>

                <div class="auth-input-group">
                    <span class="auth-input-icon">
                        <i class="fa fa-envelope"></i>
                    </span>

                    <input type="email"
                           name="email"
                           class="form-control auth-input"
                           placeholder="Enter your email"
                           value="{{ old('email') }}"
                           required>
                </div>
            </div>

            <div class="mb-4">
                <label class="auth-label">Phone Number</label>

                <div class="auth-input-group">
                    <span class="auth-input-icon">
                        <i class="fa fa-phone"></i>
                    </span>

                    <input type="text"
                           name="phone"
                           class="form-control auth-input"
                           placeholder="Enter phone number"
                           value="{{ old('phone') }}">
                </div>
            </div>

            <div class="mb-4">
                <label class="auth-label">Password</label>

                <div class="auth-input-group">
                    <span class="auth-input-icon">
                        <i class="fa fa-lock"></i>
                    </span>

                    <input type="password"
                           name="password"
                           class="form-control auth-input"
                           placeholder="Create password"
                           required>
                </div>
            </div>

            <div class="mb-4">
                <label class="auth-label">Confirm Password</label>

                <div class="auth-input-group">
                    <span class="auth-input-icon">
                        <i class="fa fa-lock"></i>
                    </span>

                    <input type="password"
                           name="password_confirmation"
                           class="form-control auth-input"
                           placeholder="Confirm password"
                           required>
                </div>
            </div>

            <button type="submit" class="btn auth-btn w-100">
                <i class="fa fa-arrow-right"></i>
                Create Account
            </button>

        </form>

        <div class="auth-footer-link">
            <a href="{{ route('independent.login') }}">
                Already have an account? Login
            </a>
        </div>

    </div>

</div>

@endsection