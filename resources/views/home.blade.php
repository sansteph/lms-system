@extends('layouts.app')

@section('content')

<div class="container text-center mt-5">
    <h1>Smart LMS System</h1>
    <p>Manage students, teachers and assessments</p>

    <div class="row mt-4">
        <div class="col-md-4">
            <a href="{{ route('admin.login') }}" class="btn btn-primary">Admin</a>
        </div>
        <div class="col-md-4">
            <a href="{{ route('teacher.login') }}" class="btn btn-primary">Teacher</a>
        </div>
        <div class="col-md-4">
            <a href="{{ route('student.login') }}" class="btn btn-primary">Student</a>
        </div>
    </div>
</div>

@endsection