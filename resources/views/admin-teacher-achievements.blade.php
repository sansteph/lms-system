@extends('layouts.app')

@section('content')

<div class="container-fluid">
    <div class="row">

        @include('layouts.sidebar')

        <div class="col-md-10 col-lg-10 p-4">
            <div class="page-header mb-4">
                <h3 class="fw-bold mb-1">
                    STEM Engineer Achievements
                </h3>
                <p class="text-muted mb-0">
                    Review and approve achievements submitted by STEM Engineers.
                </p>
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
                                    <th>STEM Engineer</th>
                                    <th>Institute</th>
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
                                            fn ($achievement) => $achievement->teacher->institute ?? '',
                                            fn ($achievement) => $achievement->teacher->name ?? '',
                                        ])
                                        ->groupBy(fn ($achievement) => $achievement->teacher->institute ?? 'STEM Engineer Deleted / Unassigned Institute');
                                @endphp

                                @forelse($groupedAchievements as $instituteName => $instituteAchievements)
                                    <tr class="table-primary">
                                        <td colspan="9" class="fw-semibold">
                                            {{ $instituteName }} &middot; {{ $instituteAchievements->count() }} achievement{{ $instituteAchievements->count() == 1 ? '' : 's' }}
                                        </td>
                                    </tr>

                                    @foreach($instituteAchievements as $achievement)
                                        <tr>
                                            <td>{{ $achievement->id }}</td>

                                            <td>
                                                <strong>{{ $achievement->teacher->name ?? 'STEM Engineer Deleted' }}</strong>
                                                @if($achievement->teacher)
                                                    <br>
                                                    <small class="text-muted">{{ $achievement->teacher->user_id }}</small>
                                                @endif
                                            </td>

                                            <td>{{ $achievement->teacher->institute ?? 'N/A' }}</td>
                                            <td>{{ $achievement->achievement_type }}</td>
                                            <td>{{ $achievement->title }}</td>
                                            <td>{{ $achievement->organizer ?? 'N/A' }}</td>

                                            <td>
                                                @if($achievement->verification_status == 'Approved')
                                                    <span class="badge bg-success">Approved</span>
                                                @elseif($achievement->verification_status == 'Rejected')
                                                    <span class="badge bg-danger">Rejected</span>
                                                @else
                                                    <span class="badge bg-warning text-dark">Pending</span>
                                                @endif
                                            </td>

                                            <td>
                                                @if($achievement->certificate_file)
                                                    <a href="{{ asset('storage/' . $achievement->certificate_file) }}"
                                                       target="_blank"
                                                       class="btn btn-sm btn-outline-primary">
                                                        View
                                                    </a>
                                                @else
                                                    <span class="text-muted">No file</span>
                                                @endif
                                            </td>

                                            <td>
                                                <div class="d-flex gap-2 flex-wrap">
                                                    <form action="{{ route('admin.teacher-achievements.approve', $achievement->id) }}"
                                                          method="POST">
                                                        @csrf
                                                        <button class="btn btn-success btn-sm">
                                                            Approve
                                                        </button>
                                                    </form>

                                                    <form action="{{ route('admin.teacher-achievements.reject', $achievement->id) }}"
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
                                @empty
                                    <tr>
                                        <td colspan="9" class="text-center text-muted py-4">
                                            No STEM Engineer achievements found.
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
