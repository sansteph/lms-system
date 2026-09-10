@extends('layouts.app')

@section('content')

<div class="container-fluid">
    <div class="row">

        @include('layouts.sidebar')

        <div class="col-md-10 col-lg-10 p-4">

            <div class="page-header mb-4">
                <h2>
                    @if(($submitterType ?? null) == 'Teacher')
                        STEM Engineer My Space Review
                    @elseif(($submitterType ?? null) == 'Student')
                        Student My Space Review
                    @else
                        My Space Review
                    @endif
                </h2>
                <p class="text-muted mb-0">
                    Review, approve, reject, and feature submitted ideas and projects.
                </p>
            </div>

            @if(session('success'))
                <div class="alert alert-success">
                    {{ session('success') }}
                </div>
            @endif

            <div class="card shadow border-0 mb-4">
                <div class="card-body lms-report-filter-card">
                    <div class="d-flex justify-content-between align-items-start flex-wrap gap-3 mb-3">
                        <div>
                            <h5 class="mb-1">Apply filters for easy navigation</h5>
                            <p class="text-muted mb-0">
                                @if(($submitterType ?? null) == 'Student')
                                    Choose institute, class, section, or status to load the relevant My Space approvals.
                                @else
                                    Choose institute or status to load the relevant My Space approvals.
                                @endif
                            </p>
                        </div>
                        <span class="badge bg-light text-dark border">Approval Filters</span>
                    </div>

                    @php
                        $mySpaceRoute = ($submitterType ?? null) == 'Teacher'
                            ? route('admin.my-space.teachers')
                            : (($submitterType ?? null) == 'Student' ? route('admin.my-space.students') : route('admin.my-space'));
                    @endphp

                    <form method="GET" action="{{ $mySpaceRoute }}">
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

                            @if(($submitterType ?? null) == 'Student')
                                <div class="lms-report-action-group">
                                    <label class="form-label">Class</label>
                                    <select name="student_class" class="form-select">
                                        <option value="">All classes</option>
                                        @foreach($classOptions as $classOption)
                                            <option value="{{ $classOption }}" @selected($selectedStudentClass == $classOption)>{{ $classOption }}</option>
                                        @endforeach
                                    </select>
                                </div>

                                <div class="lms-report-action-group">
                                    <label class="form-label">Section</label>
                                    <select name="student_section" class="form-select">
                                        <option value="">All sections</option>
                                        @foreach($sectionOptions as $sectionOption)
                                            <option value="{{ $sectionOption }}" @selected($selectedStudentSection == $sectionOption)>{{ $sectionOption }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            @endif

                            <div class="lms-report-action-group">
                                <label class="form-label">Status</label>
                                <select name="status" class="form-select">
                                    <option value="">Any status</option>
                                    <option value="Pending" @selected($selectedItemStatus == 'Pending')>Pending</option>
                                    <option value="Approved" @selected($selectedItemStatus == 'Approved')>Approved</option>
                                    <option value="Rejected" @selected($selectedItemStatus == 'Rejected')>Rejected</option>
                                    <option value="Featured" @selected($selectedItemStatus == 'Featured')>Featured</option>
                                </select>
                            </div>
                        </div>

                        <div class="lms-report-button-band">
                            <button type="submit" class="btn btn-primary lms-report-action-button">Apply Filters</button>
                            <a href="{{ $mySpaceRoute }}" class="btn btn-outline-secondary lms-report-action-clear">Clear</a>
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
                            <table class="table table-hover align-middle lms-table-fit">
                                <thead class="table-light">
                                    <tr>
                                        <th>Title</th>
                                        <th>Type</th>
                                        <th>Submitter</th>
                                        <th>Role</th>
                                        <th>Institute</th>
                                        <th>Status</th>
                                        <th width="220">Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @php
                                        $itemRows = method_exists($items, 'getCollection') ? $items->getCollection() : collect($items);
                                    @endphp

                                    @forelse($itemRows as $item)
                                        @php
                                            $submitter = $item->submitter();
                                            $submitterInstitute = $submitter->institute ?? 'N/A';
                                        @endphp

                                        <tr>
                                            <td>{{ $item->title }}</td>
                                            <td>
                                                <span class="badge bg-info">
                                                    {{ $item->type }}
                                                </span>
                                            </td>
                                            <td>
                                                @if($submitter)
                                                    <strong>{{ $submitter->name }}</strong>
                                                    <br>
                                                    <small class="text-muted">
                                                        {{ $item->created_by_type == 'Student' ? $submitter->student_id : $submitter->user_id }}
                                                    </small>
                                                @else
                                                    Deleted User
                                                @endif
                                            </td>
                                            <td>{{ $item->created_by_type }}</td>
                                            <td>{{ $submitterInstitute }}</td>
                                            <td>
                                                @if($item->status == 'Approved')
                                                    <span class="badge bg-success">Approved</span>
                                                @elseif($item->status == 'Rejected')
                                                    <span class="badge bg-danger">Rejected</span>
                                                @elseif($item->status == 'Featured')
                                                    <span class="badge bg-warning text-dark">Featured</span>
                                                @else
                                                    <span class="badge bg-secondary">{{ $item->status }}</span>
                                                @endif
                                            </td>
                                            <td>
                                                <div class="d-flex flex-wrap gap-2">
                                                    <a href="{{ route('admin.my-space.show', $item->id) }}" class="btn btn-sm btn-primary">View</a>

                                                    @if($item->status === 'Pending')
                                                    <form method="POST" action="{{ route('admin.my-space.approve', $item->id) }}">
                                                        @csrf
                                                        <button type="submit" class="btn btn-sm btn-success">Approve</button>
                                                    </form>

                                                    <form method="POST" action="{{ route('admin.my-space.reject', $item->id) }}">
                                                        @csrf
                                                        <button type="submit" class="btn btn-sm btn-outline-danger">Reject</button>
                                                    </form>
                                                    @endif

                                                    @if(in_array($item->status, ['Pending', 'Approved']))
                                                    <form method="POST" action="{{ route('admin.my-space.feature', $item->id) }}">
                                                        @csrf
                                                        <button type="submit" class="btn btn-sm btn-outline-primary">Feature</button>
                                                    </form>
                                                    @endif
                                                </div>
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="7" class="text-center text-muted py-4">No My Space submissions found.</td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>

                    @if(method_exists($items, 'links'))
                        <div class="px-3 pb-3">
                            {{ $items->links('pagination::bootstrap-5') }}
                        </div>
                    @endif
                </div>
            @endif
        </div>
    </div>
</div>

@endsection
