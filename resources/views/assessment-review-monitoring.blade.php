@extends('layouts.app')

@section('content')

<div class="container-fluid">
    <div class="row">

        @include('layouts.sidebar')

        <div class="col-md-10 col-lg-10 p-4">

            <div class="page-header mb-4">
                <h2 class="mb-1">Assessment Review Monitoring</h2>
                <p class="text-muted mb-0">
                    Monitor assessment review status, scores, badges, and pending manual evaluations.
                </p>
            </div>

            <div class="card shadow-sm border-0 mb-4">
                <div class="card-body">
                    <form method="GET" action="{{ route('admin.assessment.review.monitoring') }}" class="row g-3 align-items-end">
                        @if(session('user_role') == 'Admin')
                            <div class="col-md-3">
                                <label for="assessmentReviewInstitute" class="form-label">Institute</label>
                                <select id="assessmentReviewInstitute" name="institute" class="form-select">
                                    <option value="">All Institutes</option>
                                    @foreach($instituteOptions as $institute)
                                        <option value="{{ $institute }}" @selected(($selectedInstitute ?? '') === $institute)>{{ $institute }}</option>
                                    @endforeach
                                </select>
                            </div>
                        @endif

                        <div class="col-md-3">
                            <label for="assessmentReviewClass" class="form-label">Class</label>
                            <select id="assessmentReviewClass" name="student_class" class="form-select">
                                <option value="">All Classes</option>
                                @foreach($reviewClassOptions as $className)
                                    <option value="{{ $className }}" @selected(($selectedStudentClass ?? '') === $className)>{{ $className }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div class="col-md-3">
                            <label for="assessmentReviewSection" class="form-label">Section</label>
                            <select id="assessmentReviewSection" name="student_section" class="form-select">
                                <option value="">All Sections</option>
                                @foreach($reviewSectionOptions as $sectionName)
                                    <option value="{{ $sectionName }}" @selected(($selectedStudentSection ?? '') === $sectionName)>{{ $sectionName }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div class="col-md-2">
                            <label for="assessmentReviewStatus" class="form-label">Status</label>
                            <select id="assessmentReviewStatus" name="status" class="form-select">
                                <option value="">All Statuses</option>
                                <option value="Completed" @selected(($statusFilter ?? '') === 'Completed')>Completed</option>
                                <option value="Pending Review" @selected(($statusFilter ?? '') === 'Pending Review')>Pending Review</option>
                            </select>
                        </div>

                        <div class="col-md-2">
                            <label for="assessmentReviewSearch" class="form-label">Search</label>
                            <input type="text" id="assessmentReviewSearch" name="search" class="form-control" value="{{ $searchFilter ?? '' }}" placeholder="Student / ID">
                        </div>

                        <div class="col-12 d-flex gap-2 flex-wrap">
                            <button type="submit" class="btn btn-primary">Apply Filters</button>
                            <a href="{{ route('admin.assessment.review.monitoring') }}" class="btn btn-outline-secondary">Clear</a>
                        </div>
                    </form>
                </div>
            </div>

            @if($showFilterPlaceholder ?? false)
                @include('partials.filter-placeholder')
            @endif

            @if(!($showFilterPlaceholder ?? false))
                <div class="card shadow border-0">
                    <div class="card-body">
                        <div class="table-responsive lms-table-shell">
                            <table class="table table-bordered table-hover align-middle lms-table-fit">
                                <thead class="table-light">
                                    <tr>
                                        <th>Student</th>
                                        <th>Assessment</th>
                                        <th>Score</th>
                                        <th>Percentage</th>
                                        <th>Status</th>
                                        <th>Badge</th>
                                        <th>Submitted At</th>
                                    </tr>
                                </thead>

                                <tbody>
                                    @php
                                        $resultRows = method_exists($results, 'getCollection') ? $results->getCollection() : collect($results);
                                        $groupedResults = $resultRows
                                            ->sortBy([
                                                fn ($result) => $result->student->institute ?? '',
                                                fn ($result) => $result->student->class ?? '',
                                                fn ($result) => $result->student->section ?? '',
                                                fn ($result) => $result->student->name ?? '',
                                            ])
                                            ->groupBy(fn ($result) => $result->student->institute ?? 'Student Deleted / Unassigned Institute');
                                    @endphp

                                    @forelse($groupedResults as $instituteName => $instituteResults)
                                        <tr class="table-primary">
                                            <td colspan="7" class="fw-semibold">
                                                {{ $instituteName }} · {{ $instituteResults->count() }} result{{ $instituteResults->count() == 1 ? '' : 's' }}
                                            </td>
                                        </tr>

                                        @foreach($instituteResults->groupBy(fn ($result) => trim(($result->student->class ?? '') . ' ' . ($result->student->section ?? '')) ?: 'Student Deleted / Unassigned Class') as $classLabel => $classResults)
                                            <tr class="table-light">
                                                <td colspan="7" class="fw-semibold ps-4">
                                                    {{ $classLabel }} · {{ $classResults->count() }} result{{ $classResults->count() == 1 ? '' : 's' }}
                                                </td>
                                            </tr>

                                            @foreach($classResults as $result)
                                                <tr>
                                                    <td>{{ $result->student->name ?? 'Student Deleted' }}</td>
                                                    <td>{{ $result->assessment->assessment_title ?? 'Assessment Deleted' }}</td>
                                                    <td>{{ $result->score }}/{{ $result->total_marks }}</td>
                                                    <td>{{ number_format($result->percentage, 2) }}%</td>
                                                    <td>
                                                        @if($result->status == 'Pending Review')
                                                            <span class="badge bg-warning text-dark">Pending Review</span>
                                                        @elseif($result->status == 'Completed')
                                                            <span class="badge bg-success">Completed</span>
                                                        @else
                                                            <span class="badge bg-secondary">{{ $result->status }}</span>
                                                        @endif
                                                    </td>
                                                    <td>
                                                        @if($result->badge)
                                                            <span class="badge bg-primary">{{ $result->badge }}</span>
                                                        @else
                                                            <span class="badge bg-light text-dark">No Badge</span>
                                                        @endif
                                                    </td>
                                                    <td>{{ $result->created_at->format('d-m-Y h:i A') }}</td>
                                                </tr>
                                            @endforeach
                                        @endforeach
                                    @empty
                                        <tr>
                                            <td colspan="7" class="text-center text-muted">
                                                No assessment review data found.
                                            </td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>

                    @if(method_exists($results, 'links'))
                        <div class="px-3 pb-3">
                            {{ $results->links('pagination::bootstrap-5') }}
                        </div>
                    @endif
                </div>
            @endif

        </div>

    </div>
</div>

@endsection
