@extends('layouts.app')

@section('content')

<div class="container-fluid">
    <div class="row">

        @include('layouts.coordinator-sidebar')

        <div class="col-md-10 col-lg-10 p-4">

            <div class="page-header mb-4">
                <h2 class="mb-1">Assessment Monitoring</h2>
                <p class="text-muted mb-0">
                    Monitor assessment readiness, attempts, scores, and pending reviews.
                </p>
            </div>

            <div class="card shadow border-0">
                <div class="card-body">

                    <table class="table table-bordered table-hover align-middle">

                        <thead class="table-light">
                            <tr>
                                <th>Sl. No</th>
                                <th>Assessment</th>
                                <th>Institute</th>
                                <th>Class</th>
                                <th>Category</th>
                                <th>Assessment Date</th>
                                <th>Question Paper</th>
                                <th>Attempts</th>
                                <th>Pending Reviews</th>
                                <th>Average Score</th>
                                <th>Status</th>
                            </tr>
                        </thead>

                        <tbody>

                            @forelse($assessments as $index => $assessment)

                                @php
                                    $attempts = $assessment->results->count();

                                    $pendingReviews = $assessment->results
                                        ->where('status', 'Pending Review')
                                        ->count();

                                    $averageScore = $assessment->results
                                        ->where('status', 'Completed')
                                        ->avg('percentage');
                                @endphp

                                <tr>
                                    <td>{{ $index + 1 }}</td>

                                    <td>{{ $assessment->assessment_title }}</td>

                                    <td>{{ $assessment->institute ?? 'N/A' }}</td>

                                    <td>{{ $assessment->assigned_class }}</td>

                                    <td>{{ $assessment->assessment_category ?? 'Monthly' }}</td>

                                    <td>{{ $assessment->assessment_date ? \Carbon\Carbon::parse($assessment->assessment_date)->format('d M Y') : 'Not Set' }}</td>

                                    <td>
                                        @if($assessment->question_paper_status == 'Approved')
                                            <span class="badge bg-success">Approved</span>
                                        @elseif($assessment->question_paper_status == 'Rejected')
                                            <span class="badge bg-danger">Rejected</span>
                                        @else
                                            <span class="badge bg-warning text-dark">Pending</span>
                                        @endif
                                    </td>

                                    <td>{{ $attempts }}</td>

                                    <td>
                                        @if($pendingReviews > 0)
                                            <span class="badge bg-warning text-dark">
                                                {{ $pendingReviews }}
                                            </span>
                                        @else
                                            <span class="badge bg-success">
                                                0
                                            </span>
                                        @endif
                                    </td>

                                    <td>
                                        {{ $averageScore ? number_format($averageScore, 1) . '%' : 'N/A' }}
                                    </td>

                                    <td>
                                        @if($assessment->status == 1)
                                            <span class="badge bg-success">Active</span>
                                        @else
                                            <span class="badge bg-danger">Inactive</span>
                                        @endif
                                    </td>
                                </tr>

                            @empty

                                <tr>
                                    <td colspan="11"
                                        class="text-center text-muted">
                                        No assessments found.
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

