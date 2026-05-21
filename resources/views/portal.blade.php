@extends('layouts.app')

@section('content')

<div class="portal-page">

    <div class="portal-card">

        <div class="portal-icon">

            <i class="fa fa-lock"></i>

        </div>

        <h1>

            Authorized Learning Portal

        </h1>

        <p class="portal-subtitle">

            Login using credentials provided by your institute administrator.

        </p>

        <div class="portal-notice">

            <i class="fa fa-user-check"></i>

            <span>

                Access restricted to approved students and teachers.

            </span>

        </div>

        <div class="portal-divider">

            <span>

                LOGIN ACCESS

            </span>

        </div>

        <div class="portal-role-grid">

            <a href="{{ route('teacher.login') }}"
               class="portal-role">

                <i class="fa fa-chalkboard-teacher"></i>

                Teacher Login

            </a>

            <a href="{{ route('student.login') }}"
               class="portal-role">

                <i class="fa fa-user-graduate"></i>

                Student Login

            </a>

        </div>

        <div class="mt-4">

            <a href="{{ route('home') }}"
               class="btn btn-outline-secondary">

                <i class="fa fa-arrow-left me-2"></i>

                Back to Main Website

            </a>

        </div>

        <p class="portal-footer-text">

            Secure AI powered learning and assessment portal.

        </p>

    </div>

</div>

@endsection