@extends('layouts.app')

@section('content')

<div class="d-flex justify-content-center align-items-center" style="height: 80vh;">

    <div class="card p-4 shadow border-0" style="width: 380px;">

        <h3 class="text-center mb-3">
            Student Login
        </h3>

        @if(session('error'))
            <div class="alert alert-danger">
                {{ session('error') }}
            </div>
        @endif

        <form method="POST" action="{{ route('student.login.submit') }}">

            @csrf

            <div class="mb-3">
                <label class="form-label">Student ID</label>

                <input type="text"
                       name="student_id"
                       class="form-control"
                       placeholder="Enter Student ID"
                       required>
            </div>

            <div class="mb-4">
                <label class="form-label">Password</label>
                <input  type="password" 
                        name="password"
                        class="form-control" 
                        placeholder="Enter Password" 
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