@extends('layouts.app')

@section('content')

<div class="portal-page student-theme">

    <div class="login-card">

        <div class="login-icon student-icon">
            <i class="fa fa-user-graduate"></i>
        </div>

        <h2 class="login-title">
            Complete Your Profile
        </h2>

        <p class="login-subtitle">
            Please complete your student details before accessing the LMS dashboard.
        </p>

        @if ($errors->any())

            <div class="alert alert-danger border-0 shadow-sm mb-4">

                <ul class="mb-0">

                    @foreach ($errors->all() as $error)

                        <li>{{ $error }}</li>

                    @endforeach

                </ul>

            </div>

        @endif

        <form method="POST"
              action="{{ route('student.basic-details.store') }}">

            @csrf

            <div class="mb-4">

                <label class="login-label">
                    Student Name
                </label>

                <div class="input-group modern-input">

                    <span class="input-group-text">
                        <i class="fa fa-user"></i>
                    </span>

                    <input type="text"
                           name="name"
                           class="form-control"
                           value="{{ old('name', $student->name) }}" readonly
                           required>

                </div>

            </div>

            <div class="mb-4">

                <label class="login-label">
                    Email Address
                </label>

                <div class="input-group modern-input">

                    <span class="input-group-text">
                        <i class="fa fa-envelope"></i>
                    </span>

                    <input type="email"
                           name="email"
                           class="form-control"
                           value="{{ old('email', $student->email) }}"
                           required>

                </div>

            </div>

            <div class="mb-4">

                <label class="login-label">
                    Class
                </label>

                <div class="input-group modern-input">

                    <span class="input-group-text">
                        <i class="fa fa-layer-group"></i>
                    </span>

                    <input type="text"
                           name="class"
                           class="form-control"
                           value="{{ old('class', $student->class) }}" readonly
                           required>

                </div>

            </div>

            <div class="mb-4">

                <label class="login-label">
                    Institute Name
                </label>

                <div class="input-group modern-input">

                    <span class="input-group-text">
                        <i class="fa fa-building-columns"></i>
                    </span>

                    <input type="text"
                           name="institute"
                           class="form-control"
                           value="{{ old('institute', $student->institute) }}" readonly
                           required>

                </div>

            </div>

            <div class="mb-4">

                <label class="login-label">
                    Parent / Guardian Name
                </label>

                <div class="input-group modern-input">

                    <span class="input-group-text">
                        <i class="fa fa-users"></i>
                    </span>

                    <input type="text"
                           name="guardian_name"
                           class="form-control"
                           value="{{ old('guardian_name') }}"
                           required>

                </div>

            </div>

            <div class="mb-4">

                <label class="login-label">
                    Parent / Guardian Contact Number
                </label>

                <div class="input-group modern-input">

                    <span class="input-group-text">
                        <i class="fa fa-phone"></i>
                    </span>

                    <input type="text"
                           name="guardian_contact"
                           class="form-control"
                           value="{{ old('guardian_contact') }}"
                           required>

                </div>

            </div>

            <div class="mb-4">

                <label class="login-label">
                    Robotics Club Member
                </label>

                <select name="is_robotics_club_member"
                        class="form-select auth-select"
                        required>

                    <option value="">
                        Select Option
                    </option>

                    <option value="1">
                        Yes
                    </option>

                    <option value="0">
                        No
                    </option>

                </select>

            </div>

            <button type="submit"
                    class="btn login-btn student-btn w-100">

                <i class="fa fa-check-circle me-2"></i>

                Complete Profile

            </button>

        </form>

    </div>

</div>

@endsection
