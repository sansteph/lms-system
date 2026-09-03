@extends('layouts.app')

@section('content')

<div class="container-fluid">
    <div class="row">
        @include('layouts.sidebar')

        <div class="col-md-10 col-lg-10 p-4">
            <div class="page-header mb-4">
                <h2 class="mb-1">Learning Content Monitoring</h2>
                <p class="text-muted mb-0">
                    Track how long STEM Engineers and students access learning content and secure previews.
                </p>
            </div>

            @include('partials.section-navigator', [
                'sectionPager' => $sectionPager,
                'sectionDescription' => 'Learning content access is shown one institute at a time for faster monitoring.'
            ])

            <form method="GET" class="mb-4">
                <input type="hidden" name="section_page" value="{{ request('section_page', 1) }}">

                <div class="row g-3 align-items-end">
                    <div class="col-md-3">
                        <label class="form-label fw-semibold">Access Date</label>
                        <input type="date"
                               name="date"
                               value="{{ request('date') }}"
                               class="form-control">
                    </div>

                    <div class="col-md-3">
                        <label class="form-label fw-semibold">Viewer Type</label>
                        <select name="viewer_type" class="form-select">
                            <option value="">All Viewers</option>
                            <option value="Teacher" @selected($viewerType === 'Teacher')>STEM Engineers</option>
                            <option value="Student" @selected($viewerType === 'Student')>Students</option>
                        </select>
                    </div>

                    <div class="col-md-2">
                        <button type="submit" class="btn btn-primary w-100">
                            Apply Filter
                        </button>
                    </div>

                    <div class="col-md-2">
                        <a href="{{ route('admin.activity.monitoring', ['section_page' => request('section_page', 1)]) }}"
                           class="btn btn-outline-secondary w-100">
                            Reset
                        </a>
                    </div>
                </div>
            </form>

            <div class="row g-4 mb-4">
                <div class="col-md-3">
                    <div class="dashboard-card">
                        <h6>Total Content Accesses</h6>
                        <h2>{{ $totalAccesses }}</h2>
                    </div>
                </div>

                <div class="col-md-3">
                    <div class="dashboard-card">
                        <h6>STEM Engineer Accesses</h6>
                        <h2>{{ $teacherAccesses }}</h2>
                    </div>
                </div>

                <div class="col-md-3">
                    <div class="dashboard-card">
                        <h6>Student Accesses</h6>
                        <h2>{{ $studentAccesses }}</h2>
                    </div>
                </div>

                <div class="col-md-3">
                    <div class="dashboard-card">
                        <h6>Total Content Time</h6>
                        <h2>{{ gmdate('H:i:s', $totalDurationSeconds ?? 0) }}</h2>
                    </div>
                </div>
            </div>

            <div class="row g-4 mb-4">
                <div class="col-12">
                    <div class="card shadow border-0 h-100">
                        <div class="card-body">
                            <h5 class="mb-4">Top Content Viewers</h5>

                            <div class="table-responsive">
                                <table class="table table-bordered table-hover align-middle">
                                    <thead>
                                        <tr>
                                            <th>User</th>
                                            <th>Role</th>
                                            <th>Accesses</th>
                                            <th>Time</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @forelse($userDurations as $viewer)
                                            <tr>
                                                <td>
                                                    <strong>{{ $viewer->display_name }}</strong>
                                                    @if($viewer->class_label)
                                                        <div class="text-muted small">{{ $viewer->class_label }}</div>
                                                    @endif
                                                </td>
                                                <td>{{ $viewer->user_type == 'Teacher' ? 'STEM Engineer' : 'Student' }}</td>
                                                <td>{{ $viewer->total_accesses }}</td>
                                                <td>{{ gmdate('H:i:s', $viewer->total_duration ?? 0) }}</td>
                                            </tr>
                                        @empty
                                            <tr>
                                                <td colspan="4" class="text-center text-muted">
                                                    No learning content access recorded for this institute.
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

            <div class="card shadow border-0">
                <div class="card-body">
                    <h5 class="mb-4">Learning Content Access Log</h5>

                    <div class="table-responsive">
                        <table class="table table-bordered table-hover align-middle">
                            <thead>
                                <tr>
                                    <th>User</th>
                                    <th>Role</th>
                                    <th>Class</th>
                                    <th>Access Type</th>
                                    <th>Opened At</th>
                                    <th>Closed / Changed At</th>
                                    <th>Time Spent</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($contentLogs as $log)
                                    @php
                                        $person = $log->user_type == 'Teacher' ? $log->teacher : $log->student;
                                        $classLabel = $log->user_type == 'Student'
                                            ? trim(($person->class ?? '') . ' ' . ($person->section ?? ''))
                                            : 'N/A';
                                    @endphp

                                    <tr>
                                        <td>{{ $person->name ?? ($log->user_type == 'Teacher' ? 'STEM Engineer Deleted' : 'Student Deleted') }}</td>
                                        <td>{{ $log->user_type == 'Teacher' ? 'STEM Engineer' : 'Student' }}</td>
                                        <td>{{ $classLabel ?: 'N/A' }}</td>
                                        <td>
                                            @if($log->route_name == 'content.preview')
                                                Secure Preview
                                            @else
                                                Learning Content Page
                                            @endif
                                        </td>
                                        <td>{{ \Carbon\Carbon::parse($log->started_at)->format('d M Y h:i A') }}</td>
                                        <td>
                                            @if($log->ended_at)
                                                {{ \Carbon\Carbon::parse($log->ended_at)->format('d M Y h:i A') }}
                                            @elseif($log->activity_status === 'Disconnected')
                                                <span class="badge bg-secondary">Disconnected</span>
                                            @else
                                                <span class="badge bg-success">Currently Active</span>
                                            @endif
                                        </td>
                                        <td>{{ gmdate('H:i:s', $log->duration_seconds ?? 0) }}</td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="7" class="text-center text-muted">
                                            No learning content access recorded for this institute.
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    @if($contentLogs->hasPages())
                        <div class="content-monitor-pagination mt-4">
                            <div class="content-monitor-pagination-meta">
                                Showing {{ $contentLogs->firstItem() }} to {{ $contentLogs->lastItem() }} of {{ $contentLogs->total() }} access records
                            </div>

                            <div class="content-monitor-pagination-actions">
                                @if($contentLogs->onFirstPage())
                                    <button type="button" class="btn btn-outline-secondary" disabled>Previous</button>
                                @else
                                    <a href="{{ $contentLogs->previousPageUrl() }}" class="btn btn-outline-primary">Previous</a>
                                @endif

                                <span class="content-monitor-page-pill">
                                    Page {{ $contentLogs->currentPage() }} of {{ $contentLogs->lastPage() }}
                                </span>

                                @if($contentLogs->hasMorePages())
                                    <a href="{{ $contentLogs->nextPageUrl() }}" class="btn btn-primary">Next</a>
                                @else
                                    <button type="button" class="btn btn-outline-secondary" disabled>Next</button>
                                @endif
                            </div>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>

<style>
    .content-monitor-pagination {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 16px;
        flex-wrap: wrap;
        padding: 16px 18px;
        border: 1px solid #e5edf7;
        border-radius: 14px;
        background: #f8fbff;
    }

    .content-monitor-pagination-meta {
        color: #64748b;
        font-weight: 700;
    }

    .content-monitor-pagination-actions {
        display: flex;
        align-items: center;
        gap: 10px;
        flex-wrap: wrap;
    }

    .content-monitor-page-pill {
        display: inline-flex;
        align-items: center;
        min-height: 40px;
        padding: 0 14px;
        border-radius: 999px;
        background: #eef5ff;
        color: #0f3b7a;
        font-weight: 800;
        white-space: nowrap;
    }

</style>

@endsection
