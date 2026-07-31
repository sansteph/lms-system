@extends('layouts.app')

@section('content')

<style>
    .student-dashboard-shell {
        display: grid;
        gap: 22px;
    }

    .student-dashboard-hero {
        position: relative;
        overflow: hidden;
        padding: 28px 30px;
        border: 1px solid #173f78;
        border-radius: 8px;
        background: #0f2f63;
        color: #ffffff;
        box-shadow: 0 18px 42px rgba(15, 47, 99, 0.18);
    }

    .student-dashboard-hero::after {
        content: "";
        position: absolute;
        inset: 12px;
        border: 1px solid rgba(255, 255, 255, 0.11);
        pointer-events: none;
    }

    .student-dashboard-hero-content {
        position: relative;
        z-index: 1;
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 24px;
    }

    .student-dashboard-kicker {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        margin-bottom: 10px;
        color: #b9dcff;
        font-size: 12px;
        font-weight: 900;
        text-transform: uppercase;
    }

    .student-dashboard-hero h2 {
        margin: 0 0 8px;
        color: #ffffff;
        font-size: 32px;
        font-weight: 950;
    }

    .student-dashboard-hero p {
        margin: 0;
        color: rgba(255, 255, 255, 0.78);
        line-height: 1.65;
    }

    .student-dashboard-identity {
        min-width: 220px;
        padding: 14px 16px;
        border: 1px solid rgba(255, 255, 255, 0.16);
        border-radius: 8px;
        background: rgba(255, 255, 255, 0.08);
    }

    .student-dashboard-identity span,
    .student-dashboard-identity strong {
        display: block;
    }

    .student-dashboard-identity span {
        margin-bottom: 3px;
        color: #b9dcff;
        font-size: 11px;
        font-weight: 800;
        text-transform: uppercase;
    }

    .student-dashboard-identity strong {
        color: #ffffff;
        font-size: 15px;
    }

    .student-dashboard-metrics {
        display: grid;
        grid-template-columns: repeat(4, minmax(0, 1fr));
        gap: 14px;
    }

    .student-dashboard-metric,
    .student-dashboard-panel,
    .student-dashboard-action {
        border: 1px solid #e2e8f0;
        border-radius: 8px;
        background: #ffffff;
        box-shadow: 0 12px 28px rgba(15, 23, 42, 0.06);
    }

    .student-dashboard-metric {
        min-height: 142px;
        padding: 18px;
    }

    .student-dashboard-metric-head {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 10px;
        margin-bottom: 16px;
    }

    .student-dashboard-metric-icon {
        width: 42px;
        height: 42px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        border-radius: 8px;
        font-size: 17px;
    }

    .student-dashboard-metric-icon.blue { color: #1d4ed8; background: #eaf2ff; }
    .student-dashboard-metric-icon.green { color: #15803d; background: #eaf8ef; }
    .student-dashboard-metric-icon.orange { color: #c2410c; background: #fff3e6; }
    .student-dashboard-metric-icon.gold { color: #a16207; background: #fff8d9; }

    .student-dashboard-metric small {
        color: #64748b;
        font-size: 11px;
        font-weight: 900;
        text-transform: uppercase;
    }

    .student-dashboard-metric strong {
        display: block;
        margin-bottom: 7px;
        color: #071124;
        font-size: 31px;
        font-weight: 950;
        line-height: 1;
    }

    .student-dashboard-metric p {
        margin: 0;
        color: #64748b;
        font-size: 13px;
    }

    .student-dashboard-grid {
        display: grid;
        grid-template-columns: minmax(0, 1.45fr) minmax(280px, 0.55fr);
        gap: 18px;
        align-items: start;
    }

    .student-dashboard-panel {
        padding: 20px;
    }

    .student-dashboard-panel-head {
        display: flex;
        align-items: flex-start;
        justify-content: space-between;
        gap: 14px;
        margin-bottom: 16px;
    }

    .student-dashboard-panel h4 {
        margin: 0 0 4px;
        color: #071124;
        font-size: 20px;
        font-weight: 950;
    }

    .student-dashboard-panel-head p {
        margin: 0;
        color: #64748b;
        font-size: 13px;
    }

    .student-dashboard-progress {
        margin-bottom: 18px;
        padding: 15px;
        border: 1px solid #dce7f5;
        border-radius: 8px;
        background: #f8fbff;
    }

    .student-dashboard-progress-copy {
        display: flex;
        justify-content: space-between;
        gap: 12px;
        margin-bottom: 9px;
        color: #334155;
        font-size: 13px;
        font-weight: 800;
    }

    .student-dashboard-progress-track {
        display: block;
        width: 100%;
        height: 8px;
        overflow: hidden;
        border: 0;
        border-radius: 4px;
        background: #dce7f5;
        appearance: none;
        -webkit-appearance: none;
    }

    .student-dashboard-progress-track::-webkit-progress-bar {
        border-radius: 4px;
        background: #dce7f5;
    }

    .student-dashboard-progress-track::-webkit-progress-value {
        border-radius: 4px;
        background: #1677e8;
    }

    .student-dashboard-progress-track::-moz-progress-bar {
        border-radius: 4px;
        background: #1677e8;
    }

    .student-dashboard-table {
        margin: 0;
    }

    .student-dashboard-table th {
        border-bottom-width: 1px;
        color: #64748b;
        font-size: 11px;
        font-weight: 900;
        text-transform: uppercase;
        white-space: nowrap;
    }

    .student-dashboard-table td {
        color: #334155;
        vertical-align: middle;
    }

    .student-dashboard-assessment-name {
        color: #0f172a;
        font-weight: 850;
    }

    .student-dashboard-status {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        padding: 5px 9px;
        border-radius: 999px;
        background: #fff6dc;
        color: #9a6700;
        font-size: 12px;
        font-weight: 850;
        white-space: nowrap;
    }

    .student-dashboard-empty {
        padding: 34px 18px;
        text-align: center;
        color: #64748b;
    }

    .student-dashboard-empty i {
        display: block;
        margin-bottom: 10px;
        color: #94a3b8;
        font-size: 25px;
    }

    .student-dashboard-actions {
        display: grid;
        gap: 10px;
    }

    .student-dashboard-action {
        display: flex;
        align-items: center;
        gap: 12px;
        padding: 14px;
        color: #0f172a;
        text-decoration: none;
        transition: transform .18s ease, border-color .18s ease, box-shadow .18s ease;
    }

    .student-dashboard-action:hover {
        color: #0f172a;
        border-color: #b8d5fa;
        transform: translateY(-2px);
        box-shadow: 0 15px 30px rgba(15, 59, 122, 0.1);
    }

    .student-dashboard-action-icon {
        width: 40px;
        height: 40px;
        flex: 0 0 40px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        border-radius: 8px;
        background: #eef6ff;
        color: #0f3b7a;
    }

    .student-dashboard-action strong,
    .student-dashboard-action small {
        display: block;
    }

    .student-dashboard-action strong {
        margin-bottom: 2px;
        font-size: 14px;
        font-weight: 900;
    }

    .student-dashboard-action small {
        color: #64748b;
        line-height: 1.4;
    }

    .student-dashboard-action-arrow {
        margin-left: auto;
        color: #94a3b8;
    }

    @media (max-width: 1199.98px) {
        .student-dashboard-metrics {
            grid-template-columns: repeat(2, minmax(0, 1fr));
        }

        .student-dashboard-grid {
            grid-template-columns: 1fr;
        }
    }

    @media (max-width: 767.98px) {
        .student-dashboard-page {
            padding: 18px !important;
        }

        .student-dashboard-hero {
            padding: 24px 22px;
        }

        .student-dashboard-hero-content {
            align-items: flex-start;
            flex-direction: column;
        }

        .student-dashboard-hero h2 {
            font-size: 27px;
        }

        .student-dashboard-identity {
            width: 100%;
            min-width: 0;
        }

        .student-dashboard-metrics {
            grid-template-columns: 1fr;
        }
    }
</style>

<div class="container-fluid">
    <div class="row">
        @include('layouts.student-sidebar')

        <main class="col-md-10 col-lg-10 p-4 student-dashboard-page">
            <div class="student-dashboard-shell">
                <section class="student-dashboard-hero">
                    <div class="student-dashboard-hero-content">
                        <div>
                            <div class="student-dashboard-kicker">
                                <i class="fa-solid fa-graduation-cap"></i>
                                Student Workspace
                            </div>
                            <h2>Welcome back, {{ $studentName }}</h2>
                            <p>Continue your learning, stay ready for upcoming assessments, and track your achievements.</p>
                        </div>

                        <div class="student-dashboard-identity">
                            <span>Student ID</span>
                            <strong>{{ $studentCode }}</strong>
                            <span class="mt-2">Class</span>
                            <strong>{{ $assignedClass ?: 'Not assigned' }}</strong>
                        </div>
                    </div>
                </section>

                <section class="student-dashboard-metrics" aria-label="Learning overview">
                    <article class="student-dashboard-metric">
                        <div class="student-dashboard-metric-head">
                            <small>Assessments</small>
                            <span class="student-dashboard-metric-icon blue"><i class="fa-solid fa-file-pen"></i></span>
                        </div>
                        <strong>{{ $totalAssessmentCount }}</strong>
                        <p>Total assessments assigned to your class.</p>
                    </article>

                    <article class="student-dashboard-metric">
                        <div class="student-dashboard-metric-head">
                            <small>Completed</small>
                            <span class="student-dashboard-metric-icon green"><i class="fa-solid fa-circle-check"></i></span>
                        </div>
                        <strong>{{ $completedAssessmentCount }}</strong>
                        <p>{{ $assessmentProgress }}% of assigned assessments completed.</p>
                    </article>

                    <article class="student-dashboard-metric">
                        <div class="student-dashboard-metric-head">
                            <small>Average Score</small>
                            <span class="student-dashboard-metric-icon orange"><i class="fa-solid fa-chart-line"></i></span>
                        </div>
                        <strong>{{ number_format($averagePercentage, 1) }}%</strong>
                        <p>Average across finalized assessment results.</p>
                    </article>

                    <article class="student-dashboard-metric">
                        <div class="student-dashboard-metric-head">
                            <small>Badges Earned</small>
                            <span class="student-dashboard-metric-icon gold"><i class="fa-solid fa-medal"></i></span>
                        </div>
                        <strong>{{ $badgeCount }}</strong>
                        <p>Recognition earned from evaluated results.</p>
                    </article>
                </section>

                <section class="student-dashboard-grid">
                    <div class="student-dashboard-panel">
                        <div class="student-dashboard-panel-head">
                            <div>
                                <h4>Upcoming Assessments</h4>
                                <p>Your next scheduled monthly and annual assessments.</p>
                            </div>
                            <a href="{{ route('student.assessment') }}" class="btn btn-sm btn-outline-primary">View all</a>
                        </div>

                        <div class="student-dashboard-progress">
                            <div class="student-dashboard-progress-copy">
                                <span>Assessment progress</span>
                                <span>{{ $completedAssessmentCount }} of {{ $totalAssessmentCount }}</span>
                            </div>
                            <progress class="student-dashboard-progress-track" value="{{ $assessmentProgress }}" max="100" aria-label="Assessment completion">{{ $assessmentProgress }}%</progress>
                        </div>

                        <div class="table-responsive">
                            <table class="table student-dashboard-table align-middle">
                                <thead>
                                    <tr>
                                        <th>Assessment</th>
                                        <th>Category</th>
                                        <th>Date</th>
                                        <th>Duration</th>
                                        <th>Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($upcomingAssessments as $assessment)
                                        <tr>
                                            <td class="student-dashboard-assessment-name">{{ $assessment->assessment_title }}</td>
                                            <td>{{ $assessment->assessment_category ?? 'Monthly' }}</td>
                                            <td>{{ $assessment->assessment_date ? \Carbon\Carbon::parse($assessment->assessment_date)->format('d M Y') : 'Not set' }}</td>
                                            <td>{{ $assessment->duration }} min</td>
                                            <td>
                                                <span class="student-dashboard-status">
                                                    <i class="fa-regular fa-clock"></i>
                                                    Scheduled
                                                </span>
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="5">
                                                <div class="student-dashboard-empty">
                                                    <i class="fa-regular fa-calendar-check"></i>
                                                    No upcoming assessments are scheduled for your class.
                                                </div>
                                            </td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <aside class="student-dashboard-panel">
                        <div class="student-dashboard-panel-head">
                            <div>
                                <h4>Continue Learning</h4>
                                <p>Jump back into your most-used areas.</p>
                            </div>
                        </div>

                        <div class="student-dashboard-actions">
                            <a href="{{ route('student.content') }}" class="student-dashboard-action">
                                <span class="student-dashboard-action-icon"><i class="fa-solid fa-book-open"></i></span>
                                <span>
                                    <strong>Learning Content</strong>
                                    <small>Open released lessons and AI reviews.</small>
                                </span>
                                <i class="fa-solid fa-chevron-right student-dashboard-action-arrow"></i>
                            </a>

                            <a href="{{ route('student.assessment') }}" class="student-dashboard-action">
                                <span class="student-dashboard-action-icon"><i class="fa-solid fa-clipboard-check"></i></span>
                                <span>
                                    <strong>Take Assessment</strong>
                                    <small>{{ $pendingAssessmentCount }} assessment(s) currently available.</small>
                                </span>
                                <i class="fa-solid fa-chevron-right student-dashboard-action-arrow"></i>
                            </a>

                            <a href="{{ route('student.history') }}" class="student-dashboard-action">
                                <span class="student-dashboard-action-icon"><i class="fa-solid fa-chart-column"></i></span>
                                <span>
                                    <strong>Assessment History</strong>
                                    <small>Review finalized scores and feedback.</small>
                                </span>
                                <i class="fa-solid fa-chevron-right student-dashboard-action-arrow"></i>
                            </a>

                            <a href="{{ route('student.badges') }}" class="student-dashboard-action">
                                <span class="student-dashboard-action-icon"><i class="fa-solid fa-trophy"></i></span>
                                <span>
                                    <strong>Achievements</strong>
                                    <small>View badges, certificates, and projects.</small>
                                </span>
                                <i class="fa-solid fa-chevron-right student-dashboard-action-arrow"></i>
                            </a>
                        </div>
                    </aside>
                </section>
            </div>
        </main>
    </div>
</div>

@endsection
