@extends('layouts.app')

@section('content')

<div class="container-fluid">
    <div class="row">

        @include('layouts.teacher-sidebar')

        <div class="col-md-10 col-lg-10 p-4">

            <div class="page-header mb-4">
                <h2 class="mb-1">Notifications</h2>
                <p class="text-muted mb-0">
                    View announcements and important LMS updates.
                </p>
            </div>

            <div class="row g-4 mb-4">
                <div class="col-md-3">
                    <div class="dashboard-card">
                        <h6>Total Notifications</h6>
                        <h2>{{ $notifications->count() }}</h2>
                    </div>
                </div>

                <div class="col-md-3">
                    <div class="dashboard-card">
                        <h6>STEM Engineer Notifications</h6>
                        <h2>{{ $notifications->where('target', 'Teachers')->count() }}</h2>
                    </div>
                </div>

                <div class="col-md-3">
                    <div class="dashboard-card">
                        <h6>Class Notifications</h6>
                        <h2>{{ $notifications->where('target', 'Class')->count() }}</h2>
                    </div>
                </div>

                <div class="col-md-3">
                    <div class="dashboard-card">
                        <h6>General Updates</h6>
                        <h2>{{ $notifications->count() }}</h2>
                    </div>
                </div>
            </div>

            <div class="card shadow border-0">
                <div class="card-body">

                    <table class="table table-bordered table-hover align-middle">
                        <thead class="table-light">
                            <tr>
                                <th>Sl. No</th>
                                <th>Title</th>
                                <th>Message</th>
                                <th>Target</th>
                                <th>Date</th>
                            </tr>
                        </thead>

                        <tbody>
                            @forelse($notifications as $index => $notification)
                                <tr>
                                    <td>{{ $index + 1 }}</td>
                                    <td>{{ $notification->title }}</td>
                                    <td>{{ $notification->message }}</td>
                                    <td>
                                        @if($notification->target == 'Teachers')
                                            <span class="badge bg-warning text-dark">STEM Engineers</span>
                                        @elseif($notification->target == 'Students')
                                            <span class="badge bg-primary">Students</span>
                                        @else
                                            <span class="badge bg-info">Class</span>
                                        @endif
                                    </td>
                                    <td>{{ $notification->notification_date }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="text-center text-muted">
                                        No notifications found
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>

                </div>
            </div>

        </div>

    </div>
</div>

@endsection