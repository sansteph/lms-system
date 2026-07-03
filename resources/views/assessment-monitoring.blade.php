@extends('layouts.app')

@section('content')

<div class="container-fluid">
    <div class="row">

        @include('layouts.sidebar')

        <div class="col-md-10 col-lg-10 p-4">

            <div class="card shadow border-0">

                <div class="card-body">

                    <h3 class="fw-bold mb-4">
                        Assessment Monitoring
                    </h3>

                    <table class="table table-bordered table-hover align-middle">

                        <thead class="table-light">
                            <tr>
                                <th>User</th>
                                <th>User Type</th>
                                <th>Institute</th>
                                <th>Assessment</th>
                                <th>Started At</th>
                                <th>Submitted At</th>
                                <th>Violations</th>
                                <th>Status</th>
                            </tr>
                        </thead>

                        <tbody>

                            @forelse($sessions as $session)

                                @php
                                    $userName = 'Unknown User';
                                    $institute = 'N/A';

                                    if ($session->user_type == 'Student') {
                                        $userName = $session->student->name ?? 'Student Deleted';
                                        $institute = $session->student->institute ?? 'N/A';
                                    } elseif ($session->user_type == 'Teacher') {
                                        $userName = $session->teacher->name ?? 'STEM Engineer Deleted';
                                        $institute = $session->teacher->institute ?? 'N/A';
                                    }
                                @endphp

                                <tr>
                                    <td>{{ $userName }}</td>
                                    <td>{{ $session->user_type }}</td>
                                    <td>{{ $institute }}</td>
                                    <td>{{ $session->assessment->assessment_title ?? 'Assessment Deleted' }}</td>
                                    <td>{{ $session->started_at }}</td>
                                    <td>{{ $session->submitted_at ?? 'Not Submitted' }}</td>

                                    <td>
                                        @if($session->violation_count >= 3)
                                            <span class="badge bg-danger">
                                                {{ $session->violation_count }}
                                            </span>
                                        @elseif($session->violation_count > 0)
                                            <span class="badge bg-warning text-dark">
                                                {{ $session->violation_count }}
                                            </span>
                                        @else
                                            <span class="badge bg-success">
                                                0
                                            </span>
                                        @endif
                                    </td>

                                    <td>
                                        @if($session->status == 'Started')
                                            <span class="badge bg-warning text-dark">Started</span>
                                        @elseif($session->status == 'Submitted')
                                            <span class="badge bg-success">Submitted</span>
                                        @elseif($session->status == 'AutoSubmitted')
                                            <span class="badge bg-danger">Auto Submitted</span>
                                        @else
                                            <span class="badge bg-secondary">{{ $session->status }}</span>
                                        @endif
                                    </td>
                                </tr>

                            @empty

                                <tr>
                                    <td colspan="8" class="text-center text-muted">
                                        No assessment activity found.
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

@endsection
