@extends('layouts.app')

@section('content')

<div class="container py-5">

    <div class="row justify-content-center">

        <div class="col-md-6">

            <div class="card shadow border-0">

                <div class="card-body p-4">

                    <h3 class="mb-4">
                        Change Password
                    </h3>

                    @if(session('success'))
                        <div class="alert alert-success">
                            {{ session('success') }}
                        </div>
                    @endif

                    @if(session('error'))
                        <div class="alert alert-danger">
                            {{ session('error') }}
                        </div>
                    @endif

                    <form method="POST"
                          action="{{ route('admin.change.password.submit') }}">

                        @csrf

                        <div class="mb-3">

                            <label class="form-label">
                                Current Password
                            </label>

                            <input type="password"
                                   name="current_password"
                                   class="form-control"
                                   required>

                        </div>

                        <div class="mb-3">

                            <label class="form-label">
                                New Password
                            </label>

                            <input type="password"
                                   name="new_password"
                                   class="form-control"
                                   required>

                        </div>

                        <div class="mb-3">

                            <label class="form-label">
                                Confirm New Password
                            </label>

                            <input type="password"
                                   name="new_password_confirmation"
                                   class="form-control"
                                   required>

                        </div>

                        <button type="submit"
                                class="btn btn-primary">

                            Update Password

                        </button>

                    </form>

                </div>

            </div>

        </div>

    </div>

</div>

@endsection
