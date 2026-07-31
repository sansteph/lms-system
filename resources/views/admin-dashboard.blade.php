@extends('layouts.app')

@section('content')

@php
    $isInstituteAdmin = session('user_role') == 'InstituteAdmin';
    $adminName = session('user_name') ?? ($isInstituteAdmin ? 'Institute Admin' : 'Admin');
    $scopeLabel = $isInstituteAdmin ? session('user_institute') : 'All Institutes';
    $totalPeople = $studentCount + $teacherCount;
    $assessmentRatio = $studentCount > 0 ? round(($assessmentCount / max($studentCount, 1)) * 100) : 0;

    $metricCards = [
        [
            'label' => 'Students',
            'value' => $studentCount,
            'icon' => 'fa-user-graduate',
            'tone' => 'blue',
            'route' => route('students'),
            'hint' => 'Learners under active management',
        ],
        [
            'label' => 'STEM Engineers',
            'value' => $teacherCount,
            'icon' => 'fa-chalkboard-user',
            'tone' => 'green',
            'route' => route('users'),
            'hint' => 'Training team accounts',
        ],
        [
            'label' => 'Classes',
            'value' => $classCount,
            'icon' => 'fa-school',
            'tone' => 'orange',
            'route' => route('classes'),
            'hint' => 'Class and section records',
        ],
        [
            'label' => 'Assessments',
            'value' => $assessmentCount,
            'icon' => 'fa-file-circle-check',
            'tone' => 'purple',
            'route' => route('admin.assessment.monitoring'),
            'hint' => 'Assessment records in scope',
        ],
    ];

    $quickActions = [
        [
            'title' => 'Manage Students',
            'description' => 'Add, bulk upload, edit, and review student records.',
            'icon' => 'fa-users',
            'route' => route('students'),
        ],
        [
            'title' => 'Manage Courses',
            'description' => 'Organize courses, contents, templates, and lesson order.',
            'icon' => 'fa-layer-group',
            'route' => route('courses'),
        ],
        [
            'title' => 'Teaching Plans',
            'description' => 'Deploy templates, release weekly content, and manage AI prep.',
            'icon' => 'fa-calendar-check',
            'route' => route('teaching-plans'),
        ],
        [
            'title' => 'Reports',
            'description' => 'Generate focused AI reports for sessions and performance.',
            'icon' => 'fa-chart-line',
            'route' => route('admin.reports.hub'),
        ],
    ];
@endphp

<style>
    .admin-dashboard-shell {
        display: grid;
        gap: 22px;
    }

    .admin-dashboard-hero {
        position: relative;
        overflow: hidden;
        border-radius: 28px;
        border: 1px solid rgba(219, 231, 244, .95);
        background:
            radial-gradient(circle at 86% 14%, rgba(20, 184, 166, .22), transparent 28%),
            radial-gradient(circle at 8% 20%, rgba(37, 99, 235, .18), transparent 32%),
            linear-gradient(135deg, #07184a 0%, #0f3b7a 54%, #075985 100%);
        color: #ffffff;
        padding: 30px;
        box-shadow: 0 26px 70px rgba(15, 23, 42, .16);
    }

    .admin-dashboard-hero::after {
        content: "";
        position: absolute;
        inset: 0;
        background-image:
            linear-gradient(rgba(255,255,255,.055) 1px, transparent 1px),
            linear-gradient(90deg, rgba(255,255,255,.055) 1px, transparent 1px);
        background-size: 42px 42px;
        opacity: .45;
        pointer-events: none;
    }

    .admin-dashboard-hero > * {
        position: relative;
        z-index: 1;
    }

    .admin-dashboard-kicker {
        display: inline-flex;
        align-items: center;
        gap: 9px;
        border: 1px solid rgba(255,255,255,.2);
        background: rgba(255,255,255,.1);
        border-radius: 999px;
        padding: 8px 14px;
        font-size: 13px;
        font-weight: 800;
        letter-spacing: .02em;
        margin-bottom: 16px;
    }

    .admin-dashboard-hero h2 {
        font-size: 38px;
        line-height: 1.08;
        font-weight: 950;
        letter-spacing: -1.1px;
        margin: 0 0 10px;
    }

    .admin-dashboard-hero p {
        max-width: 760px;
        color: rgba(255,255,255,.78);
        font-size: 16px;
        line-height: 1.7;
        margin: 0;
    }

    .admin-dashboard-status {
        display: flex;
        flex-wrap: wrap;
        gap: 10px;
        margin-top: 22px;
    }

    .admin-dashboard-status span {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        border-radius: 12px;
        background: rgba(255,255,255,.12);
        border: 1px solid rgba(255,255,255,.16);
        padding: 10px 13px;
        font-size: 13px;
        font-weight: 800;
    }

    .admin-dashboard-grid {
        display: grid;
        grid-template-columns: repeat(4, minmax(0, 1fr));
        gap: 18px;
    }

    .admin-dashboard-metric,
    .admin-dashboard-panel,
    .admin-dashboard-action {
        background: #ffffff;
        border: 1px solid #e5edf7;
        box-shadow: 0 20px 55px rgba(15, 23, 42, .07);
    }

    .admin-dashboard-metric {
        display: block;
        text-decoration: none;
        color: inherit;
        border-radius: 22px;
        padding: 20px;
        transition: transform .18s ease, box-shadow .18s ease;
    }

    .admin-dashboard-metric:hover {
        color: inherit;
        transform: translateY(-4px);
        box-shadow: 0 26px 70px rgba(15, 23, 42, .11);
    }

    .admin-dashboard-metric-top {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 12px;
        margin-bottom: 16px;
    }

    .admin-dashboard-icon {
        width: 48px;
        height: 48px;
        border-radius: 16px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        color: #ffffff;
        font-size: 20px;
        box-shadow: 0 16px 28px rgba(15, 23, 42, .14);
    }

    .admin-dashboard-icon.blue { background: linear-gradient(135deg, #2563eb, #38bdf8); }
    .admin-dashboard-icon.green { background: linear-gradient(135deg, #16a34a, #34d399); }
    .admin-dashboard-icon.orange { background: linear-gradient(135deg, #f97316, #fbbf24); }
    .admin-dashboard-icon.purple { background: linear-gradient(135deg, #7c3aed, #a855f7); }

    .admin-dashboard-arrow {
        width: 34px;
        height: 34px;
        border-radius: 999px;
        background: #f1f5f9;
        color: #2563eb;
        display: inline-flex;
        align-items: center;
        justify-content: center;
    }

    .admin-dashboard-metric small,
    .admin-dashboard-panel small {
        display: block;
        color: #64748b;
        font-weight: 800;
        margin-bottom: 5px;
    }

    .admin-dashboard-metric strong {
        display: block;
        color: #071124;
        font-size: 36px;
        line-height: 1;
        font-weight: 950;
    }

    .admin-dashboard-metric p {
        color: #64748b;
        margin: 10px 0 0;
        font-size: 13px;
        line-height: 1.5;
    }

    .admin-dashboard-panels {
        display: grid;
        grid-template-columns: 1.25fr .75fr;
        gap: 18px;
    }

    .admin-dashboard-panel {
        border-radius: 24px;
        padding: 24px;
    }

    .admin-dashboard-panel h4 {
        color: #071124;
        font-weight: 950;
        font-size: 22px;
        margin-bottom: 6px;
    }

    .admin-dashboard-action-grid {
        display: grid;
        grid-template-columns: repeat(2, minmax(0, 1fr));
        gap: 14px;
        margin-top: 18px;
    }

    .admin-dashboard-action {
        display: flex;
        align-items: flex-start;
        gap: 14px;
        border-radius: 18px;
        padding: 16px;
        color: inherit;
        text-decoration: none;
        transition: transform .18s ease, border-color .18s ease;
    }

    .admin-dashboard-action:hover {
        color: inherit;
        transform: translateY(-3px);
        border-color: #bfdbfe;
    }

    .admin-dashboard-action-icon {
        width: 42px;
        height: 42px;
        border-radius: 14px;
        background: #eff6ff;
        color: #2563eb;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        flex: 0 0 auto;
        font-size: 18px;
    }

    .admin-dashboard-action h5 {
        color: #071124;
        font-size: 16px;
        font-weight: 900;
        margin: 0 0 4px;
    }

    .admin-dashboard-action p {
        color: #64748b;
        margin: 0;
        font-size: 13px;
        line-height: 1.5;
    }

    .admin-dashboard-health {
        display: grid;
        gap: 12px;
        margin-top: 18px;
    }

    .admin-dashboard-health-row {
        display: flex;
        justify-content: space-between;
        gap: 14px;
        align-items: center;
        padding: 12px 14px;
        border-radius: 14px;
        background: #f8fafc;
        border: 1px solid #edf2f7;
    }

    .admin-dashboard-health-row span {
        color: #64748b;
        font-size: 13px;
        font-weight: 800;
    }

    .admin-dashboard-health-row strong {
        color: #071124;
        font-weight: 950;
    }

    .admin-dashboard-alert {
        border: 1px solid #fde68a;
        background: #fffbeb;
        color: #92400e;
        border-radius: 18px;
        padding: 16px 18px;
        box-shadow: 0 14px 32px rgba(146, 64, 14, .08);
    }

    @media (max-width: 1200px) {
        .admin-dashboard-grid {
            grid-template-columns: repeat(2, minmax(0, 1fr));
        }

        .admin-dashboard-panels {
            grid-template-columns: 1fr;
        }
    }

    @media (max-width: 768px) {
        .admin-dashboard-hero {
            padding: 24px;
        }

        .admin-dashboard-hero h2 {
            font-size: 30px;
        }

        .admin-dashboard-grid,
        .admin-dashboard-action-grid {
            grid-template-columns: 1fr;
        }
    }
</style>

<div class="container-fluid">
    <div class="row">

        @include('layouts.sidebar')

        <main class="col-md-10 col-lg-10 p-4">
            <div class="admin-dashboard-shell">
                <section class="admin-dashboard-hero">
                    <div class="admin-dashboard-kicker">
                        <i class="fa fa-shield-halved"></i>
                        {{ $scopeLabel }}
                    </div>

                    <h2>Welcome back, {{ $adminName }}</h2>
                    <p>
                        Monitor learners, STEM Engineers, classes, assessments, teaching plans,
                        approvals, and reports from one focused LMS control center.
                    </p>

                    <div class="admin-dashboard-status">
                        <span><i class="fa fa-circle-check"></i> LMS operational</span>
                        <span><i class="fa fa-brain"></i> AI workflows active</span>
                        <span><i class="fa fa-lock"></i> Institute isolation enabled</span>
                    </div>
                </section>

                @if(session('user_role') == 'Admin' && is_null(session('password_changed_at')))
                    <div class="admin-dashboard-alert">
                        <strong>Security Reminder:</strong>
                        You are using a system-generated password. Please change your password for better account security.
                    </div>
                @endif

                <section class="admin-dashboard-grid">
                    @foreach($metricCards as $metric)
                        <a href="{{ $metric['route'] }}" class="admin-dashboard-metric">
                            <div class="admin-dashboard-metric-top">
                                <span class="admin-dashboard-icon {{ $metric['tone'] }}">
                                    <i class="fa {{ $metric['icon'] }}"></i>
                                </span>
                                <span class="admin-dashboard-arrow">
                                    <i class="fa fa-arrow-right"></i>
                                </span>
                            </div>

                            <small>{{ $metric['label'] }}</small>
                            <strong>{{ number_format($metric['value']) }}</strong>
                            <p>{{ $metric['hint'] }}</p>
                        </a>
                    @endforeach
                </section>

                <section class="admin-dashboard-panels">
                    <div class="admin-dashboard-panel">
                        <small>Quick Actions</small>
                        <h4>Continue LMS Operations</h4>
                        <p class="text-muted mb-0">
                            Jump into the areas admins use most often during live operations.
                        </p>

                        <div class="admin-dashboard-action-grid">
                            @foreach($quickActions as $action)
                                <a href="{{ $action['route'] }}" class="admin-dashboard-action">
                                    <span class="admin-dashboard-action-icon">
                                        <i class="fa {{ $action['icon'] }}"></i>
                                    </span>

                                    <span>
                                        <h5>{{ $action['title'] }}</h5>
                                        <p>{{ $action['description'] }}</p>
                                    </span>
                                </a>
                            @endforeach
                        </div>
                    </div>

                    <div class="admin-dashboard-panel">
                        <small>Overview</small>
                        <h4>Operational Snapshot</h4>
                        <p class="text-muted mb-0">
                            A quick pulse check of the current LMS scope.
                        </p>

                        <div class="admin-dashboard-health">
                            <div class="admin-dashboard-health-row">
                                <span>Total Users</span>
                                <strong>{{ number_format($totalPeople) }}</strong>
                            </div>

                            <div class="admin-dashboard-health-row">
                                <span>Students per Class</span>
                                <strong>{{ $classCount ? number_format($studentCount / $classCount, 1) : '0' }}</strong>
                            </div>

                            <div class="admin-dashboard-health-row">
                                <span>STEM Engineers per Class</span>
                                <strong>{{ $classCount ? number_format($teacherCount / $classCount, 1) : '0' }}</strong>
                            </div>

                            <div class="admin-dashboard-health-row">
                                <span>Assessment Coverage</span>
                                <strong>{{ $assessmentRatio }}%</strong>
                            </div>
                        </div>
                    </div>
                </section>
            </div>
        </main>

    </div>
</div>

@endsection
