@extends('layouts.app')

@section('content')

<div class="container-fluid">
    <div class="row">

        @include('layouts.teacher-sidebar')

        <div class="col-md-10 col-lg-10 p-4">

            <div class="page-header d-flex justify-content-between align-items-center flex-wrap gap-3 mb-4">
                <div>
                    <h2 class="mb-1">Reports</h2>
                    <p class="text-muted mb-0">
                        View class performance, student progress, and assessment reports.
                    </p>
                </div>

                <a href="{{ route('teacher.reports.export', request()->query()) }}" class="btn btn-success btn-sm">
                    <i class="fa fa-file-csv me-1"></i>
                    Export Report
                </a>
            </div>

            <div class="row g-4 mb-4">

                <div class="col-md-3">
                    <div class="dashboard-card">
                        <h6>Institute Students</h6>
                        <h2>{{ $studentCount }}</h2>
                    </div>
                </div>

                <div class="col-md-3">
                    <div class="dashboard-card">
                        <h6>Institute Classes</h6>
                        <h2>{{ $classCount }}</h2>
                    </div>
                </div>

                <div class="col-md-3">
                    <div class="dashboard-card">
                        <h6>Average Score</h6>
                        <h2>{{ number_format($averageScore, 2) }}%</h2>
                    </div>
                </div>

                <div class="col-md-3">
                    <div class="dashboard-card">
                        <h6>Pending Reviews</h6>
                        <h2>{{ $pendingReviewCount }}</h2>
                    </div>
                </div>

            </div>

            <div class="card shadow border-0 mb-4">
                <div class="card-body">
                    <form method="GET" action="{{ route('teacher.reports') }}" class="row g-3 align-items-end">
                        <div class="col-md-4">
                            <label class="form-label">Class</label>
                            <select name="class" class="form-control">
                                <option value="">All Classes</option>
                                @foreach($classOptions as $classOption)
                                    <option value="{{ $classOption }}" {{ $selectedClass == $classOption ? 'selected' : '' }}>
                                        {{ $classOption }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-2">
                            <button type="submit" class="btn btn-primary w-100">Apply</button>
                        </div>
                        <div class="col-md-2">
                            <a href="{{ route('teacher.reports') }}" class="btn btn-outline-secondary w-100">Clear</a>
                        </div>
                        <div class="col-md-2">
                            <a href="{{ route('teacher.reports.export', request()->query()) }}" class="btn btn-success w-100">
                                Export
                            </a>
                        </div>
                    </form>
                </div>
            </div>
            
            <div class="card shadow border-0">
                <div class="card-body">

                    <h5 class="mb-4">Live STEM Engineer Report Summary</h5>

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
                                <td>Students in Institute</td>
                                <td>{{ $studentCount }}</td>
                                <td><span class="badge bg-primary">Live</span></td>
                            </tr>

                            <tr>
                                <td>2</td>
                                <td>Classes</td>
                                <td>Institute Classes</td>
                                <td>{{ $classCount }}</td>
                                <td><span class="badge bg-info">Live</span></td>
                            </tr>

                            <tr>
                                <td>3</td>
                                <td>Content</td>
                                <td>Approved Planned Content</td>
                                <td>{{ $contentCount }}</td>
                                <td><span class="badge bg-info">Live</span></td>
                            </tr>

                            <tr>
                                <td>4</td>
                                <td>Sessions</td>
                                <td>Sessions Conducted By You</td>
                                <td>{{ $sessionCount }} total | {{ $completedSessionCount }} completed</td>
                                <td><span class="badge bg-success">Live</span></td>
                            </tr>

                            <tr>
                                <td>5</td>
                                <td>Assessments</td>
                                <td>Your Assessments</td>
                                <td>{{ $assessmentCount }} total | {{ $monthlyAssessmentCount }} monthly | {{ $annualAssessmentCount }} annual</td>
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
                                <td>Manual Reviews</td>
                                <td>Pending Written Answers</td>
                                <td>{{ $pendingReviewCount }}</td>
                                <td><span class="badge bg-warning text-dark">Pending</span></td>
                            </tr>

                            <tr>
                                <td>8</td>
                                <td>Performance</td>
                                <td>Average Assessment Score</td>
                                <td>{{ number_format($averageScore, 2) }}%</td>
                                <td><span class="badge bg-success">Calculated</span></td>
                            </tr>

                            <tr>
                                <td>9</td>
                                <td>Certificates</td>
                                <td>Total Certificates Issued</td>
                                <td>{{ $certificateCount }}</td>
                                <td><span class="badge bg-success">Live</span></td>
                            </tr>

                        </tbody>

                    </table>

                </div>
            </div>

            <div class="card shadow border-0 mt-4">
                <div class="card-body">
                    <h5 class="mb-4">Class-wise Report Summary</h5>

                    <div class="table-responsive">
                        <table class="table table-bordered table-hover align-middle">
                            <thead class="table-light">
                                <tr>
                                    <th>Class</th>
                                    <th>Students</th>
                                    <th>Released Content</th>
                                    <th>Your Sessions</th>
                                    <th>Completed Results</th>
                                    <th>Average Score</th>
                                    <th>Certificates</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($classReportRows as $row)
                                    <tr>
                                        <td class="fw-semibold">{{ $row['class'] }}</td>
                                        <td>{{ $row['students'] }}</td>
                                        <td>{{ $row['content'] }}</td>
                                        <td>{{ $row['sessions'] }}</td>
                                        <td>{{ $row['completed_results'] }}</td>
                                        <td>{{ number_format($row['average_score'], 2) }}%</td>
                                        <td>{{ $row['certificates'] }}</td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="7" class="text-center text-muted">No class-wise report data found.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

        </div>

    </div>

</div>

@endsection
