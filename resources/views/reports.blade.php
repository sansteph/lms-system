@extends('layouts.app')

@section('content')

@php
    $reportMode = $reportMode ?? 'overview';
    $reportTitles = [
        'overview' => 'Reports',
        'student-ai-review' => 'Weekly Student AI Review Report',
        'stem-engineer-prep' => 'Weekly STEM Engineer Prep Report',
        'daily-student-performance' => 'Daily Student Performance Report',
        'weekly-student-performance' => 'Weekly Student Performance Report',
        'monthly-student-performance' => 'Monthly Student Performance Report',
        'weekly-stem-engineer-performance' => 'Weekly STEM Engineer Performance Report',
        'monthly-stem-engineer-performance' => 'Monthly STEM Engineer Performance Report',
    ];
    $reportDescriptions = [
        'overview' => 'Generate, view, and download LMS performance reports.',
        'student-ai-review' => 'Track weekly student AI review quiz completion, attempts, pass rates, and readiness.',
        'stem-engineer-prep' => 'Track weekly STEM Engineer prep quiz attempts, pass rates, and readiness.',
        'daily-student-performance' => 'Track student progress and assessment outcomes for the selected day.',
        'weekly-student-performance' => 'Track student progress and assessment outcomes for the selected week.',
        'monthly-student-performance' => 'Track student progress and assessment outcomes for the selected month.',
        'weekly-stem-engineer-performance' => 'Track STEM Engineer weekly sessions, completion patterns, teaching hours, and prep readiness.',
        'monthly-stem-engineer-performance' => 'Track STEM Engineer monthly consistency, completion patterns, teaching hours, and prep readiness.',
    ];
    $isFocusedReport = $reportMode !== 'overview';
    $isStudentPrepReport = $reportMode == 'student-ai-review';
    $isTeacherPrepReport = $reportMode == 'stem-engineer-prep';
    $isStudentPerformanceReport = in_array($reportMode, ['daily-student-performance', 'weekly-student-performance', 'monthly-student-performance'], true);
    $isStudentScopedReport = in_array($reportMode, ['student-ai-review', 'daily-student-performance', 'weekly-student-performance', 'monthly-student-performance'], true);
    $isTeacherPerformanceReport = in_array($reportMode, ['weekly-stem-engineer-performance', 'monthly-stem-engineer-performance'], true);
    $isDailyReport = str_starts_with($reportMode, 'daily-');
    $isWeeklyReport = str_starts_with($reportMode, 'weekly-');
    $isWeeklyPrepReport = in_array($reportMode, ['student-ai-review', 'stem-engineer-prep'], true);
    $isMonthlyReport = str_starts_with($reportMode, 'monthly-');
    $reportRoutePrefix = session('user_role') === 'Principal'
        ? 'principal.'
        : (session('user_role') === 'Manager' ? 'manager.' : '');
    $downloadRoute = match ($reportMode) {
        'student-ai-review' => route('reports.student-ai-review.download'),
        'stem-engineer-prep' => route('reports.stem-engineer-prep.download'),
        'daily-student-performance' => route($reportRoutePrefix . 'reports.student-performance.daily.download'),
        'weekly-student-performance' => route($reportRoutePrefix . 'reports.student-performance.weekly.download'),
        'monthly-student-performance' => route($reportRoutePrefix . 'reports.student-performance.monthly.download'),
        'weekly-stem-engineer-performance' => route($reportRoutePrefix . 'reports.stem-engineer-performance.weekly.download'),
        'monthly-stem-engineer-performance' => route($reportRoutePrefix . 'reports.stem-engineer-performance.monthly.download'),
        default => null,
    };
    $clearReportUrl = url()->current();
@endphp

<div class="container-fluid">
    <div class="row">

        @include('layouts.sidebar')

        <div class="col-md-10 col-lg-10 p-4">

            <div class="page-header d-flex justify-content-between align-items-center flex-wrap gap-3 mb-4">
                <div>
                    <h2 class="mb-1">{{ $reportTitles[$reportMode] ?? 'Reports' }}</h2>
                    <p class="text-muted mb-0">
                        {{ $reportDescriptions[$reportMode] ?? $reportDescriptions['overview'] }}
                    </p>
                </div>

            </div>

            @if(session('error'))
                <div class="alert alert-danger">
                    {{ session('error') }}
                </div>
            @endif

            @if($isFocusedReport)
                <div class="card shadow border-0 mb-4">
                    <div class="card-body lms-report-filter-card">
                        <div class="d-flex justify-content-between align-items-start flex-wrap gap-3 mb-3">
                            <div>
                                <h5 class="mb-1">Apply filters for easy navigation</h5>
                                <p class="text-muted mb-0">Choose the report scope below to load the relevant report data.</p>
                            </div>
                            <span class="badge bg-light text-dark border">Report Filters</span>
                        </div>

                        <form method="GET" action="{{ url()->current() }}">
                            <div class="lms-report-filter-grid">
                            @if(in_array(session('user_role'), ['Admin', 'Manager'], true))
                                <div class="lms-report-action-group">
                                    <label class="form-label">Institute</label>
                                    <select name="institute" class="form-select">
                                        <option value="">Select institute</option>
                                        @foreach($reportInstituteOptions as $instituteOption)
                                            <option value="{{ $instituteOption }}" @selected($selectedReportInstitute == $instituteOption)>{{ $instituteOption }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            @endif

                            @if($isStudentScopedReport)
                                <div class="lms-report-action-group">
                                    <label class="form-label">Class</label>
                                    <select name="student_class" class="form-select">
                                        <option value="">Select class</option>
                                        @foreach($reportClassOptions as $classOption)
                                            <option value="{{ $classOption }}" @selected($selectedStudentReportClass == $classOption)>{{ $classOption }}</option>
                                        @endforeach
                                    </select>
                                </div>

                                <div class="lms-report-action-group">
                                    <label class="form-label">Section</label>
                                    <select name="student_section" class="form-select">
                                        <option value="">Select section</option>
                                        @foreach($reportSectionOptions as $sectionOption)
                                            <option value="{{ $sectionOption }}" @selected($selectedStudentReportSection == $sectionOption)>{{ $sectionOption }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            @endif

                            @if($isDailyReport)
                                <div class="lms-report-action-group">
                                    <label class="form-label">Report Date</label>
                                    <input type="date" name="report_date" class="form-control" value="{{ request('report_date') }}">
                                </div>
                            @elseif($isMonthlyReport)
                                <div class="lms-report-action-group">
                                    <label class="form-label">Report Month</label>
                                    <input type="month" name="report_month" class="form-control" value="{{ request('report_month') }}">
                                </div>
                            @else
                                <div class="lms-report-date-range-line">
                                    <div class="lms-report-date-range-group">
                                        <div class="lms-report-date-item">
                                            <label class="form-label">From Date</label>
                                            <input type="date" name="from_date" class="form-control" value="{{ request('from_date') }}">
                                        </div>

                                        <div class="lms-report-date-item">
                                            <label class="form-label">To Date</label>
                                            <input type="date" name="to_date" class="form-control" value="{{ request('to_date') }}">
                                        </div>
                                    </div>
                                </div>
                            @endif

                            </div>

                            <div class="lms-report-button-band">
                                <button type="submit" class="btn btn-primary lms-report-action-button">Show Report Data</button>
                                <a href="{{ $clearReportUrl }}" class="btn btn-outline-secondary lms-report-action-clear">Clear</a>
                            </div>
                        </form>

                        <div class="lms-report-status-row mt-3 text-muted small">
                            <span class="lms-report-status-text">Current range: {{ $periodLabel ?? 'All available data' }}</span>
                            @if($downloadRoute)
                                <form method="POST" action="{{ $downloadRoute }}" class="lms-report-status-action">
                                    @csrf
                                    @if(in_array(session('user_role'), ['Admin', 'Manager'], true))
                                        <input type="hidden" name="institute" value="{{ $selectedReportInstitute }}">
                                    @endif
                                    @if($isStudentScopedReport)
                                        <input type="hidden" name="student_class" value="{{ $selectedStudentReportClass }}">
                                        <input type="hidden" name="student_section" value="{{ $selectedStudentReportSection }}">
                                    @endif
                                    @if($isDailyReport)
                                        <input type="hidden" name="report_date" value="{{ request('report_date') }}">
                                    @elseif($isMonthlyReport)
                                        <input type="hidden" name="report_month" value="{{ request('report_month') }}">
                                    @else
                                        <input type="hidden" name="from_date" value="{{ request('from_date') }}">
                                        <input type="hidden" name="to_date" value="{{ request('to_date') }}">
                                    @endif
                                    <button type="submit" class="btn btn-outline-primary btn-sm lms-report-action-generate">Generate Report PDF</button>
                                </form>
                            @endif
                        </div>
                    </div>
                </div>
            @endif

            @if($isFocusedReport && !$hasFilters)
                @include('partials.filter-placeholder')
            @endif

            @if(!$isFocusedReport || $hasFilters)

            @php
                $aiInsights = session('aiInsights');
            @endphp

            @if($aiInsights)
                @include('partials.ai-full-report', [
                    'aiInsights' => $aiInsights,
                    'reportTitle' => 'AI Generated LMS Report'
                ])
            @endif

            @if(!$isFocusedReport)
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
            @endif

            @if(in_array($reportMode, ['overview', 'student-ai-review', 'stem-engineer-prep', 'daily-student-performance', 'weekly-student-performance', 'monthly-student-performance'], true))
            <div class="row g-4 mb-4">
                @if(in_array($reportMode, ['overview', 'student-ai-review', 'daily-student-performance', 'weekly-student-performance', 'monthly-student-performance'], true))
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
                @endif

                @if(in_array($reportMode, ['overview', 'stem-engineer-prep'], true))
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
                @endif
            </div>
            @endif

            @if(!$isFocusedReport)
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
            @endif

            @if(!$isFocusedReport)
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
            @endif

            @if(!$isFocusedReport)
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
            @endif

            @if(in_array($reportMode, ['overview', 'stem-engineer-prep', 'weekly-stem-engineer-performance', 'monthly-stem-engineer-performance'], true))
            <div class="row g-4 mb-4">
                @if($reportMode == 'overview')
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
                @endif

                <div class="{{ $reportMode == 'overview' ? 'col-lg-6' : 'col-12' }}">
                    <div class="card shadow border-0 h-100">
                        <div class="card-body">
                            <h5 class="mb-3">
                                {{ $isTeacherPrepReport ? 'STEM Engineer Prep Quiz Readiness' : 'STEM Engineer Performance' }}
                            </h5>
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
            @endif

            @if(in_array($reportMode, ['overview', 'student-ai-review', 'daily-student-performance', 'weekly-student-performance', 'monthly-student-performance'], true))
            <div class="card shadow border-0 mb-4">
                <div class="card-body">
                    <h5 class="mb-3">
                        {{ $isStudentPrepReport ? 'Student AI Review Tracking' : 'Class-wise Student Performance' }}
                    </h5>
                    <div class="table-responsive">
                        <table class="table table-bordered table-hover align-middle">
                            <thead class="table-light">
                                <tr>
                                    <th>Institute</th>
                                    <th>Class</th>
                                    <th>Students</th>
                                    <th>Sessions</th>
                                    <th>Active Plans</th>
                                    <th>Assessments</th>
                                    <th>AI Reviews</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($classBreakdowns as $class)
                                    <tr>
                                        <td>{{ $class['institute'] }}</td>
                                        <td>{{ $class['class_label'] }}</td>
                                        <td>{{ $class['students'] }}</td>
                                        <td>{{ $class['sessions'] }}</td>
                                        <td>{{ $class['active_plans'] }}</td>
                                        <td>
                                            {{ $class['assessment_results'] }} results |
                                            {{ number_format($class['assessment_average'], 2) }}% avg
                                        </td>
                                        <td>
                                            {{ $class['ai_reviews'] }} total |
                                            {{ $class['ai_reviews_passed'] }} passed |
                                            {{ number_format($class['ai_review_average'], 2) }}%
                                        </td>
                                    </tr>
                                @empty
                                    <tr><td colspan="7" class="text-center text-muted">No class tracking data available.</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            @endif

            @if(!$isFocusedReport)
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
            @endif

            @if(!$isFocusedReport)
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
            @endif

            @endif

        </div>
    </div>
</div>

@endsection
