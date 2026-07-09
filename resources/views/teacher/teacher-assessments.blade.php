@extends('layouts.app')

@section('content')

<div class="container-fluid">
    <div class="row">
        @include('layouts.teacher-sidebar')

        <div class="col-md-10 col-lg-10 p-4">
            <div class="page-header d-flex justify-content-between align-items-center flex-wrap gap-3 mb-4">
                <div>
                    <h2 class="mb-1">Assessment Management</h2>
                    <p class="text-muted mb-0">View assessments assigned to your institute and classes.</p>
                </div>

                <a href="{{ route('teacher.assessments') }}" class="btn btn-primary btn-sm">
                    Manage Assessments
                </a>
            </div>

            <div class="card shadow border-0">
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-bordered table-hover align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>Sl. No</th>
                                    <th>Assessment</th>
                                    <th>Class</th>
                                    <th>Category</th>
                                    <th>Date</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($assessments as $index => $assessment)
                                    <tr>
                                        <td>{{ $index + 1 }}</td>
                                        <td>{{ $assessment->assessment_title }}</td>
                                        <td>{{ $assessment->assigned_class }}</td>
                                        <td>{{ $assessment->assessment_category ?? 'Monthly' }}</td>
                                        <td>
                                            {{ $assessment->assessment_date ? \Carbon\Carbon::parse($assessment->assessment_date)->format('d M Y') : 'Not Set' }}
                                        </td>
                                        <td>{{ $assessment->status ?? 'Active' }}</td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="6" class="text-center text-muted">
                                            No assessments found.
                                        </td>
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
