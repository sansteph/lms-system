@extends('layouts.app')

@section('content')

<div class="container-fluid">
    <div class="row">

        @include('layouts.sidebar')

        <div class="col-md-10 col-lg-10 p-4">
            <div class="page-header mb-4">
                <h2 class="mb-1">Student Achievement Approvals</h2>
                <p class="text-muted mb-0">Review and approve student achievements submitted inside the selected institute, class, and section.</p>
            </div>

            @if(session('success'))
                <div class="alert alert-success">{{ session('success') }}</div>
            @endif

            <div class="card shadow border-0 mb-4">
                <div class="card-body lms-report-filter-card">
                    <div class="d-flex justify-content-between align-items-start flex-wrap gap-3 mb-3">
                        <div>
                            <h5 class="mb-1">Apply filters for easy navigation</h5>
                            <p class="text-muted mb-0">Choose institute, class, and section to load the relevant approvals.</p>
                        </div>
                        <span class="badge bg-light text-dark border">Approval Filters</span>
                    </div>

                    <form method="GET" action="{{ route('admin.achievements') }}">
                        <div class="lms-report-filter-grid">
                            @if(session('user_role') == 'Admin')
                                <div class="lms-report-action-group">
                                    <label class="form-label">Institute</label>
                                    <select name="institute" class="form-select">
                                        <option value="">Select institute</option>
                                        @foreach($instituteOptions as $instituteOption)
                                            <option value="{{ $instituteOption }}" @selected($currentInstitute == $instituteOption)>{{ $instituteOption }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            @endif

                            <div class="lms-report-action-group">
                                <label class="form-label">Class</label>
                                <select name="student_class" class="form-select">
                                    <option value="">Select class</option>
                                    @foreach($classOptions as $classOption)
                                        <option value="{{ $classOption }}" @selected($selectedStudentClass == $classOption)>{{ $classOption }}</option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="lms-report-action-group">
                                <label class="form-label">Section</label>
                                <select name="student_section" class="form-select">
                                    <option value="">Select section</option>
                                    @foreach($sectionOptions as $sectionOption)
                                        <option value="{{ $sectionOption }}" @selected($selectedStudentSection == $sectionOption)>{{ $sectionOption }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>

                        <div class="lms-report-button-band">
                            <button type="submit" class="btn btn-primary lms-report-action-button">Apply Filters</button>
                            <a href="{{ route('admin.achievements') }}" class="btn btn-outline-secondary lms-report-action-clear">Clear</a>
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
                            <table class="table table-bordered table-hover align-middle lms-table-fit">
                                <thead class="table-light">
                                    <tr>
                                        <th>ID</th>
                                        <th>Student</th>
                                        <th>Institute</th>
                                        <th>Class</th>
                                        <th>Section</th>
                                        <th>Type</th>
                                        <th>Title</th>
                                        <th>Status</th>
                                        <th>Certificate</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @php
                                        $achievementRows = method_exists($achievements, 'getCollection') ? $achievements->getCollection() : collect($achievements);
                                    @endphp

                                    @forelse($achievementRows as $achievement)
                                        <tr>
                                            <td>{{ $achievement->id }}</td>
                                            <td>
                                                <strong>{{ $achievement->student->name ?? 'Student Deleted' }}</strong>
                                                @if($achievement->student)
                                                    <br>
                                                    <small class="text-muted">{{ $achievement->student->student_id }}</small>
                                                @endif
                                            </td>
                                            <td>{{ $achievement->student->institute ?? 'N/A' }}</td>
                                            <td>{{ $achievement->student->class ?? 'N/A' }}</td>
                                            <td>{{ $achievement->student->section ?? 'N/A' }}</td>
                                            <td>{{ $achievement->achievement_type }}</td>
                                            <td>{{ $achievement->title }}</td>
                                            <td>
                                                @if($achievement->status == 'Approved')
                                                    <span class="badge bg-success">Approved</span>
                                                @elseif($achievement->status == 'Rejected')
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
                                                    <form action="{{ route('admin.achievements.approve', $achievement->id) }}" method="POST">
                                                        @csrf
                                                        <button class="btn btn-success btn-sm">Approve</button>
                                                    </form>

                                                    <form action="{{ route('admin.achievements.reject', $achievement->id) }}" method="POST">
                                                        @csrf
                                                        <button class="btn btn-sm btn-outline-danger">Reject</button>
                                                    </form>
                                                </div>
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="10" class="text-center text-muted py-4">No student achievements found.</td>
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
