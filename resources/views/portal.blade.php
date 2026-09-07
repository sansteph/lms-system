@extends('layouts.app')

@section('content')

<div class="auth-page portal-auth-page">

    <div class="auth-bg-glow glow-1"></div>
    <div class="auth-bg-glow glow-2"></div>

    <div class="portal-auth-card">

        <div class="portal-top-icon">

            <div class="portal-main-icon">
                <i class="fa fa-lock"></i>
            </div>

        </div>

        <div class="auth-badge">
            SECURE LEARNING ACCESS
        </div>

        <h1 class="portal-title">
            Authorized Learning Portal
        </h1>

        <p class="portal-subtitle">
            Login using credentials provided by your institute administrator.
        </p>

        <div class="portal-notice-box">

            <i class="fa fa-user-check"></i>

            <span>
                Access restricted to approved students, STEM Engineers, and principals.
            </span>

        </div>

        <div class="portal-divider">

            <span>LOGIN ACCESS</span>

        </div>

        <div class="portal-role-grid">

            <a href="{{ route('teacher.login') }}"
               class="portal-role-card teacher-card">

                <div class="portal-role-icon">
                    <i class="fa fa-chalkboard-teacher"></i>
                </div>

                <div>
                    <h5>STEM Engineer Login</h5>
                    <p>Manage classes and assessments</p>
                </div>

            </a>

            <a href="{{ route('student.login') }}"
               class="portal-role-card student-card">

                <div class="portal-role-icon">
                    <i class="fa fa-user-graduate"></i>
                </div>

                <div>
                    <h5>Student Login</h5>
                    <p>Access learning dashboard</p>
                </div>

            </a>

            <a href="{{ route('admin.login', ['portal' => 'principal']) }}"
               class="portal-role-card principal-card">

                <div class="portal-role-icon">
                    <i class="fa fa-user-tie"></i>
                </div>

                <div>
                    <h5>Principal Login</h5>
                    <p>Review institute reports</p>
                </div>

            </a>

        </div>

        <div class="auth-footer-link mt-4">

            <a href="{{ route('home') }}">

                <i class="fa fa-arrow-left"></i>

                Back to Main Website

            </a>

        </div>

    </div>

</div>

@endsection
