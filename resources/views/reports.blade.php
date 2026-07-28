@extends('layouts.app')

@section('content')

<div class="container-fluid">
    <div class="row">

        @include('layouts.sidebar')

        <div class="col-md-10 col-lg-10 p-4">

            <div class="page-header d-flex justify-content-between align-items-center flex-wrap gap-3 mb-4">
                <div>
                    <h2 class="mb-1">Reports</h2>
                    <p class="text-muted mb-0">
                        Generate, view, and download LMS performance reports.
                    </p>
                </div>

                <div class="d-flex gap-2 flex-wrap">
                    <form method="POST" action="{{ route('reports.ai-insights') }}">
                        @csrf
                        <button type="submit" class="btn btn-primary btn-sm">
                            <i class="fa fa-wand-magic-sparkles me-1"></i>
                            Generate AI Insights
                        </button>
                    </form>
                    <form method="POST" action="{{ route('reports.ai-insights.download') }}">
                        @csrf
                        <button type="submit" class="btn btn-outline-primary btn-sm">
                            <i class="fa fa-file-pdf me-1"></i>
                            Download AI PDF
                        </button>
                    </form>
                    <a href="{{ url('/reports/export') }}" class="btn btn-success btn-sm">
                        <i class="fa fa-file-csv me-1"></i>
                        Export CSV
                    </a>
                </div>

            </div>

            @if(session('error'))
                <div class="alert alert-danger">
                    {{ session('error') }}
                </div>
            @endif

            @php
                $aiInsights = session('aiInsights');
            @endphp

            @if($aiInsights)
                @include('partials.ai-full-report', [
                    'aiInsights' => $aiInsights,
                    'reportTitle' => 'AI Generated LMS Report'
                ])
            @endif

            <div class="row g-4 mb-4">
                <div class="col-md-3">
                    <div class="dashboard-card">
                        <h6>Students</h6>
                        <h2>{{ $studentCount }}</h2>
                    </div>
                </div>

                <div class="col-md-3">
                    <div class="dashboard-card">
                        <h6>STEM Engineers</h6>
                        <h2>{{ $teacherCount }}</h2>
                    </div>
                </div>

                <div class="col-md-3">
                    <div class="dashboard-card">
                        <h6>Classes</h6>
                        <h2>{{ $classCount }}</h2>
                    </div>
                </div>

                <div class="col-md-3">
                    <div class="dashboard-card">
                        <h6>Assessments</h6>
                        <h2>{{ $assessmentCount }}</h2>
                    </div>
                </div>
            </div>

            <div class="row g-4 mb-4">
                <div class="col-md-3">
                    <div class="dashboard-card">
                        <h6>AI Reviews</h6>
                        <h2>{{ $studentAiReviewCount }}</h2>
                        <small class="text-muted">Progress only</small>
                    </div>
                </div>

                <div class="col-md-3">
                    <div class="dashboard-card">
                        <h6>AI Reviews Passed</h6>
                        <h2>{{ $studentAiReviewPassedCount }}</h2>
                        <small class="text-muted">{{ number_format($studentAiReviewAverage, 2) }}% avg</small>
                    </div>
                </div>

                <div class="col-md-3">
                    <div class="dashboard-card">
                        <h6>Prep Quizzes</h6>
                        <h2>{{ $teacherAiPrepCount }}</h2>
                        <small class="text-muted">STEM Engineer readiness</small>
                    </div>
                </div>

                <div class="col-md-3">
                    <div class="dashboard-card">
                        <h6>Prep Passed</h6>
                        <h2>{{ $teacherAiPrepPassedCount }}</h2>
                        <small class="text-muted">{{ number_format($teacherAiPrepAverage, 2) }}% avg</small>
                    </div>
                </div>
            </div>

            <div class="row g-4 mb-4">

                <div class="col-md-3">
                    <div class="dashboard-card">
                        <h6>Today's Sessions</h6>
                        <h2>{{ $todayClassCount }}</h2>
                    </div>
                </div>

                <div class="col-md-3">
                    <div class="dashboard-card">
                        <h6>Completed Today</h6>
                        <h2>{{ $todayCompletedSessions }}</h2>
                    </div>
                </div>

                <div class="col-md-3">
                    <div class="dashboard-card">
                        <h6>Active Sessions</h6>
                        <h2>{{ $activeSessions }}</h2>
                    </div>
                </div>

                <div class="col-md-3">
                    <div class="dashboard-card">
                        <h6>Teaching Hours</h6>
                        <h2>{{ $totalTeachingHours }}</h2>
                    </div>
                </div>

            </div>

            <div class="row g-4 mb-4">

                <div class="col-md-3">
                    <div class="dashboard-card">
                        <h6>Active Plans</h6>
                        <h2>{{ $approvedTeachingPlans }}</h2>
                    </div>
                </div>

                <div class="col-md-3">
                    <div class="dashboard-card">
                        <h6>Inactive Plans</h6>
                        <h2>{{ $pendingTeachingPlans }}</h2>
                    </div>
                </div>

                <div class="col-md-3">
                    <div class="dashboard-card">
                        <h6>Released Lessons</h6>
                        <h2>{{ $contentReleasedCount }}</h2>
                    </div>
                </div>

                <div class="col-md-3">
                    <div class="dashboard-card">
                        <h6>Certificates Issued</h6>
                        <h2>{{ $certificateCount }}</h2>
                    </div>
                </div>

            </div>

            <div class="row g-4 mb-4">

                <div class="col-md-3">
                    <div class="dashboard-card">
                        <h6>Released Weeks</h6>
                        <h2>{{ $releasedTeachingWeeks }}</h2>
                    </div>
                </div>

                <div class="col-md-3">
                    <div class="dashboard-card">
                        <h6>Locked Weeks</h6>
                        <h2>{{ $lockedTeachingWeeks }}</h2>
                    </div>
                </div>

                <div class="col-md-3">
                    <div class="dashboard-card">
                        <h6>Completed Weeks</h6>
                        <h2>{{ $completedTeachingWeeks }}</h2>
                    </div>
                </div>

                <div class="col-md-3">
                    <div class="dashboard-card">
                        <h6>Pending Plan Items</h6>
                        <h2>{{ $pendingTeachingItems }}</h2>
                    </div>
                </div>

            </div>

            <div class="row g-4 mb-4">
                <div class="col-lg-6">
                    <div class="card shadow border-0 h-100">
                        <div class="card-body">
                            <h5 class="mb-3">Institute Overview</h5>
                            <div class="table-responsive">
                                <table class="table table-bordered table-hover align-middle">
                                    <thead class="table-light">
                                        <tr>
                                            <th>Institute</th>
                                            <th>Students</th>
                                            <th>STEM Engineers</th>
                                            <th>Classes</th>
                                            <th>Active Sessions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @forelse($instituteBreakdowns as $institute)
                                            <tr>
                                                <td>{{ $institute['institute'] }}</td>
                                                <td>{{ $institute['students'] }}</td>
                                                <td>{{ $institute['stem_engineers'] }}</td>
                                                <td>{{ $institute['classes'] }}</td>
                                                <td>{{ $institute['active_sessions'] }}</td>
                                            </tr>
                                        @empty
                                            <tr><td colspan="5" class="text-center text-muted">No institute data available.</td></tr>
                                        @endforelse
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-lg-6">
                    <div class="card shadow border-0 h-100">
                        <div class="card-body">
                            <h5 class="mb-3">STEM Engineer Performance</h5>
                            <div class="table-responsive">
                                <table class="table table-bordered table-hover align-middle">
                                    <thead class="table-light">
                                        <tr>
                                            <th>Institute</th>
                                            <th>STEM Engineer</th>
                                            <th>Sessions</th>
                                            <th>Completed</th>
                                            <th>Partial</th>
                                            <th>Hours</th>
                                            <th>AI Prep</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @forelse($teacherPerformance as $teacher)
                                            <tr>
                                                <td>{{ $teacher['institute'] }}</td>
                                                <td>{{ $teacher['name'] }}</td>
                                                <td>{{ $teacher['sessions'] }}</td>
                                                <td>{{ $teacher['completed_sessions'] }}</td>
                                                <td>{{ $teacher['partial_sessions'] }}</td>
                                                <td>{{ $teacher['hours'] }}</td>
                                                <td>
                                                    {{ $teacher['ai_prep'] }} total |
                                                    {{ $teacher['ai_prep_passed'] }} passed |
                                                    {{ number_format($teacher['ai_prep_average'], 2) }}%
                                                </td>
                                            </tr>
                                        @empty
                                            <tr><td colspan="7" class="text-center text-muted">No STEM Engineer session data available.</td></tr>
                                        @endforelse
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card shadow border-0 mb-4">
                <div class="card-body">
                    <h5 class="mb-3">Class-wise Tracking</h5>
                    <div class="table-responsive">
                        <table class="table table-bordered table-hover align-middle">
                            <thead class="table-light">
                                <tr>
                                    <th>Institute</th>
                                    <th>Class</th>
                                    <th>Students</th>
                                    <th>Sessions</th>
                                    <th>Active Plans</th>
                                    <th>AI Reviews</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($classBreakdowns->groupBy('institute') as $instituteName => $classes)
                                    <tr class="table-primary">
                                        <td colspan="6" class="fw-semibold">{{ $instituteName }} · {{ $classes->count() }} class section{{ $classes->count() == 1 ? '' : 's' }}</td>
                                    </tr>
                                    @foreach($classes as $class)
                                        <tr>
                                            <td>{{ $class['institute'] }}</td>
                                            <td>{{ $class['class_label'] }}</td>
                                            <td>{{ $class['students'] }}</td>
                                            <td>{{ $class['sessions'] }}</td>
                                            <td>{{ $class['active_plans'] }}</td>
                                            <td>
                                                {{ $class['ai_reviews'] }} total |
                                                {{ $class['ai_reviews_passed'] }} passed |
                                                {{ number_format($class['ai_review_average'], 2) }}%
                                            </td>
                                        </tr>
                                    @endforeach
                                @empty
                                    <tr><td colspan="6" class="text-center text-muted">No class tracking data available.</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <div class="card shadow border-0">

                <div class="card-body">

                    <h5 class="mb-4">
                        Daily Operations Summary
                    </h5>

                    <table class="table table-bordered table-hover align-middle">

                        <thead class="table-light">

                            <tr>
                                <th>Metric</th>
                                <th>Value</th>
                                <th>Status</th>
                            </tr>

                        </thead>

                        <tbody>

                            <tr>
                                <td>Classes Scheduled Today</td>
                                <td>{{ $todayClassCount }}</td>
                                <td>
                                    <span class="badge bg-primary">
                                        Scheduled
                                    </span>
                                </td>
                            </tr>

                            <tr>
                                <td>Sessions Completed Today</td>
                                <td>{{ $todayCompletedSessions }}</td>
                                <td>
                                    <span class="badge bg-success">
                                        Completed
                                    </span>
                                </td>
                            </tr>

                            <tr>
                                <td>Active Live Sessions</td>
                                <td>{{ $activeSessions }}</td>
                                <td>
                                    <span class="badge bg-warning text-dark">
                                        Live
                                    </span>
                                </td>
                            </tr>

                            <tr>
                                <td>Total Teaching Hours Delivered</td>
                                <td>{{ $totalTeachingHours }}</td>
                                <td>
                                    <span class="badge bg-info">
                                        Tracked
                                    </span>
                                </td>
                            </tr>

                            <tr>
                                <td>Released Learning Content</td>
                                <td>{{ $contentReleasedCount }}</td>
                                <td>
                                    <span class="badge bg-success">
                                        Available
                                    </span>
                                </td>
                            </tr>

                            <tr>
                                <td>Certificates Issued</td>
                                <td>{{ $certificateCount }}</td>
                                <td>
                                    <span class="badge bg-success">
                                        Issued
                                    </span>
                                </td>
                            </tr>

                            <tr>
                                <td>AI Student Reviews</td>
                                <td>{{ $studentAiReviewCount }} total | {{ $studentAiReviewPassedCount }} passed | {{ number_format($studentAiReviewAverage, 2) }}% avg</td>
                                <td>
                                    <span class="badge bg-info">
                                        AI Progress Only
                                    </span>
                                </td>
                            </tr>

                            <tr>
                                <td>AI STEM Engineer Prep</td>
                                <td>{{ $teacherAiPrepCount }} total | {{ $teacherAiPrepPassedCount }} passed | {{ number_format($teacherAiPrepAverage, 2) }}% avg</td>
                                <td>
                                    <span class="badge bg-info">
                                        AI Progress Only
                                    </span>
                                </td>
                            </tr>

                        </tbody>

                    </table>

                </div>

            </div>

            <div class="card shadow border-0 mt-4">
                <div class="card-body">

                    <h5 class="mb-4">Live LMS Report Summary</h5>

                    <table class="table table-bordered table-hover align-middle">
                        <thead class="table-light">
                            <tr>
                                <th>Sl. No</th>
                                <th>Report Area</th>
                                <th>Metric</th>
                                <th>Current Value</th>
                                <th>Status</th>
                            </tr>
                        </thead>

                        <tbody>
                            <tr>
                                <td>1</td>
                                <td>Students</td>
                                <td>Total Registered Students</td>
                                <td>{{ $studentCount }}</td>
                                <td><span class="badge bg-primary">Live</span></td>
                            </tr>

                            <tr>
                                <td>2</td>
                                <td>STEM Engineers</td>
                                <td>Total STEM Engineers</td>
                                <td>{{ $teacherCount }}</td>
                                <td><span class="badge bg-primary">Live</span></td>
                            </tr>

                            <tr>
                                <td>3</td>
                                <td>Classes</td>
                                <td>Total Classes</td>
                                <td>{{ $classCount }}</td>
                                <td><span class="badge bg-info">Live</span></td>
                            </tr>

                            <tr>
                                <td>4</td>
                                <td>Content</td>
                                <td>Total Uploaded Content</td>
                                <td>{{ $contentCount }}</td>
                                <td><span class="badge bg-info">Live</span></td>
                            </tr>

                            <tr>
                                <td>5</td>
                                <td>Assessments</td>
                                <td>Total Assessments</td>
                                <td>{{ $assessmentCount }}</td>
                                <td><span class="badge bg-warning text-dark">Live</span></td>
                            </tr>

                            <tr>
                                <td>6</td>
                                <td>Assessment Results</td>
                                <td>Completed Results</td>
                                <td>{{ $completedResults }}</td>
                                <td><span class="badge bg-success">Completed</span></td>
                            </tr>

                            <tr>
                                <td>7</td>
                                <td>Assessment Results</td>
                                <td>Pending Manual Review</td>
                                <td>{{ $pendingReviewResults }}</td>
                                <td><span class="badge bg-warning text-dark">Pending</span></td>
                            </tr>

                            <tr>
                                <td>8</td>
                                <td>Performance</td>
                                <td>Average Score</td>
                                <td>{{ number_format($averageScore, 2) }}%</td>
                                <td><span class="badge bg-success">Calculated</span></td>
                            </tr>

                            <tr>
                                <td>9</td>
                                <td>Certificates</td>
                                <td>Total Certificates</td>
                                <td>{{ $certificateCount }}</td>
                                <td><span class="badge bg-success">Live</span></td>
                            </tr>

                            <tr>
                                <td>10</td>
                                <td>Class Sessions</td>
                                <td>Total Sessions Conducted</td>
                                <td>{{ $classSessionCount }}</td>
                                <td><span class="badge bg-secondary">Tracked</span></td>
                            </tr>

                            <tr>
                                <td>11</td>
                                <td>AI Progress</td>
                                <td>Student AI Reviews</td>
                                <td>{{ $studentAiReviewCount }} total | {{ $studentAiReviewPassedCount }} passed | {{ number_format($studentAiReviewAverage, 2) }}% avg</td>
                                <td><span class="badge bg-info">Progress Only</span></td>
                            </tr>

                            <tr>
                                <td>12</td>
                                <td>AI Progress</td>
                                <td>STEM Engineer Prep Quizzes</td>
                                <td>{{ $teacherAiPrepCount }} total | {{ $teacherAiPrepPassedCount }} passed | {{ number_format($teacherAiPrepAverage, 2) }}% avg</td>
                                <td><span class="badge bg-info">Progress Only</span></td>
                            </tr>
                        </tbody>
                    </table>

                </div>
            </div>

        </div>
    </div>
</div>

@endsection
