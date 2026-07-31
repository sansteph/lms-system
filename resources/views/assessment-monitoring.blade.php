@extends('layouts.app')

@section('content')

<div class="container-fluid">
    <div class="row">

        @include('layouts.sidebar')

        <div class="col-md-10 col-lg-10 p-4">

            @include('partials.section-navigator', [
                'sectionPager' => $sectionPager ?? null,
                'sectionDescription' => 'Browse assessment monitoring institute by institute.',
            ])

            @include('partials.section-navigator', [
                'sectionPager' => $classSectionPager ?? null,
                'sectionDescription' => 'Browse assessment monitoring one class at a time inside the selected institute.',
            ])

            @include('partials.section-navigator', [
                'sectionPager' => $studentSectionPager ?? null,
                'sectionDescription' => 'Showing assessment monitoring for this section only.',
            ])

            @if(!empty($selectedStudentClass))
                <div class="alert alert-light border d-flex justify-content-between align-items-center flex-wrap gap-2">
                    <div>
                        <span class="fw-semibold">Current assessment scope:</span>
                        Class {{ $selectedStudentClass }}
                        @if(!empty($selectedStudentSection))
                            &middot; Section {{ $selectedStudentSection }}
                        @endif
                    </div>

                    @if(!empty($currentInstitute))
                        <span class="text-muted small">{{ $currentInstitute }}</span>
                    @endif
                </div>
            @endif

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

                            @php
                                $sessionRows = method_exists($sessions, 'getCollection') ? $sessions->getCollection() : collect($sessions);
                                $groupedSessions = $sessionRows
                                    ->sortBy([
                                        fn ($session) => $session->student->institute ?? $session->teacher->institute ?? $session->assessment->institute ?? '',
                                        fn ($session) => trim(($session->student->class ?? $session->assessment->assigned_class ?? '') . ' ' . ($session->student->section ?? '')),
                                    ])
                                    ->groupBy(fn ($session) => $session->student->institute ?? $session->teacher->institute ?? $session->assessment->institute ?? 'Unassigned Institute');
                            @endphp

                            @forelse($groupedSessions as $instituteName => $instituteSessions)
                                <tr class="table-primary">
                                    <td colspan="8" class="fw-semibold">
                                        {{ $instituteName }} · {{ $instituteSessions->count() }} session{{ $instituteSessions->count() == 1 ? '' : 's' }}
                                    </td>
                                </tr>

                                @foreach($instituteSessions->groupBy(fn ($session) => trim(($session->student->class ?? $session->assessment->assigned_class ?? '') . ' ' . ($session->student->section ?? '')) ?: 'Unassigned Class') as $classLabel => $classSessions)
                                    <tr class="table-light">
                                        <td colspan="8" class="fw-semibold ps-4">
                                            {{ $classLabel }} · {{ $classSessions->count() }} session{{ $classSessions->count() == 1 ? '' : 's' }}
                                        </td>
                                    </tr>

                                    @foreach($classSessions as $session)

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

                                    @endforeach
                                @endforeach

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

                @if(method_exists($sessions, 'links'))
                    <div class="px-3 pb-3">
                        {{ $sessions->links('pagination::bootstrap-5') }}
                    </div>
                @endif

            </div>
        </div> 
    </div>
</div>

@endsection
