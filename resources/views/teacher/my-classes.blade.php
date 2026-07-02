@extends('layouts.app')

@section('content')

<div class="container-fluid">
    <div class="row">
    @include('layouts.teacher-sidebar')

    <div class="col-md-10 col-lg-10 p-4">

        <div class="page-header mb-4">
            <h2 class="mb-1">Today's Scheduled Classes</h2>
            <p class="text-muted mb-0">
                View today's timetable, start sessions, and track class delivery.
            </p>
        </div>

        <div class="row g-4 mb-4">

            <div class="col-md-3">
                <div class="dashboard-card">
                    <h6>Today's Sessions</h6>
                    <h2>{{ $scheduledClasses->count() }}</h2>
                </div>
            </div>

            <div class="col-md-3">
                <div class="dashboard-card">
                    <h6>Scheduled Today</h6>
                    <h2>{{ $scheduledClasses->where('status', 'Scheduled')->count() }}</h2>
                </div>
            </div>

            <div class="col-md-3">
                <div class="dashboard-card">
                    <h6>Completed Sessions</h6>
                    <h2>{{ $scheduledClasses->where('status', 'Completed')->count() }}</h2>
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

                            <th>Topic</th>

                            <th>Date</th>

                            <th>Time</th>

                            <th>Session</th>

                            <th>Status</th>

                        </tr>

                    </thead>

                    <tbody>

                        @forelse($scheduledClasses as $index => $schedule)

                            <tr>

                                <td>
                                    {{ $index + 1 }}
                                </td>

                                <td>
                                    {{ $schedule->schoolClass->class_name }}
                                    -
                                    {{ $schedule->schoolClass->section }}
                                </td>

                                <td>

                                    @if($schedule->content)

                                        {{ $schedule->content->content_title }}

                                        @if($schedule->content->lesson_order)

                                            <br>

                                            <small class="text-muted">

                                                Lesson {{ $schedule->content->lesson_order }}

                                            </small>

                                        @endif

                                    @else

                                        <span class="text-muted">

                                            No Content Assigned

                                        </span>

                                    @endif

                                </td>

                                <td>

                                    {{ \Carbon\Carbon::parse($schedule->session_date)->format('d-m-Y') }}

                                </td>

                                <td>

                                    {{ \Carbon\Carbon::parse($schedule->from_time)->format('h:i A') }}

                                    -

                                    {{ \Carbon\Carbon::parse($schedule->to_time)->format('h:i A') }}

                                </td>

                               <td>

                                    @if(isset($activeSessions[$schedule->id]))

                                        <form method="POST"
                                            action="{{ route('teacher.class-session.end', $activeSessions[$schedule->id]->id) }}">

                                            @csrf

                                            <button type="submit"
                                                    class="btn btn-sm btn-danger">

                                                End Session

                                            </button>

                                        </form>

                                    @elseif($schedule->status == 'Completed' && $schedule->content && !$schedule->content->student_file_path)

                                        <span class="badge bg-warning text-dark">
                                            Student Doc Missing
                                        </span>

                                    @elseif($schedule->status == 'Completed' && $schedule->content && !$schedule->content->is_released)

                                        <form method="POST"
                                            action="{{ route('teacher.complete-topic', $schedule->content_id) }}">

                                            @csrf

                                            <button type="submit"
                                                    class="btn btn-sm btn-primary">

                                                Mark Topic Complete

                                            </button>

                                        </form>

                                    @elseif($schedule->content && $schedule->content->is_released)

                                        <span class="badge bg-success">
                                            Topic Released
                                        </span>

                                    @elseif($schedule->status == 'Scheduled' && $schedule->content_id)

                                        <form method="POST"
                                            action="{{ route('teacher.class-session.start', $schedule->id) }}">
                                            @csrf

                                            <button type="submit" class="btn btn-sm btn-success">
                                                Start Session
                                            </button>
                                        </form>

                                    @else

                                        <span class="badge bg-secondary">
                                            No Content
                                        </span>

                                    @endif

                                </td>

                                <td>

                                    @if($schedule->status == 'Scheduled')

                                        <span class="badge bg-primary">

                                            Scheduled

                                        </span>

                                    @elseif($schedule->status == 'Started')

                                        <span class="badge bg-warning text-dark">

                                            Live

                                        </span>

                                    @elseif($schedule->status == 'Completed')

                                        <span class="badge bg-success">

                                            Completed

                                        </span>

                                    @elseif($schedule->status == 'Cancelled')

                                        <span class="badge bg-danger">

                                            Cancelled

                                        </span>

                                    @else

                                        <span class="badge bg-secondary">

                                            {{ $schedule->status }}

                                        </span>

                                    @endif

                                </td>

                            </tr>

                        @empty

                            <tr>

                                <td colspan="7"
                                    class="text-center text-muted">

                                    No classes scheduled for today

                                </td>

                            </tr>

                        @endforelse

                    </tbody>

                </table>

                <div class="alert alert-info mt-3 mb-0">

                    Sessions displayed here are based on today's timetable schedule.
                    STEM Engineers should start and complete sessions from this page.

                </div>

            </div>

        </div>

    </div>

</div>

</div>

@endsection
