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
                        <h2>12</h2>
                    </div>
                </div>

                <div class="col-md-3">
                    <div class="dashboard-card">
                        <h6>Gold Badges</h6>
                        <h2>4</h2>
                    </div>
                </div>

                <div class="col-md-3">
                    <div class="dashboard-card">
                        <h6>Silver Badges</h6>
                        <h2>5</h2>
                    </div>
                </div>

                <div class="col-md-3">
                    <div class="dashboard-card">
                        <h6>Bronze Badges</h6>
                        <h2>3</h2>
                    </div>
                </div>

            </div>

            <div class="card shadow border-0 mb-4">
                <div class="card-body">

                    <h5 class="mb-4">Earned Badges</h5>

                    <div class="row g-4">

                        <div class="col-md-3">
                            <div class="card border-0 shadow-sm text-center p-3">

                                <div class="mb-3" style="font-size: 50px;">
                                    <i class="fa fa-trophy text-warning"></i>
                                </div>

                                <h6>AI Master</h6>

                                <p class="text-muted small mb-2">
                                    Completed all AI modules.
                                </p>

                                <span class="badge bg-warning text-dark">
                                    Gold Badge
                                </span>

                            </div>
                        </div>

                        <div class="col-md-3">
                            <div class="card border-0 shadow-sm text-center p-3">

                                <div class="mb-3" style="font-size: 50px;">
                                    <i class="fa fa-medal text-secondary"></i>
                                </div>

                                <h6>Robotics Explorer</h6>

                                <p class="text-muted small mb-2">
                                    Completed robotics assessments.
                                </p>

                                <span class="badge bg-secondary">
                                    Silver Badge
                                </span>

                            </div>
                        </div>

                        <div class="col-md-3">
                            <div class="card border-0 shadow-sm text-center p-3">

                                <div class="mb-3" style="font-size: 50px;">
                                    <i class="fa fa-award text-danger"></i>
                                </div>

                                <h6>IoT Beginner</h6>

                                <p class="text-muted small mb-2">
                                    Completed IoT introduction chapter.
                                </p>

                                <span class="badge bg-danger">
                                    Bronze Badge
                                </span>

                            </div>
                        </div>

                        <div class="col-md-3">
                            <div class="card border-0 shadow-sm text-center p-3 bg-light">

                                <div class="mb-3" style="font-size: 50px; opacity: 0.4;">
                                    <i class="fa fa-lock text-dark"></i>
                                </div>

                                <h6 class="text-muted">Advanced ML Badge</h6>

                                <p class="text-muted small mb-2">
                                    Complete all ML assessments to unlock.
                                </p>

                                <span class="badge bg-dark">
                                    Locked
                                </span>

                            </div>
                        </div>

                    </div>

                </div>
            </div>

            <div class="alert alert-info mb-0">
                Students with the highest badge achievements will become eligible for certificates and rewards.
            </div>

        </div>

    </div>
</div>

@endsection