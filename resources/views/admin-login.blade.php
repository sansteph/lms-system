@extends('layouts.app')

@section('content')

@php
    $portalRole = $portalRole ?? request('portal');
    $isPrincipalPortal = $portalRole === 'principal';
    $accessLabel = $isPrincipalPortal ? 'Principal Access Portal' : 'Admin Access Portal';
    $badgeLabel = $isPrincipalPortal ? 'INSTITUTE REPORTING' : 'SYSTEM ADMINISTRATION';
    $subtitle = $isPrincipalPortal
        ? 'Sign in securely to review your institute sessions and student performance reports.'
        : 'Sign in securely to manage institutions, learners, analytics and LMS operations.';
    $emailPlaceholder = $isPrincipalPortal ? 'Enter principal email' : 'Enter admin email';
    $buttonLabel = $isPrincipalPortal ? 'Login to Principal Panel' : 'Login to Admin Panel';
    $iconClass = $isPrincipalPortal ? 'fa-user-tie' : 'fa-user-shield';
@endphp

<div class="auth-page admin-auth-page">

    <div class="auth-bg-glow glow-1"></div>
    <div class="auth-bg-glow glow-2"></div>

    <div class="auth-card">

        <div class="auth-logo">

            <div class="auth-logo-icon">
                <i class="fa {{ $iconClass }}"></i>
            </div>

            <div>
                <h5>InnovatEdge</h5>
                <span>{{ $accessLabel }}</span>
            </div>

        </div>

        <div class="auth-badge">
            {{ $badgeLabel }}
        </div>

        <h1 class="auth-title">
            Welcome Back
        </h1>

        <p class="auth-subtitle">
            {{ $subtitle }}
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
                           placeholder="{{ $emailPlaceholder }}"
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

            <div class="d-flex justify-content-end mb-4">
                <a href="{{ route('admin.forgot.password') }}" class="small text-decoration-none fw-semibold">
                    Forgot password?
                </a>
            </div>

            <button type="submit"
                    class="btn auth-btn w-100">

                <i class="fa fa-right-to-bracket"></i>

                {{ $buttonLabel }}

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
