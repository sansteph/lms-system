@extends('layouts.app')

@section('content')

<div class="portal-page">

    <div class="login-card">

        <div class="login-icon student-icon">
            <i class="fa fa-user-graduate"></i>
        </div>

        <h2 class="login-title">
            Student Portal
        </h2>

        <p class="login-subtitle">
            Access assessments, badges, achievements, and learning progress.
        </p>

        @if(session('error'))

            <div class="alert alert-danger border-0 shadow-sm">
                {{ session('error') }}
            </div>

        @endif

        <form method="POST"
              action="{{ route('student.login.submit') }}">

            @csrf

            <div class="mb-4">

                <label class="login-label">
                    Student ID
                </label>

                <div class="input-group modern-input">

                    <span class="input-group-text">
                        <i class="fa fa-id-card"></i>
                    </span>

                    <input type="text"
                           name="student_id"
                           class="form-control"
                           placeholder="Enter Student ID"
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
                           placeholder="Enter Password"
                           required>

                </div>

            </div>

            <button type="submit"
                    class="btn login-btn student-btn w-100">

                <i class="fa fa-right-to-bracket me-2"></i>
                Login to Student Portal

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
