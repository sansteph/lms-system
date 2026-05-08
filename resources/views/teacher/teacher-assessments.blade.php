@extends('layouts.app')

@section('content')

<div class="container-fluid">
    <div class="row">

        @include('layouts.teacher-sidebar')

        <div class="col-md-10 col-lg-10 p-4">

            <div class="page-header mb-4">
                <h2 class="mb-1">Assessments</h2>
                <p class="text-muted mb-0">
                    View assigned assessments and generate student assessment links.
                </p>
            </div>

            <div class="row g-4 mb-4">
                <div class="col-md-3">
                    <div class="dashboard-card">
                        <h6>Total Assessments</h6>
                        <h2>{{ $assessments->count() }}</h2>
                    </div>
                </div>

                <div class="col-md-3">
                    <div class="dashboard-card">
                        <h6>Student Assessments</h6>
                        <h2>{{ $assessments->where('assessment_type', 'Student')->count() }}</h2>
                    </div>
                </div>

                <div class="col-md-3">
                    <div class="dashboard-card">
                        <h6>Teacher Assessments</h6>
                        <h2>{{ $assessments->where('assessment_type', 'Teacher')->count() }}</h2>
                    </div>
                </div>

                <div class="col-md-3">
                    <div class="dashboard-card">
                        <h6>Active</h6>
                        <h2>{{ $assessments->where('status', 1)->count() }}</h2>
                    </div>
                </div>
            </div>

            <div class="card shadow border-0">
                <div class="card-body">

                    <table class="table table-bordered table-hover align-middle">
                        <thead class="table-light">
                            <tr>
                                <th>Sl. No</th>
                                <th>Assessment Title</th>
                                <th>Type</th>
                                <th>Class / Group</th>
                                <th>Total Marks</th>
                                <th>Duration</th>
                                <th>Status</th>
                                <th width="220">Actions</th>
                            </tr>
                        </thead>

                        <tbody>
                            @forelse($assessments as $index => $assessment)
                                <tr>
                                    <td>{{ $index + 1 }}</td>
                                    <td>{{ $assessment->assessment_title }}</td>
                                    <td>
                                        @if($assessment->assessment_type == 'Student')
                                            <span class="badge bg-primary">Student</span>
                                        @else
                                            <span class="badge bg-warning text-dark">Teacher</span>
                                        @endif
                                    </td>
                                    <td>{{ $assessment->assigned_class }}</td>
                                    <td>{{ $assessment->total_marks }}</td>
                                    <td>{{ $assessment->duration }}</td>
                                    <td>
                                        @if($assessment->status == 1)
                                            <span class="badge bg-success">Active</span>
                                        @else
                                            <span class="badge bg-danger">Inactive</span>
                                        @endif
                                    </td>
                                    <td>
                                        <button class="btn btn-sm btn-outline-warning">
                                            Generate Link
                                        </button>

                                        @if($assessment->file_path)
                                            <a href="{{ asset('storage/' . $assessment->file_path) }}"
                                               target="_blank"
                                               class="btn btn-sm btn-outline-primary">
                                                View Paper
                                            </a>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="8" class="text-center text-muted">
                                        No assessments assigned yet
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>

                    <div class="alert alert-info mt-3 mb-0">
                        Generate Link is kept as a future feature until student assessment flow and result storage are connected.
                    </div>

                </div>
            </div>

        </div>

    </div>
</div>

@endsection