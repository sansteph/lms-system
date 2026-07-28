@extends('layouts.app')

@section('content')

<div class="blogs-login-page">
    <div class="blogs-login-art">
        <span class="blogs-login-pill">InnovatEdge Community</span>
        <h1>Ideas, wins and stories from the InnovatEdge community.</h1>
        <p>
            Sign in once and the LMS will identify whether you are an Admin,
            STEM Engineer or Student before opening the shared Blogs space.
        </p>
        <div class="blogs-login-roles">
            <span><i class="fa fa-user-shield"></i> Admin</span>
            <span><i class="fa fa-chalkboard-teacher"></i> STEM Engineer</span>
            <span><i class="fa fa-user-graduate"></i> Student</span>
        </div>
    </div>

    <div class="blogs-login-card">
        <div class="blogs-login-icon">
            <i class="fa fa-pen-nib"></i>
        </div>

        <h2>Enter Blogs</h2>
        <p>One login for Admins, STEM Engineers and Students.</p>

        @if(session('error'))
            <div class="alert alert-danger border-0 shadow-sm">
                {{ session('error') }}
            </div>
        @endif

        @if($errors->any())
            <div class="alert alert-danger border-0 shadow-sm">
                Please enter your login details correctly.
            </div>
        @endif

        <form method="POST" action="{{ route('blogs.login.submit') }}">
            @csrf

            <div class="mb-4">
                <label class="login-label">Login ID</label>
                <div class="input-group modern-input">
                    <span class="input-group-text">
                        <i class="fa fa-id-badge"></i>
                    </span>
                    <input type="text"
                           name="login"
                           class="form-control"
                           value="{{ old('login') }}"
                           placeholder="Email, user ID, or student ID"
                           required>
                </div>
            </div>

            <div class="mb-4">
                <label class="login-label">Password</label>
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

            <button type="submit" class="btn login-btn w-100">
                <i class="fa fa-right-to-bracket me-2"></i>
                Enter Blogs
            </button>
        </form>

        <div class="back-home">
            <a href="{{ route('home') }}">
                <i class="fa fa-arrow-left"></i>
                Back to Website
            </a>
        </div>
    </div>
</div>

@endsection
