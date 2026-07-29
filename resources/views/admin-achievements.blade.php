@extends('layouts.app')

@section('content')

<div class="container-fluid">

    <div class="row">

        @include('layouts.sidebar')

        <div class="col-md-10 col-lg-10 p-4">
            <div class="page-header mb-4">
                <h3 class="fw-bold mb-4">
                    Student Achievements
                </h3>
            </div>

            @if(session('success'))

                <div class="alert alert-success">

                    {{ session('success') }}

                </div>

            @endif

            @include('partials.section-navigator', ['sectionPager' => $sectionPager ?? null])

            <div class="card shadow border-0">

                <div class="card-body">

                    <div class="table-responsive">

                        <table class="table table-bordered align-middle">

                            <thead class="table-light">

                                <tr>

                                    <th>ID</th>

                                    <th>Student ID</th>

                                    <th>Student</th>

                                    <th>Type</th>

                                    <th>Title</th>

                                    <th>Organizer</th>

                                    <th>Status</th>

                                    <th>Certificate</th>

                                    <th>Actions</th>

                                </tr>

                            </thead>

                            <tbody>

                                @php
                                    $achievementRows = method_exists($achievements, 'getCollection') ? $achievements->getCollection() : collect($achievements);
                                    $groupedAchievements = $achievementRows
                                        ->sortBy([
                                            fn ($achievement) => $achievement->student->institute ?? '',
                                            fn ($achievement) => $achievement->student->class ?? '',
                                            fn ($achievement) => $achievement->student->section ?? '',
                                            fn ($achievement) => $achievement->student->name ?? '',
                                        ])
                                        ->groupBy(fn ($achievement) => $achievement->student->institute ?? 'Student Deleted / Unassigned Institute');
                                @endphp

                                @forelse($groupedAchievements as $instituteName => $instituteAchievements)

                                    <tr class="table-primary">
                                        <td colspan="9" class="fw-semibold">
                                            {{ $instituteName }} · {{ $instituteAchievements->count() }} achievement{{ $instituteAchievements->count() == 1 ? '' : 's' }}
                                        </td>
                                    </tr>

                                    @foreach($instituteAchievements->groupBy(fn ($achievement) => trim(($achievement->student->class ?? '') . ' ' . ($achievement->student->section ?? '')) ?: 'Student Deleted / Unassigned Class') as $classLabel => $classAchievements)

                                        <tr class="table-light">
                                            <td colspan="9" class="fw-semibold ps-4">
                                                {{ $classLabel }} · {{ $classAchievements->count() }} achievement{{ $classAchievements->count() == 1 ? '' : 's' }}
                                            </td>
                                        </tr>

                                        @foreach($classAchievements as $achievement)

                                    <tr>

                                        <td>
                                            {{ $achievement->id }}
                                        </td>

                                        <td>
                                            {{ $achievement->student_id }}
                                        </td>

                                        <td>
                                            {{ $achievement->student->name ?? 'Student Deleted' }}
                                        </td>

                                        <td>
                                            {{ $achievement->achievement_type }}
                                        </td>

                                        <td>
                                            {{ $achievement->title }}
                                        </td>

                                        <td>
                                            {{ $achievement->organizer }}
                                        </td>

                                        <td>

                                            @if($achievement->verification_status == 'Approved')

                                                <span class="badge bg-success">
                                                    Approved
                                                </span>

                                            @elseif($achievement->verification_status == 'Rejected')

                                                <span class="badge bg-danger">
                                                    Rejected
                                                </span>

                                            @else

                                                <span class="badge bg-warning text-dark">
                                                    Pending
                                                </span>

                                            @endif

                                        </td>

                                        <td>

                                            <a href="{{ asset('storage/' . $achievement->certificate_file) }}"
                                            target="_blank"
                                            class="btn btn-sm btn-outline-primary">

                                                View

                                            </a>

                                        </td>

                                        <td>

                                            <div class="d-flex gap-2">

                                                <form action="{{ route('admin.achievements.approve', $achievement->id) }}"
                                                    method="POST">

                                                    @csrf

                                                    <button class="btn btn-success btn-sm">

                                                        Approve

                                                    </button>

                                                </form>

                                                <form action="{{ route('admin.achievements.reject', $achievement->id) }}"
                                                    method="POST">

                                                    @csrf

                                                    <button class="btn btn-sm btn-outline-danger">

                                                        Reject

                                                    </button>

                                                </form>

                                            </div>

                                        </td>

                                    </tr>

                                        @endforeach

                                    @endforeach

                                @empty

                                    <tr>

                                        <td colspan="9"
                                            class="text-center text-muted py-4">

                                            No achievements found

                                        </td>

                                    </tr>

                                @endforelse

                            </tbody>

                        </table>

                    </div>

                </div>

                @if(method_exists($achievements, 'links'))
                    <div class="px-3 pb-3">
                        {{ $achievements->links('pagination::bootstrap-5') }}
                    </div>
                @endif

            </div>
        </div>
    </div>
</div>

@endsection
