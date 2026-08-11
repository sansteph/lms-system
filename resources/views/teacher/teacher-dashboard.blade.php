@extends('layouts.app')

@section('content')

<style>
    .teacher-dashboard-shell {
        display: grid;
        gap: 24px;
    }

    .teacher-dashboard-hero {
        position: relative;
        overflow: hidden;
        border-radius: 22px;
        padding: 30px;
        color: #ffffff;
        background:
            linear-gradient(135deg, rgba(15, 59, 122, 0.96), rgba(13, 31, 75, 0.98)),
            radial-gradient(circle at top right, rgba(56, 189, 248, 0.32), transparent 34%);
        box-shadow: 0 22px 55px rgba(15, 23, 42, 0.16);
    }

    .teacher-dashboard-hero::after {
        content: "";
        position: absolute;
        right: -80px;
        top: -110px;
        width: 280px;
        height: 280px;
        border-radius: 999px;
        border: 42px solid rgba(255, 255, 255, 0.08);
    }

    .teacher-dashboard-hero > * {
        position: relative;
        z-index: 1;
    }

    .teacher-dashboard-kicker {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        padding: 8px 12px;
        border-radius: 999px;
        background: rgba(255, 255, 255, 0.12);
        border: 1px solid rgba(255, 255, 255, 0.16);
        font-weight: 800;
        font-size: 13px;
        margin-bottom: 14px;
    }

    .teacher-dashboard-hero h2 {
        font-size: 34px;
        font-weight: 950;
        margin-bottom: 8px;
        color: #ffffff;
    }

    .teacher-dashboard-hero p {
        max-width: 760px;
        margin: 0;
        color: rgba(255, 255, 255, 0.78);
        font-size: 16px;
        line-height: 1.7;
    }

    .teacher-dashboard-hero-actions {
        display: flex;
        flex-wrap: wrap;
        gap: 10px;
        margin-top: 22px;
    }

    .teacher-dashboard-hero-actions .btn {
        border-radius: 12px;
        padding: 10px 14px;
        font-weight: 800;
    }

    .teacher-dashboard-metrics {
        display: grid;
        grid-template-columns: repeat(4, minmax(0, 1fr));
        gap: 16px;
    }

    .teacher-dashboard-metric,
    .teacher-dashboard-panel,
    .teacher-dashboard-action {
        background: #ffffff;
        border: 1px solid #e8eef7;
        border-radius: 16px;
        box-shadow: 0 16px 38px rgba(15, 23, 42, 0.07);
    }

    .teacher-dashboard-metric {
        padding: 20px;
        min-height: 152px;
    }

    .teacher-dashboard-metric-top {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 12px;
        margin-bottom: 18px;
    }

    .teacher-dashboard-icon {
        width: 46px;
        height: 46px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        border-radius: 14px;
        color: #ffffff;
        font-size: 18px;
    }

    .teacher-dashboard-icon.blue { background: linear-gradient(135deg, #2563eb, #38bdf8); }
    .teacher-dashboard-icon.green { background: linear-gradient(135deg, #16a34a, #34d399); }
    .teacher-dashboard-icon.orange { background: linear-gradient(135deg, #f97316, #fbbf24); }
    .teacher-dashboard-icon.purple { background: linear-gradient(135deg, #7c3aed, #a855f7); }

    .teacher-dashboard-metric small {
        color: #64748b;
        font-size: 12px;
        font-weight: 800;
        text-transform: uppercase;
    }

    .teacher-dashboard-metric strong {
        display: block;
        color: #071124;
        font-size: 34px;
        line-height: 1;
        font-weight: 950;
        margin-bottom: 8px;
    }

    .teacher-dashboard-metric p {
        margin: 0;
        color: #64748b;
        font-size: 14px;
        line-height: 1.55;
    }

    .teacher-dashboard-grid {
        display: grid;
        grid-template-columns: minmax(0, 1.35fr) minmax(320px, 0.65fr);
        gap: 22px;
    }

    .teacher-dashboard-panel {
        padding: 22px;
    }

    .teacher-dashboard-panel-title {
        display: flex;
        align-items: flex-start;
        justify-content: space-between;
        gap: 14px;
        margin-bottom: 16px;
    }

    .teacher-dashboard-panel h4 {
        margin: 0 0 4px;
        color: #071124;
        font-size: 22px;
        font-weight: 950;
    }

    .teacher-dashboard-panel p {
        margin: 0;
        color: #64748b;
    }

    .teacher-dashboard-action-grid {
        display: grid;
        gap: 12px;
    }

    .teacher-dashboard-action {
        display: flex;
        align-items: center;
        gap: 14px;
        padding: 16px;
        color: #071124;
        text-decoration: none;
        transition: transform 0.18s ease, box-shadow 0.18s ease;
    }

    .teacher-dashboard-action:hover {
        color: #071124;
        transform: translateY(-2px);
        box-shadow: 0 20px 42px rgba(15, 23, 42, 0.11);
    }

    .teacher-dashboard-action span {
        width: 42px;
        height: 42px;
        flex: 0 0 42px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        border-radius: 13px;
        background: #eef6ff;
        color: #0f3b7a;
    }

    .teacher-dashboard-action h5 {
        margin: 0 0 3px;
        font-size: 16px;
        font-weight: 900;
    }

    .teacher-dashboard-action p {
        margin: 0;
        font-size: 13px;
        color: #64748b;
    }

    .teacher-class-table {
        margin: 0;
    }

    .teacher-class-table th {
        color: #475569;
        font-size: 13px;
        text-transform: uppercase;
    }

    .teacher-class-table td {
        vertical-align: middle;
    }

    .teacher-class-pill {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        padding: 7px 10px;
        border-radius: 999px;
        background: #f1f5f9;
        color: #0f172a;
        font-weight: 800;
    }

    @media (max-width: 1199.98px) {
        .teacher-dashboard-metrics,
        .teacher-dashboard-grid {
            grid-template-columns: repeat(2, minmax(0, 1fr));
        }
    }

    @media (max-width: 767.98px) {
        .teacher-dashboard-hero {
            padding: 24px;
        }

        .teacher-dashboard-hero h2 {
            font-size: 28px;
        }

        .teacher-dashboard-metrics,
        .teacher-dashboard-grid {
            grid-template-columns: 1fr;
        }
    }
</style>

<div class="container-fluid">
    <div class="row">

        @include('layouts.teacher-sidebar')

        <div class="col-md-10 col-lg-10 p-4">
            <div class="teacher-dashboard-shell">
                <section class="teacher-dashboard-hero">
                    <div class="teacher-dashboard-kicker">
                        <i class="fa-solid fa-chalkboard-user"></i>
                        STEM Engineer Workspace
                    </div>

                    <h2>Welcome back, {{ $teacherName }}</h2>
                    <p>
                        Manage institute sessions, released teaching plan content, student progress, and assessments from one focused dashboard.
                    </p>

                    <div class="teacher-dashboard-hero-actions">
                        <a href="{{ route('teacher.classes') }}" class="btn btn-outline-light">
                            <i class="fa-solid fa-play me-1"></i>
                            Start Session
                        </a>
                        <a href="{{ route('teacher.pending-sessions') }}" class="btn btn-outline-light">
                            <i class="fa-solid fa-clock-rotate-left me-1"></i>
                            Pending Sessions
                        </a>
                        <a href="{{ route('teacher.content') }}" class="btn btn-outline-light">
                            <i class="fa-solid fa-book-open-reader me-1"></i>
                            Learning Content
                        </a>
                    </div>
                </section>

                <section class="teacher-dashboard-metrics">
                    <div class="teacher-dashboard-metric">
                        <div class="teacher-dashboard-metric-top">
                            <span class="teacher-dashboard-icon blue">
                                <i class="fa-solid fa-school"></i>
                            </span>
                            <small>Institute Classes</small>
                        </div>
                        <strong>{{ $assignedClasses }}</strong>
                        <p>{{ $activeClasses }} active class{{ $activeClasses == 1 ? '' : 'es' }} available in your institute.</p>
                    </div>

                    <div class="teacher-dashboard-metric">
                        <div class="teacher-dashboard-metric-top">
                            <span class="teacher-dashboard-icon green">
                                <i class="fa-solid fa-users"></i>
                            </span>
                            <small>Students</small>
                        </div>
                        <strong>{{ $totalStudents }}</strong>
                        <p>Learners available across your institute classes and sections.</p>
                    </div>

                    <div class="teacher-dashboard-metric">
                        <div class="teacher-dashboard-metric-top">
                            <span class="teacher-dashboard-icon orange">
                                <i class="fa-solid fa-file-pen"></i>
                            </span>
                            <small>Assessments</small>
                        </div>
                        <strong>{{ $assessmentCount }}</strong>
                        <p>{{ $monthlyAssessmentCount }} monthly and {{ $annualAssessmentCount }} annual assessment{{ $assessmentCount == 1 ? '' : 's' }}.</p>
                    </div>

                    <div class="teacher-dashboard-metric">
                        <div class="teacher-dashboard-metric-top">
                            <span class="teacher-dashboard-icon purple">
                                <i class="fa-solid fa-clipboard-check"></i>
                            </span>
                            <small>Approved Content</small>
                        </div>
                        <strong>{{ $contentCount }}</strong>
                        <p>Released teaching plan content available for institute sessions.</p>
                    </div>
                </section>

                <section class="teacher-dashboard-grid">
                    <div class="teacher-dashboard-panel">
                        <div class="teacher-dashboard-panel-title">
                            <div>
                                <h4>Institute Class Snapshot</h4>
                                <p>Quick view of classes available in your institute.</p>
                            </div>
                            <a href="{{ route('teacher.student-management') }}" class="btn btn-sm btn-outline-primary">
                                View Students
                            </a>
                        </div>

                        <div class="table-responsive lms-table-shell">
                            <table class="table table-hover align-middle teacher-class-table lms-table-fit">
                                <thead class="table-light">
                                    <tr>
                                        <th>Class</th>
                                        <th>Section</th>
                                        <th>Students</th>
                                        <th>Status</th>
                                    </tr>
                                </thead>

                                <tbody>
                                    @forelse($classes->take(8) as $class)
                                        @php
                                            $classLabel = trim(($class->class_name ?? '') . ' ' . ($class->section ?? ''));
                                        @endphp
                                        <tr>
                                            <td>
                                                <span class="teacher-class-pill">
                                                    <i class="fa-solid fa-layer-group"></i>
                                                    {{ $class->class_name }}
                                                </span>
                                            </td>
                                            <td>{{ $class->section ?: '-' }}</td>
                                            <td>{{ $classStudentCounts[$classLabel] ?? 0 }}</td>
                                            <td>
                                                @if($class->status)
                                                    <span class="badge bg-success">Active</span>
                                                @else
                                                    <span class="badge bg-danger">Inactive</span>
                                                @endif
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="4" class="text-center text-muted py-4">
                                                No institute classes found.
                                            </td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <div class="teacher-dashboard-panel">
                        <div class="teacher-dashboard-panel-title">
                            <div>
                                <h4>Quick Actions</h4>
                                <p>Jump into your most common workflows.</p>
                            </div>
                        </div>

                        <div class="alert alert-light border mb-3">
                            <div class="fw-bold mb-1">Today’s workload</div>
                            <div class="small text-muted">
                                {{ $todaySessionCount }} session{{ $todaySessionCount == 1 ? '' : 's' }} today,
                                {{ $unfinishedSessionCount }} unfinished,
                                {{ $pendingEvaluationCount }} pending evaluation{{ $pendingEvaluationCount == 1 ? '' : 's' }}.
                            </div>
                        </div>

                        <div class="teacher-dashboard-action-grid">
                            <a href="{{ route('teacher.sessions') }}" class="teacher-dashboard-action">
                                <span><i class="fa-solid fa-calendar-check"></i></span>
                                <div>
                                    <h5>Sessions</h5>
                                    <p>Start classes, continue pending sessions, or open content.</p>
                                </div>
                            </a>

                            <a href="{{ route('teacher.assessments.hub') }}" class="teacher-dashboard-action">
                                <span><i class="fa-solid fa-file-circle-check"></i></span>
                                <div>
                                    <h5>Assessments</h5>
                                    <p>Create question papers and evaluate submissions.</p>
                                </div>
                            </a>

                            <a href="{{ route('teacher.students.hub') }}" class="teacher-dashboard-action">
                                <span><i class="fa-solid fa-user-graduate"></i></span>
                                <div>
                                    <h5>Students</h5>
                                    <p>Review student details, results, and certificates.</p>
                                </div>
                            </a>

                            <a href="{{ route('teacher.notifications') }}" class="teacher-dashboard-action">
                                <span><i class="fa-solid fa-bell"></i></span>
                                <div>
                                    <h5>Notifications</h5>
                                    <p>Read institute updates and LMS announcements.</p>
                                </div>
                            </a>
                        </div>
                    </div>
                </section>
            </div>
        </div>
    </div>
</div>

@endsection
