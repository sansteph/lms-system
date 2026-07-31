@extends('layouts.app')

@section('content')

<div class="container-fluid">
    <div class="row">

        @include('layouts.sidebar')

        <div class="col-md-10 col-lg-10 p-4">

            <div class="page-header mb-4">
                <h2 class="mb-1">Assessment Review Monitoring</h2>
                <p class="text-muted mb-0">
                    Monitor assessment review status, scores, badges, and pending manual evaluations.
                </p>
            </div>

            @include('partials.section-navigator', [
                'sectionPager' => $sectionPager ?? null,
                'sectionDescription' => 'Browse assessment review monitoring institute by institute.',
            ])

            @include('partials.section-navigator', [
                'sectionPager' => $classSectionPager ?? null,
                'sectionDescription' => 'Browse assessment reviews one class at a time inside the selected institute.',
            ])

            @include('partials.section-navigator', [
                'sectionPager' => $studentSectionPager ?? null,
                'sectionDescription' => 'Showing assessment reviews for this section only.',
            ])

            @if(!empty($selectedStudentClass))
                <div class="alert alert-light border d-flex justify-content-between align-items-center flex-wrap gap-2">
                    <div>
                        <span class="fw-semibold">Current assessment review scope:</span>
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

                    <table class="table table-bordered table-hover align-middle">
                        <thead class="table-light">
                            <tr>
                                <th>Student</th>
                                <th>Assessment</th>
                                <th>Score</th>
                                <th>Percentage</th>
                                <th>Status</th>
                                <th>Badge</th>
                                <th>Submitted At</th>
                            </tr>
                        </thead>

                        <tbody>
                            @php
                                $resultRows = method_exists($results, 'getCollection') ? $results->getCollection() : collect($results);
                                $groupedResults = $resultRows
                                    ->sortBy([
                                        fn ($result) => $result->student->institute ?? '',
                                        fn ($result) => $result->student->class ?? '',
                                        fn ($result) => $result->student->section ?? '',
                                        fn ($result) => $result->student->name ?? '',
                                    ])
                                    ->groupBy(fn ($result) => $result->student->institute ?? 'Student Deleted / Unassigned Institute');
                            @endphp

                            @forelse($groupedResults as $instituteName => $instituteResults)
                                <tr class="table-primary">
                                    <td colspan="7" class="fw-semibold">
                                        {{ $instituteName }} · {{ $instituteResults->count() }} result{{ $instituteResults->count() == 1 ? '' : 's' }}
                                    </td>
                                </tr>

                                @foreach($instituteResults->groupBy(fn ($result) => trim(($result->student->class ?? '') . ' ' . ($result->student->section ?? '')) ?: 'Student Deleted / Unassigned Class') as $classLabel => $classResults)
                                    <tr class="table-light">
                                        <td colspan="7" class="fw-semibold ps-4">
                                            {{ $classLabel }} · {{ $classResults->count() }} result{{ $classResults->count() == 1 ? '' : 's' }}
                                        </td>
                                    </tr>

                                    @foreach($classResults as $result)
                                        <tr>
                                            <td>{{ $result->student->name ?? 'Student Deleted' }}</td>
                                            <td>{{ $result->assessment->assessment_title ?? 'Assessment Deleted' }}</td>
                                            <td>{{ $result->score }}/{{ $result->total_marks }}</td>
                                            <td>{{ number_format($result->percentage, 2) }}%</td>

                                            <td>
                                                @if($result->status == 'Pending Review')
                                                    <span class="badge bg-warning text-dark">Pending Review</span>
                                                @elseif($result->status == 'Completed')
                                                    <span class="badge bg-success">Completed</span>
                                                @else
                                                    <span class="badge bg-secondary">{{ $result->status }}</span>
                                                @endif
                                            </td>

                                            <td>
                                                @if($result->badge)
                                                    <span class="badge bg-primary">{{ $result->badge }}</span>
                                                @else
                                                    <span class="badge bg-light text-dark">No Badge</span>
                                                @endif
                                            </td>

                                            <td>{{ $result->created_at->format('d-m-Y h:i A') }}</td>
                                        </tr>
                                    @endforeach
                                @endforeach
                            @empty
                                <tr>
                                    <td colspan="7" class="text-center text-muted">
                                        No assessment review data found.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>

                </div>

                @if(method_exists($results, 'links'))
                    <div class="px-3 pb-3">
                        {{ $results->links('pagination::bootstrap-5') }}
                    </div>
                @endif
            </div>

        </div>

    </div>
</div>

@endsection
