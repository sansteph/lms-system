@extends('layouts.app')

@section('content')

<div class="portal-page">

    <div class="login-card">

        <div class="login-icon admin-icon">
            <i class="fa fa-user-shield"></i>
        </div>

        <h2 class="login-title">
            Admin Portal
        </h2>

        <p class="login-subtitle">
            Sign in to manage the LMS system.
        </p>

        @if(session('error'))

            <div class="alert alert-danger border-0 shadow-sm">
                {{ session('error') }}
            </div>

        @endif

        <form method="POST"
              action="{{ route('admin.login.submit') }}">

            @csrf

            <div class="mb-4">

                <label class="login-label">
                    Email Address
                </label>

                <div class="input-group modern-input">

                    <span class="input-group-text">
                        <i class="fa fa-envelope"></i>
                    </span>

                    <input type="email"
                           name="email"
                           class="form-control"
                           placeholder="Enter admin email"
                           required>

                </div>

            </div>

            <div class="mb-4">

                <label class="login-label">
                    Password
                </label>

                <div class="input-group modern-input">

                    <span class="input-group-text">
                        <i class="fa fa-lock"></i>
                    </span>

                    <input type="password"
                           name="password"
                           class="form-control"
                           placeholder="Enter password"
                           required>

                </div>

            </div>

            <button type="submit"
                    class="btn login-btn w-100">

                <i class="fa fa-right-to-bracket me-2"></i>
                Login to Admin Panel

            </button>

        </form>

        <div class="back-home">

            <a href="{{ route('home') }}">
                <i class="fa fa-arrow-left"></i>
                Back to Portal
            </a>

        </div>

    </div>

</div>

@endsection