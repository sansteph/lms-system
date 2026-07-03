@extends('layouts.app')

@section('content')

<div class="container-fluid">

    <div class="row">

        @include('layouts.sidebar') 

        <div class="col-md-10 col-lg-10 p-4">

            <div class="page-header mb-4">
                <h2 class="mb-1">Activity Monitoring</h2>
                <p class="text-muted mb-0">
                    Monitor institute admins, STEM engineers, students, sessions, and activity logs.
                </p>
            </div>

            <div class="row g-4 mb-4">

                <div class="col-md-3">
                    <div class="dashboard-card">
                        <h6>Institute Admins</h6>
                        <h2>{{ $activeInstituteAdmins }}</h2>
                    </div>
                </div>

                <div class="col-md-3">
                    <div class="dashboard-card">
                        <h6>Active STEM Engineers</h6>
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

            </div>

            <div class="alert alert-info mb-4">
                Most visited section:
                <strong>{{ $mostVisitedSection->section_name ?? 'No data yet' }}</strong>
            </div>

            <form method="GET" class="mb-4">

                <div class="row g-3 align-items-end">

                    <div class="col-md-3">
                        <label class="form-label fw-semibold">Activity Date</label>

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

            {{-- Institute Admin Sessions --}}
            <div class="card shadow border-0 mb-4">

                <div class="card-body">

                    <h5 class="mb-4">Institute Admin Activity</h5>

                    <div class="table-responsive">

                        <table class="table table-bordered table-hover align-middle">

                            <thead>
                                <tr>
                                    <th>Admin Name</th>
                                    <th>Institute</th>
                                    <th>Login Time</th>
                                    <th>Logout Time</th>
                                    <th>Duration</th>
                                    <th>IP Address</th>
                                    <th>Current Section</th>
                                </tr>
                            </thead>

                            <tbody>

                                @forelse($instituteAdminSessions as $session)

                                    @php
                                        $admin = \App\Models\User::find($session->user_id);

                                        $currentActivity = \App\Models\UserActivityLog::where(
                                            'user_session_id',
                                            $session->id
                                        )->latest()->first();
                                    @endphp

                                    <tr>
                                        <td>{{ $admin->name ?? 'Admin Deleted' }}</td>
                                        <td>{{ $admin->institute ?? 'N/A' }}</td>

                                        <td>
                                            {{ \Carbon\Carbon::parse($session->login_time)->format('d M Y h:i A') }}
                                        </td>

                                        <td>
                                            @if($session->logout_time)
                                                {{ \Carbon\Carbon::parse($session->logout_time)->format('d M Y h:i A') }}
                                            @else
                                                <span class="badge bg-success">Active</span>
                                            @endif
                                        </td>

                                        <td>{{ gmdate('H:i:s', $session->total_duration_seconds ?? 0) }}</td>

                                        <td>{{ $session->ip_address }}</td>

                                        <td>{{ $currentActivity->section_name ?? 'No Activity' }}</td>
                                    </tr>

                                @empty

                                    <tr>
                                        <td colspan="7" class="text-center text-muted">
                                            No institute admin session data available.
                                        </td>
                                    </tr>

                                @endforelse

                            </tbody>

                        </table>

                    </div>

                </div>

            </div>

            {{-- Teacher Sessions --}}
            <div class="card shadow border-0 mb-4">

                <div class="card-body">

                    <h5 class="mb-4">STEM Engineer Activity</h5>

                    <div class="table-responsive">

                        <table class="table table-bordered table-hover align-middle">

                            <thead>
                                <tr>
                                    <th>STEM Engineer Name</th>
                                    <th>Institute</th>
                                    <th>Login Time</th>
                                    <th>Logout Time</th>
                                    <th>Duration</th>
                                    <th>IP Address</th>
                                    <th>Current Section</th>
                                </tr>
                            </thead>

                            <tbody>

                                @forelse($teacherSessions as $session)

                                    @php
                                        $currentActivity = \App\Models\UserActivityLog::where(
                                            'user_session_id',
                                            $session->id
                                        )->latest()->first();
                                    @endphp

                                    <tr>
                                        <td>{{ $session->teacher->name ?? 'STEM Engineer Deleted' }}</td>
                                        <td>{{ $session->teacher->institute ?? 'N/A' }}</td>

                                        <td>
                                            {{ \Carbon\Carbon::parse($session->login_time)->format('d M Y h:i A') }}
                                        </td>

                                        <td>
                                            @if($session->logout_time)
                                                {{ \Carbon\Carbon::parse($session->logout_time)->format('d M Y h:i A') }}
                                            @else
                                                <span class="badge bg-success">Active</span>
                                            @endif
                                        </td>

                                        <td>{{ gmdate('H:i:s', $session->total_duration_seconds ?? 0) }}</td>

                                        <td>{{ $session->ip_address }}</td>

                                        <td>{{ $currentActivity->section_name ?? 'No Activity' }}</td>
                                    </tr>

                                @empty

                                    <tr>
                                        <td colspan="7" class="text-center text-muted">
                                            No STEM Engineer session data available.
                                        </td>
                                    </tr>

                                @endforelse

                            </tbody>

                        </table>

                    </div>

                </div>

            </div>

            {{-- Student Sessions --}}
            <div class="card shadow border-0 mb-4">

                <div class="card-body">

                    <h5 class="mb-4">Student Activity</h5>

                    <div class="table-responsive">

                        <table class="table table-bordered table-hover align-middle">

                            <thead>
                                <tr>
                                    <th>Student Name</th>
                                    <th>Institute</th>
                                    <th>Class</th>
                                    <th>Login Time</th>
                                    <th>Logout Time</th>
                                    <th>Duration</th>
                                    <th>Current Section</th>
                                </tr>
                            </thead>

                            <tbody>

                                @forelse($studentSessions as $session)

                                    @php
                                        $currentActivity = \App\Models\UserActivityLog::where(
                                            'user_session_id',
                                            $session->id
                                        )->latest()->first();
                                    @endphp

                                    <tr>
                                        <td>{{ $session->student->name ?? 'Student Deleted' }}</td>
                                        <td>{{ $session->student->institute ?? 'N/A' }}</td>
                                        <td>{{ $session->student->class ?? 'N/A' }}</td>

                                        <td>
                                            {{ \Carbon\Carbon::parse($session->login_time)->format('d M Y h:i A') }}
                                        </td>

                                        <td>
                                            @if($session->logout_time)
                                                {{ \Carbon\Carbon::parse($session->logout_time)->format('d M Y h:i A') }}
                                            @else
                                                <span class="badge bg-success">Active</span>
                                            @endif
                                        </td>

                                        <td>{{ gmdate('H:i:s', $session->total_duration_seconds ?? 0) }}</td>

                                        <td>{{ $currentActivity->section_name ?? 'No Activity' }}</td>
                                    </tr>

                                @empty

                                    <tr>
                                        <td colspan="7" class="text-center text-muted">
                                            No student session data available.
                                        </td>
                                    </tr>

                                @endforelse

                            </tbody>

                        </table>

                    </div>

                </div>

            </div>

            {{-- Time Spent --}}
            <div class="card shadow border-0 mb-4">

                <div class="card-body">

                    <h5 class="mb-4">Time Spent Per Section</h5>

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
                                        <td>{{ gmdate('H:i:s', $section->total_duration ?? 0) }}</td>
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

            {{-- Assessment Activity --}}
            <div class="card shadow border-0 mb-4">

                <div class="card-body">

                    <h5 class="mb-4">
                        Assessment Activity
                    </h5>

                    <div class="table-responsive">

                        <table class="table table-bordered table-hover align-middle">

                            <thead>

                                <tr>

                                    <th>User</th>

                                    <th>User Type</th>

                                    <th>Assessment</th>

                                    <th>Started At</th>

                                    <th>Submitted At</th>

                                    <th>Violations</th>

                                    <th>Status</th>

                                </tr>

                            </thead>

                            <tbody>

                                @forelse($assessmentSessions as $session)

                                    <tr>

                                        <td>

                                            @if($session->user_type == 'Student')

                                                {{ $session->student->name ?? 'Student Deleted' }}

                                            @elseif($session->user_type == 'Teacher')

                                                {{ $session->teacher->name ?? 'STEM Engineer Deleted' }}

                                            @else

                                                {{ $session->user_id }}

                                            @endif

                                        </td>

                                        <td>
                                            {{ $session->user_type }}
                                        </td>

                                        <td>
                                            {{ $session->assessment->assessment_title ?? 'Assessment Deleted' }}
                                        </td>

                                        <td>

                                            @if($session->started_at)

                                                {{ \Carbon\Carbon::parse($session->started_at)->format('d M Y h:i A') }}

                                            @endif

                                        </td>

                                        <td>

                                            @if($session->submitted_at)

                                                {{ \Carbon\Carbon::parse($session->submitted_at)->format('d M Y h:i A') }}

                                            @else

                                                <span class="badge bg-warning">
                                                    In Progress
                                                </span>

                                            @endif

                                        </td>

                                        <td>

                                            @if($session->violation_count > 0)

                                                <span class="badge bg-danger">
                                                    {{ $session->violation_count }}
                                                </span>

                                            @else

                                                <span class="badge bg-success">
                                                    0
                                                </span>

                                            @endif

                                        </td>

                                        <td>

                                            @if($session->status == 'AutoSubmitted')

                                                <span class="badge bg-danger">
                                                    Auto Submitted
                                                </span>

                                            @elseif($session->status == 'Submitted')

                                                <span class="badge bg-success">
                                                    Submitted
                                                </span>

                                            @else

                                                <span class="badge bg-warning">
                                                    In Progress
                                                </span>

                                            @endif

                                        </td>

                                    </tr>

                                @empty

                                    <tr>

                                        <td colspan="7"
                                            class="text-center text-muted">

                                            No assessment activity available.

                                        </td>

                                    </tr>

                                @endforelse

                            </tbody>

                        </table>

                    </div>

                </div>

            </div>

            {{-- Chart --}}
            <div class="card shadow border-0 mb-4">

                <div class="card-body">

                    <h5 class="mb-4">Section Usage Analytics</h5>

                    <canvas id="sectionUsageChart" height="110"></canvas>

                </div>

            </div>

            {{-- Logs --}}
            <div class="card shadow border-0 mb-4">

                <div class="card-body">

                    <h5 class="mb-4">Institute Admin Activity Logs</h5>

                    <div class="table-responsive">

                        <table class="table table-bordered table-hover align-middle">

                            <thead>
                                <tr>
                                    <th>Admin</th>
                                    <th>Section</th>
                                    <th>Route</th>
                                    <th>Visited At</th>
                                    <th>Time Spent</th>
                                </tr>
                            </thead>

                            <tbody>

                                @forelse($instituteAdminLogs as $log)

                                    @php
                                        $admin = \App\Models\User::find($log->user_id);
                                    @endphp

                                    <tr>
                                        <td>{{ $admin->name ?? 'Admin Deleted' }}</td>
                                        <td>{{ $log->section_name }}</td>
                                        <td>{{ $log->route_name }}</td>
                                        <td>{{ \Carbon\Carbon::parse($log->started_at)->format('d M Y h:i A') }}</td>
                                        <td>{{ gmdate('H:i:s', $log->duration_seconds ?? 0) }}</td>
                                    </tr>

                                @empty

                                    <tr>
                                        <td colspan="5" class="text-center text-muted">
                                            No institute admin activity logs available.
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

                    <h5 class="mb-4">STEM Engineer Activity Logs</h5>

                    <div class="table-responsive">

                        <table class="table table-bordered table-hover align-middle">

                            <thead>
                                <tr>
                                    <th>STEM Engineer</th>
                                    <th>Section</th>
                                    <th>Route</th>
                                    <th>Visited At</th>
                                    <th>Time Spent</th>
                                </tr>
                            </thead>

                            <tbody>

                                @forelse($teacherLogs as $log)

                                    <tr>
                                        <td>{{ $log->teacher->name ?? 'STEM Engineer Deleted' }}</td>
                                        <td>{{ $log->section_name }}</td>
                                        <td>{{ $log->route_name }}</td>
                                        <td>{{ \Carbon\Carbon::parse($log->started_at)->format('d M Y h:i A') }}</td>
                                        <td>{{ gmdate('H:i:s', $log->duration_seconds ?? 0) }}</td>
                                    </tr>

                                @empty

                                    <tr>
                                        <td colspan="5" class="text-center text-muted">
                                            No STEM Engineer activity logs available.
                                        </td>
                                    </tr>

                                @endforelse

                            </tbody>

                        </table>

                    </div>

                </div>

            </div>

            <div class="card shadow border-0">

                <div class="card-body">

                    <h5 class="mb-4">Student Activity Logs</h5>

                    <div class="table-responsive">

                        <table class="table table-bordered table-hover align-middle">

                            <thead>
                                <tr>
                                    <th>Student</th>
                                    <th>Section</th>
                                    <th>Route</th>
                                    <th>Visited At</th>
                                    <th>Time Spent</th>
                                </tr>
                            </thead>

                            <tbody>

                                @forelse($studentLogs as $log)

                                    <tr>
                                        <td>{{ $log->student->name ?? 'Student Deleted' }}</td>
                                        <td>{{ $log->section_name }}</td>
                                        <td>{{ $log->route_name }}</td>
                                        <td>{{ \Carbon\Carbon::parse($log->started_at)->format('d M Y h:i A') }}</td>
                                        <td>{{ gmdate('H:i:s', $log->duration_seconds ?? 0) }}</td>
                                    </tr>

                                @empty

                                    <tr>
                                        <td colspan="5" class="text-center text-muted">
                                            No student activity logs available.
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

@php
    $sectionUsageLabels = $sectionDurations->pluck('section_name')->values();
    $sectionUsageData = $sectionDurations
        ->map(fn ($section) => round(($section->total_duration ?? 0) / 60, 2))
        ->values();
@endphp

<div id="sectionUsageChartData"
     data-labels="{{ e($sectionUsageLabels->toJson()) }}"
     data-values="{{ e($sectionUsageData->toJson()) }}">
</div>

<script>

document.addEventListener('DOMContentLoaded', function () {

    const sectionUsageChart = document.getElementById('sectionUsageChart');
    const sectionUsageChartData = document.getElementById('sectionUsageChartData');

    if (sectionUsageChart && sectionUsageChartData) {

        const labels = JSON.parse(sectionUsageChartData.dataset.labels || '[]');
        const values = JSON.parse(sectionUsageChartData.dataset.values || '[]');

        new Chart(sectionUsageChart, {

            type: 'bar',

            data: {

                labels: labels,

                datasets: [{

                    label: 'Time Spent (Minutes)',

                    data: values

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

    }

});

</script>

@endsection
