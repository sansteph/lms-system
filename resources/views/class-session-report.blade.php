@extends('layouts.app')

@section('content')

<div class="container-fluid">
    <div class="row">

        @include('layouts.sidebar')

        <div class="col-md-10 col-lg-10 p-4">

            <div class="page-header mb-4">
                <h2 class="mb-1">Class Session Report</h2>
                <p class="text-muted mb-0">
                    Track which STEM Engineer handled each class and how long they spent on assigned content.
                </p>
            </div>

            <div class="card shadow border-0 mb-4">
                <div class="card-body">
                    <form method="GET" action="{{ route('admin.class-session.report') }}" class="row g-3 align-items-end">
                        @if(session('user_role') == 'Admin')
                            <div class="col-md-3">
                                <label class="form-label">Institute</label>
                                <select name="institute" class="form-select">
                                    <option value="">All Institutes</option>
                                    @foreach($institutes as $institute)
                                        <option value="{{ $institute->institute_name }}" {{ request('institute') == $institute->institute_name ? 'selected' : '' }}>
                                            {{ $institute->institute_name }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                        @endif

                        <div class="col-md-3">
                            <label class="form-label">From Date</label>
                            <input type="date" name="from_date" class="form-control" value="{{ request('from_date') }}">
                        </div>

                        <div class="col-md-3">
                            <label class="form-label">To Date</label>
                            <input type="date" name="to_date" class="form-control" value="{{ request('to_date') }}">
                        </div>

                        <div class="col-md-3 d-flex gap-2">
                            <button type="submit" class="btn btn-primary">Apply Filter</button>
                            <a href="{{ route('admin.class-session.report') }}" class="btn btn-outline-secondary">Clear</a>
                            <a href="{{ route('admin.class-session.report.export', request()->query()) }}" class="btn btn-success">
                                Export CSV
                            </a>
                        </div>
                    </form>
                </div>
            </div>

            <div class="card shadow border-0">
                <div class="card-body">

                    <table class="table table-bordered table-hover align-middle">

                        <thead class="table-light">
                            <tr>
                                <th>Class</th>
                                <th>Institute</th>
                                <th>Course</th>
                                <th>Planned Content</th>
                                <th>Delivered Content</th>
                                <th>STEM Engineer</th>
                                <th>Date / Time</th>
                                <th>Duration</th>
                                <th>Status</th>
                            </tr>
                        </thead>

                        <tbody>

                            @php
                                $groupedSessions = $sessions
                                    ->sortBy([
                                        fn ($session) => $session->institute ?? $session->schoolClass->institute ?? '',
                                        fn ($session) => trim(($session->class ?? $session->schoolClass->class_name ?? '') . ' ' . ($session->section ?? $session->schoolClass->section ?? '')),
                                        fn ($session) => $session->session_date ?? '',
                                    ])
                                    ->groupBy(fn ($session) => $session->institute ?? $session->schoolClass->institute ?? 'Unassigned Institute');
                            @endphp

                            @forelse($groupedSessions as $instituteName => $instituteSessions)
                                <tr class="table-primary">
                                    <td colspan="9" class="fw-semibold">
                                        {{ $instituteName }} | {{ $instituteSessions->count() }} session{{ $instituteSessions->count() == 1 ? '' : 's' }}
                                    </td>
                                </tr>

                                @foreach($instituteSessions->groupBy(fn ($session) => trim(($session->class ?? $session->schoolClass->class_name ?? '') . ' ' . ($session->section ?? $session->schoolClass->section ?? '')) ?: 'Unassigned Class') as $classLabel => $classSessions)
                                    <tr class="table-light">
                                        <td colspan="9" class="fw-semibold ps-4">
                                            {{ $classLabel }} | {{ $classSessions->count() }} session{{ $classSessions->count() == 1 ? '' : 's' }}
                                        </td>
                                    </tr>

                                    @foreach($classSessions as $session)
                                        <tr>
                                            <td>
                                                {{ $session->class ?? $session->schoolClass->class_name ?? 'N/A' }}
                                                {{ $session->section ?? $session->schoolClass->section ?? '' }}
                                            </td>

                                            <td>
                                                {{ $session->institute ?? $session->schoolClass->institute ?? 'N/A' }}
                                            </td>

                                            <td>
                                                {{ $session->course->course_title ?? 'N/A' }}
                                            </td>

                                            <td>
                                                {{ $session->planned_topic ?? $session->content->content_title ?? 'No Content' }}
                                            </td>

                                            <td>
                                                {{ $session->delivered_topic ?? 'Not recorded' }}
                                            </td>

                                            <td>
                                                {{ $session->stemEngineer->name ?? 'Deleted Engineer' }}
                                            </td>

                                            <td>
                                                {{ $session->session_date ? \Carbon\Carbon::parse($session->session_date)->format('d M Y') : '-' }}
                                                <br>
                                                <small class="text-muted">
                                                    {{ $session->start_time ? \Carbon\Carbon::parse($session->start_time)->format('h:i A') : '-' }}
                                                    @if($session->end_time)
                                                        - {{ \Carbon\Carbon::parse($session->end_time)->format('h:i A') }}
                                                    @endif
                                                </small>
                                            </td>

                                            <td>
                                                {{ gmdate('H:i:s', $session->duration_seconds ?? 0) }}
                                            </td>

                                            <td>
                                                <span class="badge bg-{{ $session->status == 'in_progress' ? 'warning text-dark' : ($session->status == 'cancelled' ? 'danger' : 'success') }}">
                                                    {{ ucwords(str_replace('_', ' ', $session->status)) }}
                                                </span>
                                            </td>
                                        </tr>
                                    @endforeach
                                @endforeach

                            @empty

                                <tr>
                                    <td colspan="9" class="text-center text-muted">
                                        No class sessions found.
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
