@extends('layouts.app')

@section('content')

<div class="container-fluid">
    <div class="row">

        @include('layouts.sidebar')

        <div class="col-md-10 col-lg-10 p-4">
            <div class="page-header mb-4">
                <h3 class="fw-bold mb-1">STEM Engineer Achievements</h3>
                <p class="text-muted mb-0">Review and approve achievements submitted by STEM Engineers.</p>
            </div>

            @if(session('success'))
                <div class="alert alert-success">{{ session('success') }}</div>
            @endif

            <div class="card shadow border-0 mb-4">
                <div class="card-body lms-report-filter-card">
                    <div class="d-flex justify-content-between align-items-start flex-wrap gap-3 mb-3">
                        <div>
                            <h5 class="mb-1">Apply filters for easy navigation</h5>
                            <p class="text-muted mb-0">Choose institute or status to load the relevant STEM Engineer approvals.</p>
                        </div>
                        <span class="badge bg-light text-dark border">Approval Filters</span>
                    </div>

                    <form method="GET" action="{{ route('admin.teacher-achievements') }}">
                        <div class="lms-report-filter-grid">
                            @if(session('user_role') == 'Admin')
                                <div class="lms-report-action-group">
                                    <label class="form-label">Institute</label>
                                    <select name="institute" class="form-select">
                                        <option value="">Select institute</option>
                                        @foreach($achievementInstituteOptions as $instituteOption)
                                            <option value="{{ $instituteOption }}" @selected($currentInstitute == $instituteOption)>{{ $instituteOption }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            @endif

                            <div class="lms-report-action-group">
                                <label class="form-label">Status</label>
                                <select name="achievement_status" class="form-select">
                                    <option value="">Any status</option>
                                    <option value="Pending" @selected($selectedAchievementStatus == 'Pending')>Pending</option>
                                    <option value="Approved" @selected($selectedAchievementStatus == 'Approved')>Approved</option>
                                    <option value="Rejected" @selected($selectedAchievementStatus == 'Rejected')>Rejected</option>
                                </select>
                            </div>
                        </div>

                        <div class="lms-report-button-band">
                            <button type="submit" class="btn btn-primary lms-report-action-button">Apply Filters</button>
                            <a href="{{ route('admin.teacher-achievements') }}" class="btn btn-outline-secondary lms-report-action-clear">Clear</a>
                        </div>
                    </form>
                </div>
            </div>

            @if(!$hasFilters)
                @include('partials.filter-placeholder')
            @else
                <div class="card shadow border-0">
                    <div class="card-body">
                        <div class="table-responsive lms-table-shell">
                            <table class="table table-bordered align-middle lms-table-fit">
                                <thead class="table-light">
                                    <tr>
                                        <th>ID</th>
                                        <th>STEM Engineer</th>
                                        <th>Institute</th>
                                        <th>Type</th>
                                        <th>Title</th>
                                        <th>Organizer</th>
                                        <th>Status</th>
                                        <th>Certificate</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @php
                                        $achievementRows = method_exists($achievements, 'getCollection') ? $achievements->getCollection() : collect($achievements);
                                        $sortedAchievements = $achievementRows->sortBy([
                                            fn ($achievement) => $achievement->teacher->institute ?? '',
                                            fn ($achievement) => $achievement->teacher->name ?? '',
                                        ])->values();
                                    @endphp

                                    @forelse($sortedAchievements as $achievement)
                                        <tr>
                                            <td>{{ $achievement->id }}</td>
                                            <td>
                                                <strong>{{ $achievement->teacher->name ?? 'STEM Engineer Deleted' }}</strong>
                                                @if($achievement->teacher)
                                                    <br>
                                                    <small class="text-muted">{{ $achievement->teacher->user_id }}</small>
                                                @endif
                                            </td>
                                            <td>{{ $achievement->teacher->institute ?? 'N/A' }}</td>
                                            <td>{{ $achievement->achievement_type }}</td>
                                            <td>{{ $achievement->title }}</td>
                                            <td>{{ $achievement->organizer ?? 'N/A' }}</td>
                                            <td>
                                                @if($achievement->verification_status == 'Approved')
                                                    <span class="badge bg-success">Approved</span>
                                                @elseif($achievement->verification_status == 'Rejected')
                                                    <span class="badge bg-danger">Rejected</span>
                                                @else
                                                    <span class="badge bg-warning text-dark">Pending</span>
                                                @endif
                                            </td>
                                            <td>
                                                @if($achievement->certificate_file)
                                                    <a href="{{ asset('storage/' . $achievement->certificate_file) }}" target="_blank" class="btn btn-sm btn-outline-primary">View</a>
                                                @else
                                                    <span class="text-muted">No file</span>
                                                @endif
                                            </td>
                                            <td>
                                                <div class="d-flex gap-2 flex-wrap">
                                                    <form action="{{ route('admin.teacher-achievements.approve', $achievement->id) }}" method="POST">
                                                        @csrf
                                                        <button class="btn btn-success btn-sm">Approve</button>
                                                    </form>
                                                    <form action="{{ route('admin.teacher-achievements.reject', $achievement->id) }}" method="POST">
                                                        @csrf
                                                        <button class="btn btn-sm btn-outline-danger">Reject</button>
                                                    </form>
                                                </div>
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="9" class="text-center text-muted py-4">No STEM Engineer achievements found.</td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>

                    @if(method_exists($achievements, 'links'))
                        <div class="px-3 pb-3">
                            {{ $achievements->links('pagination::bootstrap-5') }}
                        </div>
                    @endif
                </div>
            @endif
        </div>
    </div>
</div>

@endsection
