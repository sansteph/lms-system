@extends('layouts.app')

@section('content')

<div class="container-fluid">
    <div class="row">

        @include('layouts.student-sidebar')

        <div class="col-md-10 col-lg-10 p-4">

            <div class="page-header d-flex justify-content-between align-items-center flex-wrap gap-3 mb-4">
                <div>
                    <h2 class="mb-1">
                        Welcome, {{ $studentName }}
                    </h2>
                    <p class="text-muted mb-0">
                        Student ID: {{ $studentCode }}
                    </p>
                </div>

                <a href="{{ route('student.assessment') }}" class="btn btn-primary btn-sm">
                    Take Assessment
                </a>
            </div>

            <div class="row g-4 mb-4">
                <div class="col-md-3">
                    <div class="dashboard-card">
                        <h6>Total Assessments</h6>
                        <h2>{{ $totalAssessmentCount }}</h2>
                    </div>
                </div>

                <div class="col-md-3">
                    <div class="dashboard-card">
                        <h6>Completed</h6>
                        <h2>{{ $results->where('status', 'Completed')->count() }}</h2>
                    </div>
                </div>

                <div class="col-md-3">
                    <div class="dashboard-card">
                        <h6>Pending</h6>
                        <h2>{{ $pendingAssessmentCount }}</h2>
                    </div>
                </div>

                <div class="col-md-3">
                    <div class="dashboard-card">
                        <h6>Badges Earned</h6>
                        <h2>{{ $badgeCount }}</h2>
                    </div>
                </div>
            </div>

            <div class="row g-4">

                <div class="col-lg-8">
                    <div class="card shadow border-0">
                        <div class="card-body">

                            <h5 class="mb-3">Upcoming Assessments</h5>

                            <table class="table table-bordered table-hover align-middle">
                                <thead class="table-light">
                                    <tr>
                                        <th>Sl. No</th>
                                        <th>Assessment</th>
                                        <th>Subject</th>
                                        <th>Duration</th>
                                        <th>Status</th>
                                        <th width="160">Action</th>
                                    </tr>
                                </thead>

                                <tbody>
                                    @forelse($results as $index => $result)
                                        <tr>
                                            <td>{{ $index + 1 }}</td>
                                            <td>Assessment #{{ $result->assessment_id }}</td>
                                            <td>General</td>
                                            <td>{{ $result->score }}/{{ $result->total_marks }}</td>
                                            <td><span class="badge bg-success">{{ $result->status }}</span></td>
                                            <td>
                                                <a href="{{ route('student.history') }}" class="btn btn-sm btn-primary">
                                                    View Result
                                                </a>
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="6" class="text-center text-muted">
                                                No assessments submitted yet.
                                            </td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>

                        </div>
                    </div>
                </div>

                <div class="col-lg-4">
                    <div class="card shadow border-0 mb-4">
                        <div class="card-body">
                            <h5 class="mb-3">Quick Actions</h5>

                            <div class="d-grid gap-2">
                                <a href="{{ route('student.assessment') }}" class="btn btn-primary">
                                    Take Assessment
                                </a>
                                <button class="btn btn-success">View Results</button>
                                <a href="{{ route('student.badges') }}" class="btn btn-warning">
                                    View Achievements
                                </a>
                            </div>
                        </div>
                    </div>

                    <div class="card shadow border-0">
                        <div class="card-body">
                            <div class="card-body">
                                <h5 class="mb-3">Latest Updates</h5>
                                @forelse($notifications as $notification)

                                    <div class="border-bottom pb-2 mb-2">

                                        <strong>{{ $notification->title }}</strong>

                                        <p class="text-muted mb-0 small">
                                            {{ $notification->message }}
                                        </p>

                                    </div>

                                @empty

                                    <p class="text-muted mb-0 small">
                                        No latest updates available.
                                    </p>

                                @endforelse

                            </div>

                        </div>
                    </div>
                </div>

            </div>

        </div>

    </div>
</div>

@endsection