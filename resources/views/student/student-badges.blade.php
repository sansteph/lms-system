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
                    <div class="dashboard-card">
                        <h6>Total Badges</h6>
                        <h2>{{ $results->count() }}</h2>
                    </div>
                </div>

                <div class="col-md-3">
                    <div class="dashboard-card">
                        <h6>Gold Badges</h6>
                        <h2>{{ $goldCount }}</h2>
                    </div>
                </div>

                <div class="col-md-3">
                    <div class="dashboard-card">
                        <h6>Silver Badges</h6>
                        <h2>{{ $silverCount }}</h2>
                    </div>
                </div>

                <div class="col-md-3">
                    <div class="dashboard-card">
                        <h6>Bronze Badges</h6>
                        <h2>{{ $bronzeCount }}</h2>
                    </div>
                </div>

            </div>

            <div class="card shadow border-0 mb-4">

                <div class="card-body">

                    <h5 class="mb-4">
                        Earned Badges
                    </h5>

                    <div class="row g-4">

                        @forelse($results as $result)

                            <div class="col-md-3">

                                <div class="card border-0 shadow-sm text-center p-3">

                                    <div class="mb-3" style="font-size: 50px;">

                                        @if($result->badge == 'Gold')

                                            <i class="fa fa-trophy text-warning"></i>

                                        @elseif($result->badge == 'Silver')

                                            <i class="fa fa-medal text-secondary"></i>

                                        @elseif($result->badge == 'Bronze')

                                            <i class="fa fa-award text-danger"></i>

                                        @endif

                                    </div>

                                    <h6>
                                        Assessment #{{ $result->assessment->assessment_title }}
                                    </h6>

                                    <p class="text-muted small mb-2">
                                        Score:
                                        {{ $result->score }}/{{ $result->total_marks }}
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

                                <div class="alert alert-info mb-0">
                                    No badges earned yet.
                                </div>

                            </div>

                        @endforelse

                    </div>

                </div>

            </div>

            @if($certificateEligible)
                <div class="alert alert-success mb-0">
                    Congratulations! You are eligible for a certificate based on your badge achievements.
                </div>
            @else
                <div class="alert alert-info mb-0">
                    Earn at least 5 badges to become eligible for a certificate.
                </div>
            @endif

        </div>

    </div>
</div>

@endsection