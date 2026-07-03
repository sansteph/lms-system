@extends('layouts.app')

@section('content')

<div class="container-fluid">
    <div class="row">

        @include('layouts.coordinator-sidebar')

        <div class="col-md-10 col-lg-10 p-4">

            <div class="page-header mb-4">
                <h2 class="mb-1">Coordinator Dashboard</h2>
                <p class="text-muted mb-0">
                    Monitor LMS activity, sessions, content delivery, and reviews.
                </p>
            </div>

            <div class="row g-4">

                <div class="col-md-4">
                    <div class="dashboard-card">
                        <h6>Today's Classes</h6>
                        <h2>{{ $todayClasses }}</h2>
                    </div>
                </div>

                <div class="col-md-4">
                    <div class="dashboard-card">
                        <h6>Live Sessions</h6>
                        <h2>{{ $liveSessions }}</h2>
                    </div>
                </div>

                <div class="col-md-4">
                    <div class="dashboard-card">
                        <h6>Completed Today</h6>
                        <h2>{{ $completedToday }}</h2>
                    </div>
                </div>

                <div class="col-md-4">
                    <div class="dashboard-card">
                        <h6>Pending Today</h6>
                        <h2>{{ $pendingToday }}</h2>
                    </div>
                </div>

                <div class="col-md-4">
                    <div class="dashboard-card">
                        <h6>Released Topics</h6>
                        <h2>{{ $releasedTopics }}</h2>
                    </div>
                </div>

                <div class="col-md-4">
                    <div class="dashboard-card">
                        <h6>Pending Reviews</h6>
                        <h2>{{ $pendingReviews }}</h2>
                    </div>
                </div>

            </div>

        </div>

    </div>
</div>

@endsection
