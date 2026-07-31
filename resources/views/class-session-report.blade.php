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

            @include('partials.section-navigator', ['sectionPager' => $sectionPager ?? null])

            @if($isDailyReport)
                <div class="alert alert-light border d-flex justify-content-between align-items-center flex-wrap gap-2">
                    <div>
                        <span class="fw-semibold">Showing sessions for:</span>
                        {{ \Carbon\Carbon::parse(request('report_date', now()->toDateString()))->format('d M Y') }}
                    </div>
                    <span class="text-muted small">Only sessions from this date are included.</span>
                </div>
            @elseif(request('from_date') || request('to_date'))
                <div class="alert alert-light border d-flex justify-content-between align-items-center flex-wrap gap-2">
                    <div>
                        <span class="fw-semibold">Showing sessions from:</span>
                        {{ request('from_date') ? \Carbon\Carbon::parse(request('from_date'))->format('d M Y') : 'Start' }}
                        -
                        {{ request('to_date') ? \Carbon\Carbon::parse(request('to_date'))->format('d M Y') : 'Today' }}
                    </div>
                    <span class="text-muted small">Only sessions inside this range are included.</span>
                </div>
            @endif

            <div class="card shadow border-0 mb-4">
                <div class="card-body">
                    <form method="GET" action="{{ $reportRoute }}" class="row g-3 align-items-end">
                        <input type="hidden" name="section_page" value="{{ request('section_page', 1) }}">
                        @if($isDailyReport)
                            <div class="col-md-3">
                                <label class="form-label">Report Date</label>
                                <input type="date" name="report_date" id="dailyReportDate" class="form-control" value="{{ request('report_date', now()->toDateString()) }}">
                            </div>
                        @else
                            <div class="col-md-3">
                                <label class="form-label">From Date</label>
                                <input type="date" name="from_date" class="form-control" value="{{ request('from_date') }}">
                            </div>

                            <div class="col-md-3">
                                <label class="form-label">To Date</label>
                                <input type="date" name="to_date" class="form-control" value="{{ request('to_date') }}">
                            </div>
                        @endif

                        <div class="col-md-3 d-flex gap-2">
                            <button type="submit" class="btn btn-primary">Show Sessions</button>
                            <a href="{{ $reportRoute }}" class="btn btn-outline-secondary">Clear</a>
                        </div>
                    </form>

                    <form method="POST" action="{{ $downloadRoute }}" class="mt-3">
                        @csrf
                        <input type="hidden" name="section_page" value="{{ request('section_page', 1) }}">
                        @if($isDailyReport)
                            <input type="hidden" name="report_date" value="{{ request('report_date', now()->toDateString()) }}">
                        @else
                            <input type="hidden" name="from_date" value="{{ request('from_date') }}">
                            <input type="hidden" name="to_date" value="{{ request('to_date') }}">
                        @endif
                        <button type="submit" class="btn btn-outline-primary">Generate Report PDF</button>
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
                                $sessionRows = method_exists($sessions, 'getCollection') ? $sessions->getCollection() : collect($sessions);
                                $groupedSessions = $sessionRows
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

                @if(method_exists($sessions, 'links'))
                    <div class="px-3 pb-3">
                        {{ $sessions->links('pagination::bootstrap-5') }}
                    </div>
                @endif
            </div>

        </div>

    </div>
</div>

@endsection
