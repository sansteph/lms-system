@extends('layouts.app')

@section('content')

<div class="container-fluid">
    <div class="row">

        @include('layouts.sidebar')

        <div class="col-md-10 col-lg-10 p-4">
            <div class="page-header mb-4">
                <h2 class="mb-1">Assessment Monitoring</h2>
                <p class="text-muted mb-0">
                    Track assessment starts, submissions, violations, and live status by institute, class, section, or learner.
                </p>
            </div>

            @if(!empty($selectedStudentClass))
                <div class="alert alert-light border d-flex justify-content-between align-items-center flex-wrap gap-2">
                    <div>
                        <span class="fw-semibold">Current assessment scope:</span>
                        Class {{ $selectedStudentClass }}
                        @if(!empty($selectedStudentSection))
                            &middot; Section {{ $selectedStudentSection }}
                        @endif
                    </div>

                    @if(!empty($currentInstitute))
                        <span class="text-muted small">{{ $currentInstitute }}</span>
                    @endif
                </div>
            @endif

            <div class="card shadow-sm border-0 mb-4">
                <div class="card-body lms-report-filter-card">
                    <div class="d-flex align-items-start justify-content-between flex-wrap gap-2 mb-3">
                        <div>
                            <h5 class="mb-1">Monitoring Filters</h5>
                            <p class="text-muted mb-0">Use filters for data segmentation; use table actions only for record-level work.</p>
                        </div>
                        <span class="badge bg-light text-dark border">Assessment Activity</span>
                    </div>

                    <form method="GET" action="{{ route('admin.assessment.monitoring') }}">
                        <div class="lms-report-filter-grid">
                            @if(session('user_role') == 'Admin')
                                <div class="lms-monitor-filter-item">
                                    <label for="assessmentMonitorInstitute" class="form-label">Institute</label>
                                    <select id="assessmentMonitorInstitute" name="institute" class="form-select">
                                        <option value="">All Institutes</option>
                                        @foreach($instituteOptions as $institute)
                                            <option value="{{ $institute }}" @selected(($selectedInstitute ?? '') === $institute)>{{ $institute }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            @endif

                            <div class="lms-monitor-filter-item">
                                <label for="assessmentMonitorClass" class="form-label">Class</label>
                                <select id="assessmentMonitorClass" name="student_class" class="form-select">
                                    <option value="">All Classes</option>
                                    @foreach($classOptions as $className)
                                        <option value="{{ $className }}" @selected(($selectedStudentClass ?? '') === $className)>{{ $className }}</option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="lms-monitor-filter-item">
                                <label for="assessmentMonitorSection" class="form-label">Section</label>
                                <select id="assessmentMonitorSection" name="student_section" class="form-select">
                                    <option value="">All Sections</option>
                                    @foreach($sectionOptions as $sectionName)
                                        <option value="{{ $sectionName }}" @selected(($selectedStudentSection ?? '') === $sectionName)>{{ $sectionName }}</option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="lms-monitor-filter-item">
                                <label for="assessmentMonitorStatus" class="form-label">Status</label>
                                <select id="assessmentMonitorStatus" name="status" class="form-select">
                                    <option value="">All Statuses</option>
                                    <option value="Started" @selected(($statusFilter ?? '') === 'Started')>Started</option>
                                    <option value="Submitted" @selected(($statusFilter ?? '') === 'Submitted')>Submitted</option>
                                    <option value="AutoSubmitted" @selected(($statusFilter ?? '') === 'AutoSubmitted')>Auto Submitted</option>
                                </select>
                            </div>

                            <div class="lms-monitor-filter-item">
                                <label for="assessmentMonitorDate" class="form-label">Started Date</label>
                                <input type="date" id="assessmentMonitorDate" name="date" class="form-control" value="{{ $dateFilter ?? '' }}">
                            </div>

                            <div class="lms-monitor-filter-item">
                                <label for="assessmentMonitorSearch" class="form-label">Search</label>
                                <input type="text" id="assessmentMonitorSearch" name="search" class="form-control" value="{{ $searchFilter ?? '' }}" placeholder="Student / engineer / assessment">
                            </div>

                            <div class="lms-report-action-group lms-monitor-action-group">
                                <button type="submit" class="btn btn-primary lms-report-action-button">Apply Filters</button>
                                <a href="{{ route('admin.assessment.monitoring') }}" class="btn btn-outline-secondary lms-report-action-button">Clear</a>
                            </div>
                        </div>
                    </form>
                </div>
            </div>

            <div class="card shadow border-0">

                <div class="card-body">

                    <div class="d-flex align-items-center justify-content-between flex-wrap gap-2 mb-4">
                        <h3 class="fw-bold mb-0">Assessment Activity Log</h3>
                        @if(method_exists($sessions, 'total'))
                            <span class="badge bg-primary-subtle text-primary border">{{ $sessions->total() }} records</span>
                        @endif
                    </div>

                    <div class="table-responsive lms-table-shell">
                    <table class="table table-bordered table-hover align-middle lms-table-fit lms-monitor-table">

                        <thead class="table-light">
                            <tr>
                                <th>User</th>
                                <th>User Type</th>
                                <th>Institute</th>
                                <th>Class / Section</th>
                                <th>Assessment</th>
                                <th>Started At</th>
                                <th>Submitted At</th>
                                <th>Violations</th>
                                <th>Status</th>
                            </tr>
                        </thead>

                        <tbody>

                            @php
                                $sessionRows = method_exists($sessions, 'getCollection') ? $sessions->getCollection() : collect($sessions);
                                $groupedSessions = $sessionRows
                                    ->sortBy([
                                        fn ($session) => $session->student->institute ?? $session->teacher->institute ?? $session->assessment->institute ?? '',
                                        fn ($session) => trim(($session->student->class ?? $session->assessment->assigned_class ?? '') . ' ' . ($session->student->section ?? '')),
                                    ])
                                    ->groupBy(fn ($session) => $session->student->institute ?? $session->teacher->institute ?? $session->assessment->institute ?? 'Unassigned Institute');
                            @endphp

                            @forelse($groupedSessions as $instituteName => $instituteSessions)
                                <tr class="table-primary">
                                    <td colspan="9" class="fw-semibold">
                                        {{ $instituteName }} · {{ $instituteSessions->count() }} session{{ $instituteSessions->count() == 1 ? '' : 's' }}
                                    </td>
                                </tr>

                                @foreach($instituteSessions->groupBy(fn ($session) => trim(($session->student->class ?? $session->assessment->assigned_class ?? '') . ' ' . ($session->student->section ?? '')) ?: 'Unassigned Class') as $classLabel => $classSessions)
                                    <tr class="table-light">
                                        <td colspan="9" class="fw-semibold ps-4">
                                            {{ $classLabel }} · {{ $classSessions->count() }} session{{ $classSessions->count() == 1 ? '' : 's' }}
                                        </td>
                                    </tr>

                                    @foreach($classSessions as $session)

                                        @php
                                            $userName = 'Unknown User';
                                            $institute = $session->assessment->institute ?? 'Unassigned Institute';

                                            if ($session->user_type == 'Student') {
                                                $userName = $session->student->name ?? 'Student Deleted';
                                                $institute = $session->student->institute
                                                    ?? $session->assessment->institute
                                                    ?? 'Unassigned Institute';
                                            } elseif ($session->user_type == 'Teacher') {
                                                $userName = $session->teacher->name ?? 'STEM Engineer Deleted';
                                                $institute = $session->teacher->institute
                                                    ?? $session->assessment->institute
                                                    ?? 'Unassigned Institute';
                                            }

                                            $classSection = trim(($session->student->class ?? '') . ' ' . ($session->student->section ?? ''));

                                            if ($classSection === '') {
                                                $classSection = trim((string) ($session->assessment->assigned_class ?? ''));
                                            }

                                            if ($classSection === '') {
                                                $classSection = 'Unassigned Class';
                                            }
                                        @endphp

                                        <tr>
                                            <td>{{ $userName }}</td>
                                            <td>{{ $session->user_type }}</td>
                                            <td>{{ $institute }}</td>
                                            <td>{{ $classSection }}</td>
                                            <td>{{ $session->assessment->assessment_title ?? 'Assessment Deleted' }}</td>
                                            <td>{{ $session->started_at ? $session->started_at->format('d M Y h:i A') : 'Not Started' }}</td>
                                            <td>{{ $session->submitted_at ? $session->submitted_at->format('d M Y h:i A') : 'Not Submitted' }}</td>

                                            <td>
                                                @if($session->violation_count >= 3)
                                                    <span class="badge bg-danger">
                                                        {{ $session->violation_count }}
                                                    </span>
                                                @elseif($session->violation_count > 0)
                                                    <span class="badge bg-warning text-dark">
                                                        {{ $session->violation_count }}
                                                    </span>
                                                @else
                                                    <span class="badge bg-success">
                                                        0
                                                    </span>
                                                @endif
                                            </td>

                                            <td>
                                                @if($session->status == 'Started')
                                                    <span class="badge bg-warning text-dark">Started</span>
                                                @elseif($session->status == 'Submitted')
                                                    <span class="badge bg-success">Submitted</span>
                                                @elseif($session->status == 'AutoSubmitted')
                                                    <span class="badge bg-danger">Auto Submitted</span>
                                                @else
                                                    <span class="badge bg-secondary">{{ $session->status }}</span>
                                                @endif
                                            </td>
                                        </tr>

                                    @endforeach
                                @endforeach

                            @empty

                                <tr>
                                    <td colspan="9" class="text-center text-muted">
                                        No assessment activity found.
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
        </div> 
    </div>
</div>

@endsection
