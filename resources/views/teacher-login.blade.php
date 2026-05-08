@extends('layouts.app')

@section('content')

<div class="d-flex justify-content-center align-items-center" style="height: 80vh;">
    <div class="card p-4 shadow border-0" style="width: 380px;">

        <h3 class="text-center mb-3">Teacher Login</h3>

        @if(session('error'))
            <div class="alert alert-danger">
                {{ session('error') }}
            </div>
        @endif

        <form method="POST" action="{{ route('teacher.login.submit') }}">
            @csrf

            <div class="mb-3">
                <label class="form-label">Email Address</label>

                <input type="email"
                       name="email"
                       class="form-control"
                       placeholder="Enter teacher email"
                       required>
            </div>

            <div class="mb-3">
                <label class="form-label">Password</label>

                <input type="password"
                       name="password"
                       class="form-control"
                       placeholder="Enter password"
                       required>
            </div>

            <button type="submit"
                    class="btn btn-primary w-100">
                Login
            </button>

        </form>

    </div>
</div>

@endsection