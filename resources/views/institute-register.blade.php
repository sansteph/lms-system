@extends('layouts.app')

@section('content')

<div class="auth-page">

    <div class="auth-bg-glow glow-1"></div>
    <div class="auth-bg-glow glow-2"></div>

    <div class="auth-card">

        <div class="auth-logo">

            <div class="auth-logo-icon">
                <i class="fa fa-building-columns"></i>
            </div>

            <div>
                <h5>TinkEdge LMS</h5>
                <span>Institute Admin Registration</span>
            </div>

        </div>

        <div class="auth-badge">
            INSTITUTE ONBOARDING
        </div>

        <h1 class="auth-title">
            Institute Registration
        </h1>

        <p class="auth-subtitle">
            Register your institute and create the primary administrator account.
        </p>

        @if(session('error'))

            <div class="alert alert-danger auth-alert">
                {{ session('error') }}
            </div>

        @endif

        @if($errors->any())

            <div class="alert alert-danger auth-alert">

                <ul class="mb-0">

                    @foreach($errors->all() as $error)

                        <li>{{ $error }}</li>

                    @endforeach

                </ul>

            </div>

        @endif

        <form method="POST"
              action="{{ route('admin.institute.register.submit') }}">

            @csrf

            <div class="mb-4">

                <label class="auth-label">
                    Admin Name
                </label>

                <div class="auth-input-group">

                    <span class="auth-input-icon">
                        <i class="fa fa-user"></i>
                    </span>

                    <input type="text"
                           name="admin_name"
                           class="form-control auth-input"
                           placeholder="Enter admin name"
                           value="{{ old('admin_name') }}"
                           required>

                </div>

            </div>

            <div class="mb-4">

                <label class="auth-label">
                    Admin Email
                </label>

                <div class="auth-input-group">

                    <span class="auth-input-icon">
                        <i class="fa fa-envelope"></i>
                    </span>

                    <input type="email"
                           name="admin_email"
                           class="form-control auth-input"
                           placeholder="Enter admin email"
                           value="{{ old('admin_email') }}"
                           required>

                </div>

            </div>

            <div class="mb-4">

                <label class="auth-label">
                    Institute Name
                </label>

                <div class="auth-input-group">

                    <span class="auth-input-icon">
                        <i class="fa fa-building"></i>
                    </span>

                    <input type="text"
                           name="institute_name"
                           class="form-control auth-input"
                           placeholder="Enter institute name"
                           value="{{ old('institute_name') }}"
                           required>

                </div>

            </div>

            <div class="mb-4">

                <label class="auth-label">
                    Institute Location
                </label>

                <div class="auth-input-group">

                    <span class="auth-input-icon">
                        <i class="fa fa-location-dot"></i>
                    </span>

                    <input type="text"
                        name="location"
                        class="form-control auth-input"
                        placeholder="Enter institute location"
                        value="{{ old('location') }}"
                        required>

                </div>

            </div>

            <div class="mb-4">

                <label class="auth-label">
                    Phone Number
                </label>

                <div class="auth-input-group">

                    <span class="auth-input-icon">
                        <i class="fa fa-phone"></i>
                    </span>

                    <input type="text"
                           name="phone"
                           class="form-control auth-input"
                           placeholder="Enter contact number"
                           value="{{ old('phone') }}"
                           required>

                </div>

            </div>

            <button type="submit"
                    class="btn auth-btn w-100">

                <i class="fa fa-user-shield"></i>

                Create Admin Account

            </button>

        </form>

        <div class="auth-footer-link">

            <a href="{{ route('admin.login') }}">

                <i class="fa fa-arrow-left"></i>

                Back to Admin Login

            </a>

        </div>

    </div>

</div>

@endsection
