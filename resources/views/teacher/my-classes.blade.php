@extends('layouts.app')

@section('content')

<div class="container-fluid">
    <div class="row">

        @include('layouts.teacher-sidebar')

        <div class="col-md-10 col-lg-10 p-4">

            <div class="page-header mb-4">
                <h2 class="mb-1">My Classes</h2>
                <p class="text-muted mb-0">
                    View assigned classes and student details.
                </p>
            </div>

            <div class="row g-4 mb-4">

                <div class="col-md-3">
                    <div class="dashboard-card">
                        <h6>Total Classes</h6>
                        <h2>{{ $classes->count() }}</h2>
                    </div>
                </div>

                <div class="col-md-3">
                    <div class="dashboard-card">
                        <h6>Active Classes</h6>
                        <h2>{{ $classes->where('status', 1)->count() }}</h2>
                    </div>
                </div>

                <div class="col-md-3">
                    <div class="dashboard-card">
                        <h6>Inactive Classes</h6>
                        <h2>{{ $classes->where('status', 0)->count() }}</h2>
                    </div>
                </div>

                <div class="col-md-3">
                    <div class="dashboard-card">
                        <h6>Academic Year</h6>
                        <h2>{{ date('Y') }}</h2>
                    </div>
                </div>

            </div>

            <div class="card shadow border-0">
                <div class="card-body">

                    <table class="table table-bordered table-hover align-middle">
                        <thead class="table-light">
                            <tr>
                                <th>Sl. No</th>
                                <th>Class</th>
                                <th>Section</th>
                                <th>Class STEM Engineer</th>
                                <th>Academic Year</th>
                                <th>Content</th>
                                <th>Session</th>
                                <th>Status</th>
                            </tr>
                        </thead>

                        <tbody>
                            @forelse($classes as $index => $class)
                                <tr>
                                    <td>{{ $index + 1 }}</td>
                                    <td>{{ $class->class_name }}</td>
                                    <td>{{ $class->section }}</td>
                                    <td>{{ $class->class_teacher }}</td>
                                    <td>{{ $class->academic_year }}</td>
                                    <td>{{ $class->content->content_title ?? 'Not Assigned' }}</td>
                                    <td>
                                        @if(isset($activeSessions[$class->id]))

                                            <form method="POST"
                                                action="{{ route('teacher.class-session.end', $activeSessions[$class->id]->id) }}">
                                                @csrf

                                                <button type="submit"
                                                        class="btn btn-sm btn-danger">
                                                    End Session
                                                </button>
                                            </form>

                                        @else

                                            @if($class->content_id)

                                                <form method="POST"
                                                    action="{{ route('teacher.class-session.start', $class->id) }}">
                                                    @csrf

                                                    <button type="submit"
                                                            class="btn btn-sm btn-success">
                                                        Start Session
                                                    </button>
                                                </form>

                                            @else

                                                <span class="badge bg-secondary">
                                                    No Content
                                                </span>

                                            @endif

                                        @endif
                                    </td>
                                    <td>
                                        @if($class->status == 1)
                                            <span class="badge bg-success">Active</span>
                                        @else
                                            <span class="badge bg-danger">Inactive</span>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="8" class="text-center text-muted">
                                        No classes assigned yet
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>

                    <div class="alert alert-info mt-3 mb-0">
                        Classes created by Admin will appear here for STEM Engineers.
                    </div>

                </div>
            </div>

        </div>

    </div>
</div>

@endsection