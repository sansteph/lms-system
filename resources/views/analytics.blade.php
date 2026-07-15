@extends('layouts.app')

@section('content')

<div class="container-fluid">
    <div class="row">

        @include('layouts.sidebar')

        <div class="col-md-10 col-lg-10 p-4">

            <div class="page-header d-flex justify-content-between align-items-center flex-wrap gap-3 mb-4">
                <div>
                    <h2 class="mb-1">Analytics Dashboard</h2>

                    <p class="text-muted mb-0">
                        View LMS performance, certificates, assessments, and badge insights.
                    </p>
                </div>

                <div class="d-flex gap-2 flex-wrap">
                    <form method="POST" action="{{ route('admin.analytics.ai-insights') }}">
                        @csrf
                        <button type="submit" class="btn btn-primary btn-sm">
                            <i class="fa fa-wand-magic-sparkles me-1"></i>
                            Generate AI Insights
                        </button>
                    </form>
                    <form method="POST" action="{{ route('admin.analytics.ai-insights.download') }}">
                        @csrf
                        <button type="submit" class="btn btn-outline-primary btn-sm">
                            <i class="fa fa-file-pdf me-1"></i>
                            Download AI PDF
                        </button>
                    </form>
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
                <div class="card shadow border-0 mb-4">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-start flex-wrap gap-2 mb-3">
                            <div>
                                <h5 class="mb-1">AI Generated Analytics Insights</h5>
                                <p class="text-muted mb-0">
                                    Generated from current analytics data{{ isset($aiInsights['generated_at']) ? ' at ' . $aiInsights['generated_at'] : '' }}.
                                </p>
                            </div>
                            @if(!empty($aiInsights['model']))
                                <span class="badge bg-info">{{ $aiInsights['model'] }}</span>
                            @endif
                        </div>

                        <p class="mb-3">{{ $aiInsights['summary'] ?? 'No summary returned.' }}</p>

                        <div class="row g-3">
                            <div class="col-md-4">
                                <h6>Highlights</h6>
                                <ul class="mb-0">
                                    @forelse($aiInsights['highlights'] ?? [] as $item)
                                        <li>{{ $item }}</li>
                                    @empty
                                        <li>No highlights returned.</li>
                                    @endforelse
                                </ul>
                            </div>
                            <div class="col-md-4">
                                <h6>Risks</h6>
                                <ul class="mb-0">
                                    @forelse($aiInsights['risks'] ?? [] as $item)
                                        <li>{{ $item }}</li>
                                    @empty
                                        <li>No risks returned.</li>
                                    @endforelse
                                </ul>
                            </div>
                            <div class="col-md-4">
                                <h6>Recommendations</h6>
                                <ul class="mb-0">
                                    @forelse($aiInsights['recommendations'] ?? [] as $item)
                                        <li>{{ $item }}</li>
                                    @empty
                                        <li>No recommendations returned.</li>
                                    @endforelse
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>
            @endif

            <div class="row g-4 mb-4">

                <div class="col-md-3">

                    <div class="dashboard-card">

                        <h6>Total Students</h6>

                        <h2>{{ $studentCount }}</h2>

                    </div>

                </div>

                <div class="col-md-3">

                    <div class="dashboard-card">

                        <h6>Total Assessments</h6>

                        <h2>{{ $assessmentCount }}</h2>

                    </div>

                </div>

                <div class="col-md-3">

                    <div class="dashboard-card">

                        <h6>Certificates</h6>

                        <h2>{{ $certificateCount }}</h2>

                    </div>

                </div>

                <div class="col-md-3">

                    <div class="dashboard-card">

                        <h6>Average Score</h6>

                        <h2>{{ number_format($averageScore, 1) }}%</h2>

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

            <div class="row g-4">

                <div class="col-md-7">

                    <div class="card shadow border-0 h-100">

                        <div class="card-body">

                            <h5 class="mb-4">
                                Badge Distribution
                            </h5>

                            <canvas id="badgeChart" height="130"></canvas>

                        </div>

                    </div>

                </div>

                <div class="col-md-5">

                    <div class="card shadow border-0 h-100">

                        <div class="card-body">

                            <h5 class="mb-4">
                                Student Participation
                            </h5>

                            <canvas id="participationChart" height="160"></canvas>

                        </div>

                    </div>

                </div>

            </div>

            <div class="row g-4 mt-1">

                <div class="col-md-6">

                    <div class="card shadow border-0 h-100">

                        <div class="card-body">

                            <h5 class="mb-4">
                                Pass vs Needs Improvement
                            </h5>

                            <canvas id="performanceChart" height="150"></canvas>

                        </div>

                    </div>

                </div>

                <div class="col-md-6">

                    <div class="card shadow border-0 h-100">

                        <div class="card-body">

                            <h5 class="mb-4">
                                Top 5 Performers
                            </h5>

                            <table class="table table-bordered table-hover align-middle">

                                <thead>

                                    <tr>

                                        <th>Rank</th>
                                        <th>Student</th>
                                        <th>Percentage</th>
                                        <th>Badge</th>

                                    </tr>

                                </thead>

                                <tbody>

                                    @forelse($topPerformers as $index => $result)

                                        <tr>

                                            <td>
                                                {{ $index + 1 }}
                                            </td>

                                            <td>
                                                {{ $result->student->name ?? 'Student Deleted' }}
                                            </td>

                                            <td>
                                                {{ number_format($result->percentage, 1) }}%
                                            </td>

                                            <td>

                                                <span class="badge {{ $result->badge == 'Gold' ? 'bg-warning text-dark' : ($result->badge == 'Silver' ? 'bg-secondary' : ($result->badge == 'Bronze' ? 'bg-danger' : 'bg-primary')) }}">

                                                    {{ $result->badge ?? 'No Badge' }}

                                                </span>

                                            </td>

                                        </tr>

                                    @empty

                                        <tr>

                                            <td colspan="4"
                                                class="text-center text-muted">

                                                No performance data available.

                                            </td>

                                        </tr>

                                    @endforelse

                                </tbody>

                            </table>

                        </div>

                    </div>

                </div>

            </div>

            <div class="row g-4 mt-1">
                <div class="col-lg-6">
                    <div class="card shadow border-0 h-100">
                        <div class="card-body">
                            <h5 class="mb-4">STEM Engineer Performance</h5>
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
                                        @forelse($teacherPerformanceMetrics as $teacher)
                                            <tr>
                                                <td>{{ $teacher['institute'] }}</td>
                                                <td>{{ $teacher['name'] }}</td>
                                                <td>{{ $teacher['total_sessions'] }}</td>
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
                                            <tr><td colspan="7" class="text-center text-muted">No STEM Engineer performance data available.</td></tr>
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
                            <h5 class="mb-4">Class Performance</h5>
                            <div class="table-responsive">
                                <table class="table table-bordered table-hover align-middle">
                                    <thead class="table-light">
                                        <tr>
                                            <th>Institute</th>
                                            <th>Class</th>
                                            <th>Students</th>
                                            <th>Completed Results</th>
                                            <th>Average Score</th>
                                            <th>AI Reviews</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @forelse($classPerformanceMetrics->groupBy('institute') as $instituteName => $classes)
                                            <tr class="table-primary">
                                                <td colspan="6" class="fw-semibold">{{ $instituteName }}</td>
                                            </tr>
                                            @foreach($classes as $class)
                                                <tr>
                                                    <td>{{ $class['institute'] }}</td>
                                                    <td>{{ $class['class_label'] }}</td>
                                                    <td>{{ $class['students'] }}</td>
                                                    <td>{{ $class['completed_results'] }}</td>
                                                    <td>{{ number_format($class['average_score'], 1) }}%</td>
                                                    <td>
                                                        {{ $class['ai_reviews'] }} total |
                                                        {{ $class['ai_reviews_passed'] }} passed |
                                                        {{ number_format($class['ai_review_average'], 2) }}%
                                                    </td>
                                                </tr>
                                            @endforeach
                                        @empty
                                            <tr><td colspan="6" class="text-center text-muted">No class performance data available.</td></tr>
                                        @endforelse
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card shadow border-0 mt-4">

                <div class="card-body">

                    <h5 class="mb-4">
                        Recent Assessment Activity
                    </h5>

                    @forelse($recentResults as $result)

                        <div class="d-flex justify-content-between align-items-center border-bottom py-3">

                            <div>

                                <strong>
                                    {{ $result->student->name ?? 'Student Deleted' }}
                                </strong>

                                <span class="text-muted">
                                    completed
                                </span>

                                <strong>
                                    {{ $result->assessment->assessment_title ?? 'Assessment Deleted' }}
                                </strong>

                            </div>

                            <div>

                                <span class="badge bg-primary">

                                    {{ number_format($result->percentage, 1) }}%

                                </span>

                            </div>

                        </div>

                    @empty

                        <p class="text-muted mb-0">
                            No recent activity available.
                        </p>

                    @endforelse

                </div>

            </div>
            <div class="card shadow border-0 mt-4">

                <div class="card-body">

                    <h5 class="mb-4">
                        Assessment-wise Average Scores
                    </h5>

                    <canvas id="assessmentAverageChart" height="110"></canvas>

                </div>

            </div>

        </div>

    </div>
</div>

@php
    $analyticsChartData = [
        'badges' => [(int) $goldCount, (int) $silverCount, (int) $bronzeCount],
        'participation' => [(int) $attemptedCount, (int) $notAttemptedCount],
        'performance' => [(int) $passedCount, (int) $failedCount],
        'assessmentLabels' => $assessmentAverages
            ->map(fn ($item) => $item->assessment->assessment_title ?? 'Deleted')
            ->values(),
        'assessmentScores' => $assessmentAverages
            ->map(fn ($item) => round((float) $item->average_percentage, 1))
            ->values(),
    ];
@endphp

<div id="analyticsChartData"
     data-chart-data="{{ e(json_encode($analyticsChartData)) }}">
</div>

<script>

    const analyticsChartDataElement = document.getElementById('analyticsChartData');
    const analyticsChartData = JSON.parse(analyticsChartDataElement.dataset.chartData || '{}');

    const badgeChart = document.getElementById('badgeChart');

    if (badgeChart) {

        new Chart(badgeChart, {

        type: 'bar',

        data: {

            labels: ['Gold', 'Silver', 'Bronze'],

            datasets: [{

                label: 'Badge Distribution',

                data: analyticsChartData.badges || [],

                backgroundColor: [
                    '#facc15',
                    '#94a3b8',
                    '#fb7185'
                ],

                borderRadius: 10,
                borderSkipped: false

            }]

        },

        options: {

            responsive: true,

            plugins: {

                legend: {
                    display: false
                }

            },

            scales: {

                y: {
                    beginAtZero: true,
                    ticks: {
                        precision: 0
                    }
                }

            }

        }

        });

    }

    const participationChart = document.getElementById('participationChart');

    if (participationChart) {

        new Chart(participationChart, {

        type: 'doughnut',

        data: {

            labels: ['Attempted', 'Not Attempted'],

            datasets: [{

                data: analyticsChartData.participation || [],

                backgroundColor: [
                    '#2563eb',
                    '#e2e8f0'
                ],

                borderWidth: 0

            }]

        },

        options: {

            responsive: true,
            cutout: '70%'

        }

        });

    }

    const performanceChart = document.getElementById('performanceChart');

    if (performanceChart) {

        new Chart(performanceChart, {

        type: 'pie',

        data: {

            labels: ['Passed', 'Needs Improvement'],

            datasets: [{

                data: analyticsChartData.performance || [],

                backgroundColor: [
                    '#22c55e',
                    '#ef4444'
                ],

                borderWidth: 0

            }]

        },

        options: {
            responsive: true
        }

        });

    }

    const assessmentAverageChart =
        document.getElementById('assessmentAverageChart');

    if (assessmentAverageChart) {

        new Chart(assessmentAverageChart, {

        type: 'line',

        data: {

            labels: analyticsChartData.assessmentLabels || [],

            datasets: [{

                label: 'Average Score %',

                data: analyticsChartData.assessmentScores || [],

                borderColor: '#2563eb',
                backgroundColor: 'rgba(37,99,235,0.1)',
                fill: true,
                tension: 0.4

            }]

        },

        options: {

            responsive: true,

            scales: {

                y: {
                    beginAtZero: true,
                    max: 100
                }

            }

        }

        });

    }
    
</script>

@endsection
