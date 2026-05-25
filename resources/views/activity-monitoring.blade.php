@extends('layouts.app')

@section('content')

<div class="container-fluid">

    <div class="row">

        @include('layouts.sidebar')

        <div class="col-md-10 col-lg-10 p-4">

            <div class="page-header mb-4">

                <h2 class="mb-1">
                    Activity Monitoring
                </h2>

                <p class="text-muted mb-0">
                    Monitor teacher and student sessions, page visits, and activity logs.
                </p>

            </div>

            <div class="row g-4 mb-4">

                <div class="col-md-3">
                    <div class="dashboard-card">
                        <h6>Active Teachers</h6>
                        <h2>{{ $activeTeachers }}</h2>
                    </div>
                </div>

                <div class="col-md-3">
                    <div class="dashboard-card">
                        <h6>Active Students</h6>
                        <h2>{{ $activeStudents }}</h2>
                    </div>
                </div>

                <div class="col-md-3">
                    <div class="dashboard-card">
                        <h6>Online Users</h6>
                        <h2>{{ $onlineUsers }}</h2>
                    </div>
                </div>

                <div class="col-md-3">
                    <div class="dashboard-card">
                        <h6>Avg. Session Time</h6>
                        <h2>{{ gmdate('H:i:s', $averageSessionDuration ?? 0) }}</h2>
                    </div>
                </div>

            </div>

            <div class="alert alert-info mb-4">
                Most visited section:
                <strong>{{ $mostVisitedSection->section_name ?? 'No data yet' }}</strong>
            </div>

            <form method="GET" class="mb-4">

                <div class="row g-3 align-items-end">

                    <div class="col-md-3">

                        <label class="form-label fw-semibold">
                            User Type
                        </label>

                        <select name="user_type" class="form-control">

                            <option value="">All Users</option>

                            <option value="Teacher"
                                {{ request('user_type') == 'Teacher' ? 'selected' : '' }}>
                                Teachers
                            </option>

                            <option value="Student"
                                {{ request('user_type') == 'Student' ? 'selected' : '' }}>
                                Students
                            </option>

                        </select>

                    </div>

                    <div class="col-md-3">

                        <label class="form-label fw-semibold">
                            Activity Date
                        </label>

                        <input type="date"
                            name="date"
                            value="{{ request('date') }}"
                            class="form-control">

                    </div>

                    <div class="col-md-2">

                        <button type="submit"
                                class="btn btn-primary w-100">

                            Apply Filter

                        </button>

                    </div>

                    <div class="col-md-2">

                        <a href="{{ route('admin.activity.monitoring') }}"
                        class="btn btn-outline-secondary w-100">

                            Reset

                        </a>

                    </div>

                </div>

            </form>

            <div class="mb-4">

                <a href="{{ route('admin.export.activity', request()->query()) }}"
                class="btn btn-success">

                    <i class="fa fa-download me-2"></i>
                    Export Activity Report

                </a>

            </div>

            <div class="card shadow border-0 mb-4">

                <div class="card-body">

                    <h5 class="mb-4">
                        Recent User Sessions
                    </h5>

                    <div class="table-responsive">

                        <table class="table table-bordered table-hover align-middle">

                            <thead>
                                <tr>
                                    <th>User Type</th>
                                    <th>User ID</th>
                                    <th>Login Time</th>
                                    <th>Logout Time</th>
                                    <th>Duration</th>
                                    <th>IP Address</th>
                                    <th>Current Section</th>
                                </tr>
                            </thead>

                            <tbody>

                                @forelse($sessions as $session)

                                    <tr>

                                        <td>{{ $session->user_type }}</td>

                                        <td>
                                            @if($session->user_type == 'Teacher')

                                                {{ $session->teacher->name ?? 'Teacher Deleted' }}

                                            @elseif($session->user_type == 'Student')

                                                {{ $session->student->name ?? 'Student Deleted' }}

                                            @endif
                                        </td>

                                        <td>
                                            {{ \Carbon\Carbon::parse($session->login_time)->format('d M Y h:i A') }}
                                        </td>

                                        <td>
                                            @if($session->logout_time)
                                                {{ \Carbon\Carbon::parse($session->logout_time)->format('d M Y h:i A') }}
                                            @else
                                                <span class="badge bg-success">
                                                    Active
                                                </span>
                                            @endif
                                        </td>

                                        <td>
                                            {{ gmdate('H:i:s', $session->total_duration_seconds ?? 0) }}
                                        </td>

                                        <td>{{ $session->ip_address }}</td>

                                        <td>
                                            @php
                                                $currentActivity = \App\Models\UserActivityLog::where(
                                                    'user_session_id',
                                                    $session->id
                                                )->latest()->first();
                                            @endphp

                                            {{ $currentActivity->section_name ?? 'No Activity' }}
                                        </td>

                                    </tr>

                                @empty

                                    <tr>
                                        <td colspan="7" class="text-center text-muted">
                                            No session data available.
                                        </td>
                                    </tr>

                                @endforelse

                            </tbody>

                        </table>

                    </div>

                </div>

            </div>

            <div class="card shadow border-0 mb-4">

                <div class="card-body">

                    <h5 class="mb-4">
                        Time Spent Per Section
                    </h5>

                    <div class="table-responsive">

                        <table class="table table-bordered table-hover align-middle">

                            <thead>
                                <tr>
                                    <th>Section</th>
                                    <th>Total Time Spent</th>
                                </tr>
                            </thead>

                            <tbody>

                                @forelse($sectionDurations as $section)

                                    <tr>
                                        <td>{{ $section->section_name }}</td>

                                        <td>
                                            {{ gmdate('H:i:s', $section->total_duration ?? 0) }}
                                        </td>
                                    </tr>

                                @empty

                                    <tr>
                                        <td colspan="2" class="text-center text-muted">
                                            No section duration data available.
                                        </td>
                                    </tr>

                                @endforelse

                            </tbody>

                        </table>

                    </div>

                </div>

            </div>

            <div class="card shadow border-0 mb-4">

                <div class="card-body">

                    <h5 class="mb-4">
                        Section Usage Analytics
                    </h5>

                    <canvas id="sectionUsageChart" height="110"></canvas>

                </div>

            </div>

            <div class="card shadow border-0">

                <div class="card-body">

                    <h5 class="mb-4">
                        Recent Activity Logs
                    </h5>

                    <div class="table-responsive">

                        <table class="table table-bordered table-hover align-middle">

                            <thead>
                                <tr>
                                    <th>User Type</th>
                                    <th>User ID</th>
                                    <th>Section</th>
                                    <th>Route</th>
                                    <th>URL</th>
                                    <th>Visited At</th>
                                    <th>Time Spent</th>
                                </tr>
                            </thead>

                            <tbody>

                                @forelse($activityLogs as $log)

                                    <tr>
                                        <td>{{ $log->user_type }}</td>

                                        <td>
                                            @if($log->user_type == 'Teacher')

                                                {{ $log->teacher->name ?? 'Teacher Deleted' }}

                                            @elseif($log->user_type == 'Student')

                                                {{ $log->student->name ?? 'Student Deleted' }}

                                             @else

                                                {{ $log->user_id }}

                                            @endif
                                        </td>

                                        <td>{{ $log->section_name }}</td>

                                        <td>{{ $log->route_name }}</td>

                                        <td>{{ $log->page_url }}</td>

                                        <td>
                                            {{ \Carbon\Carbon::parse($log->started_at)->format('d M Y h:i A') }}
                                        </td>

                                        <td>
                                            {{ gmdate('H:i:s', $log->duration_seconds ?? 0) }}
                                        </td>
                                    </tr>

                                @empty

                                    <tr>
                                        <td colspan="7" class="text-center text-muted">
                                            No activity logs available.
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

</div>

<script>

document.addEventListener('DOMContentLoaded', function () {

    const sectionUsageChart = document.getElementById('sectionUsageChart');

    new Chart(sectionUsageChart, {

        type: 'bar',

        data: {

            labels: [
                @foreach($sectionDurations as $section)
                    "{{ $section->section_name }}"
                    @if(!$loop->last),@endif
                @endforeach
            ],

            datasets: [{

                label: 'Time Spent (Minutes)',

                data: [
                    @foreach($sectionDurations as $section)
                        {{ round(($section->total_duration ?? 0) / 60, 2) }}
                        @if(!$loop->last),@endif
                    @endforeach
                ],

                backgroundColor: '#2563eb',
                borderRadius: 8

            }]

        },

        options: {

            responsive: true,

            plugins: {
                legend: {
                    display: false
                }
            },

            scales: {
                y: {
                    beginAtZero: true
                }
            }

        }

    });

});

</script>

@endsection