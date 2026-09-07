@extends('layouts.app')

@section('content')
<div class="portal-page">
    <div class="login-card">
        <div class="login-icon student-icon"><i class="fa fa-envelope"></i></div>
        <h2 class="login-title">Verify Student Login</h2>
        <p class="login-subtitle">A verification code was sent to {{ substr($student->email, 0, 1) . str_repeat('*', max(1, strlen(strstr($student->email, '@', true)) - 2)) . substr(strstr($student->email, '@', true), -1) . strstr($student->email, '@') }}.</p>

        @if($errors->any())
            <div class="alert alert-danger border-0 shadow-sm">{{ $errors->first() }}</div>
        @endif

        <form method="POST" action="{{ route('student.mfa.verify') }}">
            @csrf
            <label class="login-label" for="code">Verification code</label>
            <input id="code" name="code" type="text" inputmode="numeric" maxlength="6" pattern="[0-9]{6}" class="form-control" placeholder="Enter the 6-digit code" required autofocus>
            <button type="submit" class="btn login-btn student-btn w-100 mt-3">
                <i class="fa fa-check me-2"></i>Verify and continue
            </button>
        </form>

        <div class="back-home mt-3"><a href="{{ route('student.login') }}"><i class="fa fa-arrow-left"></i> Back to login</a></div>
    </div>
</div>
@endsection
