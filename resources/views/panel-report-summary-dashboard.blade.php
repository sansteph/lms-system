@extends('layouts.app')

@section('content')

@php
    $toneClasses = [
        'primary' => 'panel-summary-action-primary',
        'success' => 'panel-summary-action-success',
        'warning' => 'panel-summary-action-warning',
        'info' => 'panel-summary-action-info',
    ];
@endphp

<style>
    .panel-summary-shell {
        display: grid;
        gap: 22px;
    }

    .panel-summary-hero {
        position: relative;
        overflow: hidden;
        border-radius: 28px;
        padding: 30px;
        color: #fff;
        border: 1px solid rgba(219, 231, 244, .9);
        background:
            radial-gradient(circle at 88% 12%, rgba(20, 184, 166, .24), transparent 28%),
            radial-gradient(circle at 8% 18%, rgba(37, 99, 235, .22), transparent 30%),
            linear-gradient(135deg, #07184a 0%, #0f3b7a 55%, #075985 100%);
        box-shadow: 0 26px 70px rgba(15, 23, 42, .16);
    }

    .panel-summary-hero::after {
        content: "";
        position: absolute;
        inset: 0;
        background-image:
            linear-gradient(rgba(255,255,255,.06) 1px, transparent 1px),
            linear-gradient(90deg, rgba(255,255,255,.06) 1px, transparent 1px);
        background-size: 42px 42px;
        opacity: .42;
        pointer-events: none;
    }

    .panel-summary-hero > * {
        position: relative;
        z-index: 1;
    }

    .panel-summary-kicker {
        display: inline-flex;
        align-items: center;
        gap: 9px;
        border: 1px solid rgba(255,255,255,.22);
        background: rgba(255,255,255,.12);
        border-radius: 999px;
        padding: 8px 14px;
        font-size: 13px;
        font-weight: 800;
        margin-bottom: 16px;
    }

    .panel-summary-hero h2 {
        font-size: clamp(30px, 4vw, 44px);
        line-height: 1.06;
        font-weight: 950;
        letter-spacing: -.04em;
        margin: 0 0 10px;
    }

    .panel-summary-hero p {
        max-width: 760px;
        color: rgba(255,255,255,.8);
        font-size: 16px;
        line-height: 1.7;
        margin: 0;
    }

    .panel-summary-pills {
        display: flex;
        flex-wrap: wrap;
        gap: 10px;
        margin-top: 22px;
    }

    .panel-summary-pills span {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        border-radius: 12px;
        background: rgba(255,255,255,.13);
        border: 1px solid rgba(255,255,255,.17);
        padding: 10px 13px;
        font-size: 13px;
        font-weight: 800;
    }

    .panel-summary-actions {
        display: grid;
        grid-template-columns: repeat(4, minmax(0, 1fr));
        gap: 14px;
    }

    .panel-summary-action {
        display: flex;
        align-items: center;
        gap: 12px;
        min-height: 76px;
        padding: 16px;
        border-radius: 20px;
        text-decoration: none;
        color: #0f172a;
        background: #fff;
        border: 1px solid #e5edf7;
        box-shadow: 0 18px 50px rgba(15, 23, 42, .08);
        transition: transform .18s ease, box-shadow .18s ease;
    }

    .panel-summary-action:hover {
        color: #0f172a;
        transform: translateY(-3px);
        box-shadow: 0 24px 64px rgba(15, 23, 42, .12);
    }

    .panel-summary-action-icon {
        width: 42px;
        height: 42px;
        flex: 0 0 42px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        border-radius: 14px;
        color: #fff;
        box-shadow: 0 14px 26px rgba(15, 23, 42, .14);
    }

    .panel-summary-action-primary .panel-summary-action-icon { background: linear-gradient(135deg, #2563eb, #38bdf8); }
    .panel-summary-action-success .panel-summary-action-icon { background: linear-gradient(135deg, #16a34a, #34d399); }
    .panel-summary-action-warning .panel-summary-action-icon { background: linear-gradient(135deg, #f97316, #fbbf24); }
    .panel-summary-action-info .panel-summary-action-icon { background: linear-gradient(135deg, #0891b2, #22d3ee); }

    .panel-summary-action strong {
        display: block;
        font-size: 15px;
        font-weight: 900;
        line-height: 1.2;
    }

    .panel-summary-card {
        height: 100%;
        border: 1px solid #e5edf7;
        border-radius: 24px;
        background: #fff;
        box-shadow: 0 20px 55px rgba(15, 23, 42, .07);
    }

    .panel-summary-card .card-body {
        padding: 24px;
    }

    .panel-summary-scope {
        display: flex;
        align-items: center;
        gap: 14px;
    }

    .panel-summary-scope-icon {
        width: 54px;
        height: 54px;
        border-radius: 18px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        color: #fff;
        background: linear-gradient(135deg, #0f766e, #14b8a6);
        box-shadow: 0 16px 32px rgba(20, 184, 166, .22);
    }

    .panel-summary-muted {
        color: #64748b;
        font-weight: 700;
    }

    .panel-summary-report-list {
        display: grid;
        gap: 12px;
    }

    .panel-summary-report-item {
        display: grid;
        grid-template-columns: minmax(160px, .7fr) minmax(150px, .55fr) minmax(220px, 1fr);
        gap: 14px;
        align-items: center;
        padding: 16px;
        border-radius: 18px;
        border: 1px solid #e2e8f0;
        background: linear-gradient(135deg, #f8fbff, #ffffff);
    }

    .panel-summary-report-item strong {
        color: #0f172a;
        font-weight: 950;
    }

    .panel-summary-report-item .badge {
        justify-self: start;
        padding: 9px 12px;
        border-radius: 999px;
    }

    .panel-summary-note {
        display: flex;
        gap: 12px;
        align-items: flex-start;
        height: 100%;
        border-radius: 18px;
        padding: 16px;
        background: #f8fafc;
        border: 1px solid #e2e8f0;
        color: #475569;
        font-weight: 700;
    }

    .panel-summary-note i {
        color: #2563eb;
        margin-top: 4px;
    }

    @media (max-width: 991.98px) {
        .panel-summary-actions {
            grid-template-columns: repeat(2, minmax(0, 1fr));
        }

        .panel-summary-report-item {
            grid-template-columns: 1fr;
        }
    }

    @media (max-width: 575.98px) {
        .panel-summary-hero {
            padding: 24px;
            border-radius: 22px;
        }

        .panel-summary-actions {
            grid-template-columns: 1fr;
        }
    }
</style>

<div class="container-fluid">
    <div class="row">
        @include('layouts.sidebar')

        <main class="col-md-10 col-lg-10 p-4">
            <div class="panel-summary-shell">
                <section class="panel-summary-hero">
                    <span class="panel-summary-kicker">
                        <i class="fa-solid fa-chart-line"></i>
                        Reporting Workspace
                    </span>
                    <h2>{{ $title }}</h2>
                    <p>{{ $description }}</p>
                    <div class="panel-summary-pills">
                        <span><i class="fa-solid fa-shield-halved"></i> Role-scoped access</span>
                        <span><i class="fa-solid fa-filter"></i> Search-ready reports</span>
                        <span><i class="fa-solid fa-file-pdf"></i> PDF exports</span>
                    </div>
                </section>

                <section class="panel-summary-actions" aria-label="Quick actions">
                    @foreach($quickActions as $action)
                        <a href="{{ $action['route'] }}" class="panel-summary-action {{ $toneClasses[$action['tone']] ?? $toneClasses['primary'] }}">
                            <span class="panel-summary-action-icon">
                                <i class="fa-solid {{ $action['icon'] }}"></i>
                            </span>
                            <strong>{{ $action['label'] }}</strong>
                        </a>
                    @endforeach
                </section>

                <div class="row g-4">
                    <div class="col-xl-4">
                        <div class="panel-summary-card">
                            <div class="card-body">
                                <div class="panel-summary-scope mb-3">
                                    <span class="panel-summary-scope-icon">
                                        <i class="fa-solid fa-building-columns"></i>
                                    </span>
                                    <div>
                                        <div class="panel-summary-muted small">Access Scope</div>
                                        <h4 class="mb-0">{{ $scopeLabel }}</h4>
                                    </div>
                                </div>
                                <p class="panel-summary-muted mb-0">
                                    This dashboard summarizes what this role can review. Detailed tables, filters, and downloads stay inside Reports.
                                </p>
                            </div>
                        </div>
                    </div>

                    <div class="col-xl-8">
                        <div class="panel-summary-card">
                            <div class="card-body">
                                <div class="d-flex align-items-center justify-content-between flex-wrap gap-2 mb-3">
                                    <div>
                                        <div class="panel-summary-muted small">Available Access</div>
                                        <h4 class="mb-0">Report Summary</h4>
                                    </div>
                                    <span class="badge bg-primary-subtle text-primary">Ready</span>
                                </div>

                                <div class="panel-summary-report-list">
                                    @foreach($summaryItems as $item)
                                        <div class="panel-summary-report-item">
                                            <strong>{{ $item['label'] }}</strong>
                                            <span class="badge bg-light text-primary border">{{ $item['cadence'] }}</span>
                                            <span class="panel-summary-muted">{{ $item['coverage'] }}</span>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-12">
                        <div class="panel-summary-card">
                            <div class="card-body">
                                <div class="d-flex align-items-center gap-2 mb-3">
                                    <i class="fa-solid fa-circle-info text-primary"></i>
                                    <h4 class="mb-0">Operational Notes</h4>
                                </div>
                                <div class="row g-3">
                                    @foreach($notes as $note)
                                        <div class="col-md-6">
                                            <div class="panel-summary-note">
                                                <i class="fa-solid fa-check-circle"></i>
                                                <span>{{ $note }}</span>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>
</div>

@endsection
