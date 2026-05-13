@php
    use Illuminate\Support\Str;
@endphp
@extends('layouts.app')

@section('content')

<div class="container-fluid">
    <div class="row">

        @include('layouts.student-sidebar')

        <div class="col-md-10 col-lg-10 p-4">

            <div class="page-header mb-4">
                <h2 class="mb-1">My Badges</h2>
                <p class="text-muted mb-0">
                    Earn badges by completing chapters, assessments, and activities.
                </p>
            </div>

            <div class="row g-4 mb-4">

                <div class="col-md-3">
                    <div class="dashboard-card achievement-stat-card">
                        <div class="achievement-stat-icon bg-primary-subtle text-primary">
                            <i class="fa fa-trophy"></i>
                        </div>

                        <div>
                            <h6>Total Badges</h6>
                            <h2>{{ $results->count() }}</h2>
                        </div>
                    </div>
                </div>

                <div class="col-md-3">
                    <div class="dashboard-card achievement-stat-card">
                        <div class="achievement-stat-icon bg-warning-subtle text-warning">
                            <i class="fa fa-medal"></i>
                        </div>

                        <div>
                            <h6>Gold Badges</h6>
                            <h2>{{ $goldCount }}</h2>
                        </div>
                    </div>
                </div>

                <div class="col-md-3">
                    <div class="dashboard-card achievement-stat-card">
                        <div class="achievement-stat-icon bg-secondary-subtle text-secondary">
                            <i class="fa fa-award"></i>
                        </div>

                        <div>
                            <h6>Silver</h6>
                            <h2>{{ $silverCount }}</h2>
                        </div>
                    </div>
                </div>

                <div class="col-md-3">
                    <div class="dashboard-card achievement-stat-card">
                        <div class="achievement-stat-icon bg-danger-subtle text-danger">
                            <i class="fa fa-certificate"></i>
                        </div>

                        <div>
                            <h6>Bronze</h6>
                            <h2>{{ $bronzeCount }}</h2>
                        </div>
                    </div>
                </div>

                <div class="col-md-3">
                    <div class="dashboard-card achievement-stat-card">
                        <div class="achievement-stat-icon bg-success-subtle text-success">
                            <i class="fa fa-scroll"></i>
                        </div>

                        <div>
                            <h6>Certificate</h6>

                            @if($certificateEligible)
                                <span class="badge bg-success">Eligible</span>
                            @else
                                <span class="badge bg-warning text-dark">Pending</span>
                            @endif
                        </div>
                    </div>
                </div>

            </div>

            <div class="card shadow border-0 mb-4">
                <div class="card-body">

                    <h5 class="mb-4">Earned Badges</h5>

                    <div class="row g-4">

                        @forelse($results as $result)

                            <div class="col-md-3">
                                <div class="card border-0 shadow-sm text-center p-3 earned-badge-card">

                                    <div class="mb-3" style="font-size: 50px;">

                                        @if($result->badge == 'Gold')
                                            <i class="fa fa-trophy text-warning"></i>
                                        @elseif($result->badge == 'Silver')
                                            <i class="fa fa-medal text-secondary"></i>
                                        @elseif($result->badge == 'Bronze')
                                            <i class="fa fa-award text-danger"></i>
                                        @endif

                                    </div>

                                    <h6 class="earned-badge-title"
                                        title="{{ $result->assessment->assessment_title ?? 'Assessment Deleted' }}">
                                        {{ Str::limit($result->assessment->assessment_title ?? 'Assessment Deleted', 45) }}
                                    </h6>

                                    <p class="text-muted small mb-2">
                                        Score: {{ $result->score }}/{{ $result->total_marks }}
                                    </p>

                                    <span class="
                                        badge
                                        @if($result->badge == 'Gold')
                                            bg-warning text-dark
                                        @elseif($result->badge == 'Silver')
                                            bg-secondary
                                        @elseif($result->badge == 'Bronze')
                                            bg-danger
                                        @endif
                                    ">
                                        {{ $result->badge }} Badge
                                    </span>

                                </div>
                            </div>

                        @empty

                            <div class="col-12">
                                <div class="achievement-empty-state">

                                    <div class="empty-badge-icon">
                                        <i class="fa fa-medal"></i>
                                    </div>

                                    <h5>No badges earned yet</h5>

                                    <p>
                                        Complete assessments to unlock your first achievement badge.
                                    </p>

                                    <a href="{{ route('student.assessment') }}" class="btn btn-primary btn-sm">
                                        Take Assessment
                                    </a>

                                </div>
                            </div>

                        @endforelse

                    </div>

                </div>
            </div>

            <div class="certificate-progress-card">

                <div class="d-flex justify-content-between align-items-center mb-3">

                    <div>
                        <h5 class="mb-1">Certificate Progress</h5>
                        <p class="text-muted mb-0">
                            Earn 5 badges to unlock your certificate eligibility.
                        </p>
                    </div>

                    <div>
                        @if($certificateEligible)
                            <span class="badge bg-success px-3 py-2">
                                Eligible
                            </span>
                        @else
                            <span class="badge bg-warning text-dark px-3 py-2">
                                In Progress
                            </span>
                        @endif
                    </div>

                </div>

                <div class="progress achievement-progress mb-3">
                    <div class="progress-bar"
                         role="progressbar"
                         style="width: {{ min(($results->count() / 5) * 100, 100) }}%">
                    </div>
                </div>

                <div class="d-flex justify-content-between">

                    <small class="text-muted">
                        {{ $results->count() }} / 5 badges earned
                    </small>

                    @if($certificateEligible)
                        <small class="text-success fw-semibold">
                            Certificate unlocked
                        </small>
                    @else
                        <small class="text-primary fw-semibold">
                            Keep progressing
                        </small>
                    @endif

                </div>

            </div>

        </div>

    </div>
</div>

@endsection