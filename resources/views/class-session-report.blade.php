@extends('layouts.app')

@section('content')

@php
    $reportType = $reportType ?? request('report_type', 'weekly');
    $isDailyReport = $reportType == 'daily';
    $reportTitle = $isDailyReport ? 'Daily Session Report' : 'Weekly Session Report';
    $reportDescription = $isDailyReport
        ? 'Select one date and generate a focused session execution report.'
        : 'Select a date range and generate a weekly session execution report.';
    $reportRoute = $isDailyReport
        ? route('admin.class-session.report.daily')
        : route('admin.class-session.report.weekly');
    $downloadRoute = $isDailyReport
        ? route('admin.class-session.report.daily.download')
        : route('admin.class-session.report.weekly.download');
@endphp

<div class="container-fluid">
    <div class="row">

        @include('layouts.sidebar')

        <div class="col-md-10 col-lg-10 p-4">

            <div class="page-header mb-4">
                <h2 class="mb-1">{{ $reportTitle }}</h2>
                <p class="text-muted mb-0">
                    {{ $reportDescription }}
                </p>
            </div>

            <div class="card shadow border-0 mb-4">
                <div class="card-body lms-report-filter-card">
                    <div class="d-flex justify-content-between align-items-start flex-wrap gap-3 mb-3">
                        <div>
                            <h5 class="mb-1">Apply filters for easy navigation</h5>
                            <p class="text-muted mb-0">Choose the date or range to load the session report.</p>
                        </div>
                        <span class="badge bg-light text-dark border">Report Filters</span>
                    </div>

                    <form method="GET" action="{{ $reportRoute }}">
                        <div class="lms-report-filter-grid">
                        @if(session('user_role') == 'Admin')
                            <div class="lms-report-action-group">
                                <label class="form-label">Institute</label>
                                <select name="institute" class="form-select">
                                    <option value="">Select institute</option>
                                    @foreach($reportInstituteOptions as $instituteOption)
                                        <option value="{{ $instituteOption }}" @selected($selectedReportInstitute == $instituteOption)>{{ $instituteOption }}</option>
                                    @endforeach
                                </select>
                            </div>
                        @endif

                        @if($isDailyReport)
                            <div class="lms-report-action-group">
                                <label class="form-label">Report Date</label>
                                <input type="date" name="report_date" id="dailyReportDate" class="form-control" value="{{ request('report_date') }}">
                            </div>
                        @else
                            <div class="lms-report-action-group">
                                <label class="form-label">From Date</label>
                                <input type="date" name="from_date" class="form-control" value="{{ request('from_date') }}">
                            </div>

                            <div class="lms-report-action-group">
                                <label class="form-label">To Date</label>
                                <input type="date" name="to_date" class="form-control" value="{{ request('to_date') }}">
                            </div>
                        @endif
                        </div>

                        <div class="lms-report-button-band">
                            <button type="submit" class="btn btn-primary lms-report-action-button">Show Sessions</button>
                            <a href="{{ $reportRoute }}" class="btn btn-outline-secondary lms-report-action-clear">Clear</a>
                        </div>
                    </form>

                    <div class="lms-report-status-row mt-3 text-muted small">
                        <span class="lms-report-status-text">
                            @if($isDailyReport)
                                Showing sessions for:
                                {{ request('report_date') ? \Carbon\Carbon::parse(request('report_date'))->format('d M Y') : 'Select a date' }}
                            @else
                                Showing sessions from:
                                {{ request('from_date') ? \Carbon\Carbon::parse(request('from_date'))->format('d M Y') : 'Start' }}
                                -
                                {{ request('to_date') ? \Carbon\Carbon::parse(request('to_date'))->format('d M Y') : 'Today' }}
                            @endif
                        </span>
                        <form method="POST" action="{{ $downloadRoute }}" class="lms-report-status-action">
                            @csrf
                            @if(session('user_role') == 'Admin')
                                <input type="hidden" name="institute" value="{{ $selectedReportInstitute }}">
                            @endif
                            @if($isDailyReport)
                                <input type="hidden" name="report_date" value="{{ request('report_date') }}">
                            @else
                                <input type="hidden" name="from_date" value="{{ request('from_date') }}">
                                <input type="hidden" name="to_date" value="{{ request('to_date') }}">
                            @endif
                            <button type="submit" class="btn btn-outline-primary btn-sm lms-report-action-generate">Generate Report PDF</button>
                        </form>
                    </div>
                </div>
            </div>

            @if(!$hasFilters)
                @include('partials.filter-placeholder')
            @else
            <div class="card shadow border-0">
                <div class="card-body">

                    <div class="table-responsive lms-table-shell">
                    <table class="table table-bordered table-hover align-middle lms-table-fit">

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
                                $sessionRows = method_exists($sessions, 'getCollection') ? $sessions->getCollection() : collect($sessions);
                                $sortedSessions = $sessionRows
                                    ->sortBy([
                                        fn ($session) => $session->institute ?? $session->schoolClass->institute ?? '',
                                        fn ($session) => trim(($session->class ?? $session->schoolClass->class_name ?? '') . ' ' . ($session->section ?? $session->schoolClass->section ?? '')),
                                        fn ($session) => $session->session_date ?? '',
                                    ])
                                    ->values();
                            @endphp

                            @forelse($sortedSessions as $session)
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

                @if(method_exists($sessions, 'links'))
                    <div class="px-3 pb-3">
                        {{ $sessions->links('pagination::bootstrap-5') }}
                    </div>
                @endif
            </div>
            @endif

        </div>

    </div>
</div>

@endsection
