@extends('layouts.app')

@section('content')

<div class="container-fluid">
    <div class="row">
        @include('layouts.teacher-sidebar')

        <div class="col-md-10 col-lg-10 p-4">
            <div class="page-header d-flex justify-content-between align-items-center flex-wrap gap-3 mb-4">
                <div>
                    <h2 class="mb-1">Assessment Management</h2>
                    <p class="text-muted mb-0">View assessments assigned to your institute and classes.</p>
                </div>

                <a href="{{ route('teacher.assessments') }}" class="btn btn-primary btn-sm">
                    Manage Assessments
                </a>
            </div>

            <div class="card shadow border-0 mb-4">
                <div class="card-body">
                    <form method="GET" action="{{ route('teacher.assessments') }}" class="row g-3 align-items-end">
                        <div class="col-md-3">
                            <label class="form-label">Search</label>
                            <input type="text" name="search" class="form-control" value="{{ $searchFilter ?? '' }}" placeholder="Title or class">
                        </div>
                        <div class="col-md-2">
                            <label class="form-label">Class</label>
                            <select name="class" class="form-control">
                                <option value="">All Classes</option>
                                @foreach($assignedClasses as $assignedClass)
                                    <option value="{{ $assignedClass }}" {{ ($classFilter ?? '') == $assignedClass ? 'selected' : '' }}>
                                        {{ $assignedClass }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-2">
                            <label class="form-label">Section</label>
                            <input type="text" name="section" class="form-control" value="{{ $sectionFilter ?? '' }}" placeholder="A">
                        </div>
                        <div class="col-md-2">
                            <label class="form-label">Category</label>
                            <select name="category" class="form-control">
                                <option value="">All Categories</option>
                                @foreach(['Monthly', 'Annual'] as $category)
                                    <option value="{{ $category }}" {{ ($categoryFilter ?? '') == $category ? 'selected' : '' }}>
                                        {{ $category }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-2">
                            <label class="form-label">Status</label>
                            <select name="status" class="form-control">
                                <option value="">All Statuses</option>
                                @foreach(['Active', 'Draft', 'Archived'] as $assessmentStatus)
                                    <option value="{{ $assessmentStatus }}" {{ ($statusFilter ?? '') == $assessmentStatus ? 'selected' : '' }}>
                                        {{ $assessmentStatus }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Date</label>
                            <input type="date" name="assessment_date" class="form-control" value="{{ $dateFilter ?? '' }}">
                        </div>
                        <div class="col-md-2">
                            <button type="submit" class="btn btn-primary w-100">Apply</button>
                        </div>
                        <div class="col-md-2">
                            <a href="{{ route('teacher.assessments') }}" class="btn btn-outline-secondary w-100">Clear</a>
                        </div>
                    </form>
                </div>
            </div>

            <div class="card shadow border-0">
                <div class="card-body">
                    @if($showFilterPlaceholder)
                        @include('partials.filter-placeholder')
                    @else
                    <div class="table-responsive lms-table-shell">
                        <table class="table table-bordered table-hover align-middle mb-0 lms-table-fit">
                            <thead class="table-light">
                                <tr>
                                    <th>Sl. No</th>
                                    <th>Assessment</th>
                                    <th>Class</th>
                                    <th>Category</th>
                                    <th>Date</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($assessments as $index => $assessment)
                                    <tr>
                                        <td>{{ $index + 1 }}</td>
                                        <td>{{ $assessment->assessment_title }}</td>
                                        <td>{{ $assessment->assigned_class }}</td>
                                        <td>{{ $assessment->assessment_category ?? 'Monthly' }}</td>
                                        <td>
                                            {{ $assessment->assessment_date ? \Carbon\Carbon::parse($assessment->assessment_date)->format('d M Y') : 'Not Set' }}
                                        </td>
                                        <td>{{ $assessment->status ?? 'Active' }}</td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="6" class="text-center text-muted">
                                            No assessments found.
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>

@endsection
