@extends('layouts.app')

@section('content')

<div class="container-fluid">
    <div class="row">

        @include('layouts.student-sidebar')

        <div class="col-md-10 col-lg-10 p-4">

            <div class="page-header mb-4">

                <h2 class="mb-1">
                    Assessment History
                </h2>

                <p class="text-muted mb-0">
                    View completed assessments, scores, percentages, and earned badges.
                </p>

            </div>

            @if(session('success'))

                <div class="alert alert-success">
                    {{ session('success') }}
                </div>

            @endif

            @if(session('error'))

                <div class="alert alert-danger">
                    {{ session('error') }}
                </div>

            @endif

            <div class="row g-4 mb-4">

                <div class="col-md-3">

                    <div class="dashboard-card">

                        <h6>Total Attempted</h6>

                        <h2>
                            {{ $results->count() }}
                        </h2>

                    </div>

                </div>

                <div class="col-md-3">

                    <div class="dashboard-card">

                        <h6>Completed</h6>

                        <h2>
                            {{ $results->where('status', 'Completed')->count() }}
                        </h2>

                    </div>

                </div>

                <div class="col-md-3">

                    <div class="dashboard-card">

                        <h6>Pending Review</h6>

                        <h2>
                            {{ $results->where('status', 'Pending Review')->count() }}
                        </h2>

                    </div>

                </div>

                <div class="col-md-3">

                    <div class="dashboard-card">

                        <h6>Badges Earned</h6>

                        <h2>
                            {{ $results->whereNotNull('badge')->count() }}
                        </h2>

                    </div>

                </div>

            </div>

            <div class="card shadow border-0">

                <div class="card-body">

                    <table class="table table-bordered table-hover align-middle">

                        <thead class="table-light">

                            <tr>

                                <th>Sl. No</th>

                                <th>Assessment</th>

                                <th>Category</th>

                                <th>Date</th>

                                <th>Score</th>

                                <th>Percentage</th>

                                <th>Status</th>

                                <th>Badge</th>

                            </tr>

                        </thead>

                        <tbody>

                            @forelse($results as $index => $result)

                                <tr>

                                    <td>
                                        {{ $index + 1 }}
                                    </td>

                                    <td>

                                        @if($result->assessment)

                                            {{ $result->assessment->assessment_title }}

                                        @else

                                            Assessment Deleted

                                        @endif

                                    </td>

                                    <td>
                                        {{ $result->assessment->assessment_category ?? 'N/A' }}
                                    </td>

                                    <td>
                                        {{ $result->assessment && $result->assessment->assessment_date ? \Carbon\Carbon::parse($result->assessment->assessment_date)->format('d-m-Y') : $result->created_at->format('d-m-Y') }}
                                    </td>

                                    <td>
                                        {{ $result->score }}/{{ $result->total_marks }}
                                    </td>

                                    <td>
                                        {{ number_format($result->percentage, 2) }}%
                                    </td>

                                    <td>

                                        @if($result->status == 'Completed')

                                            <span class="badge bg-success">
                                                Completed
                                            </span>

                                        @elseif($result->status == 'Pending Review')

                                            <span class="badge bg-warning text-dark">
                                                Pending Review
                                            </span>

                                        @else

                                            <span class="badge bg-secondary">
                                                {{ $result->status }}
                                            </span>

                                        @endif

                                    </td>

                                    <td>

                                        @if($result->badge == 'Gold')

                                            <span class="badge bg-warning text-dark">
                                                Gold
                                            </span>

                                        @elseif($result->badge == 'Silver')

                                            <span class="badge bg-secondary">
                                                Silver
                                            </span>

                                        @elseif($result->badge == 'Bronze')

                                            <span class="badge bg-danger">
                                                Bronze
                                            </span>

                                        @else

                                            <span class="badge bg-light text-dark">
                                                No Badge
                                            </span>

                                        @endif

                                    </td>

                                </tr>

                            @empty

                                <tr>

                                    <td colspan="8"
                                        class="text-center text-muted">

                                        No assessment history found.

                                    </td>

                                </tr>

                            @endforelse

                        </tbody>

                    </table>

                    <div class="alert alert-info mt-3 mb-0">

                        Scores, percentages, and badges are generated automatically from submitted assessment results.

                    </div>

                </div>

            </div>

        </div>

    </div>
</div>

@endsection
