@extends('layouts.app')

@section('content')

<div class="container-fluid">
    <div class="row">

        @include('layouts.sidebar')

        <div class="col-md-10 col-lg-10 p-4">

            <div class="page-header mb-4">
                <h2 class="mb-1">Analytics Dashboard</h2>

                <p class="text-muted mb-0">
                    View LMS performance, certificates, assessments, and badge insights.
                </p>
            </div>

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

                                                <span class="badge
                                                    @if($result->badge == 'Gold')
                                                        bg-warning text-dark
                                                    @elseif($result->badge == 'Silver')
                                                        bg-secondary
                                                    @elseif($result->badge == 'Bronze')
                                                        bg-danger
                                                    @else
                                                        bg-primary
                                                    @endif">

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
