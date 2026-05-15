@extends('layouts.app')

@section('content')

<div class="portal-page">

    <div class="portal-card">

        <div class="portal-icon">
            <i class="fa fa-graduation-cap"></i>
        </div>

        <h1>TinkEdge LMS</h1>

        <p class="portal-subtitle">
            Learning Management Portal
        </p>

        <div class="portal-notice">
            <i class="fa fa-shield-alt"></i>
            <span>
                Select your role to continue to the LMS portal.
            </span>
        </div>

        <div class="portal-divider">
            <span>ACCESS PORTAL</span>
        </div>

        <div class="portal-role-grid">

            <a href="{{ route('admin.login') }}" class="portal-role">
                <i class="fa fa-user-shield"></i>
                Admin
            </a>

            <a href="{{ route('teacher.login') }}" class="portal-role">
                <i class="fa fa-chalkboard-teacher"></i>
                Teacher
            </a>

            <a href="{{ route('student.login') }}" class="portal-role">
                <i class="fa fa-user-graduate"></i>
                Student
            </a>

        </div>

        <div class="mt-4">
            <a href="{{ route('certificate.verify') }}" class="btn btn-outline-primary">
                <i class="fa fa-certificate me-2"></i>
                Verify Certificate
            </a>
        </div>

        <p class="portal-footer-text">
            Smart learning, assessments, badges, and performance tracking.
        </p>

    </div>

</div>

@endsection