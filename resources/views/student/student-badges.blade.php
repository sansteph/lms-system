@php
    use Illuminate\Support\Str;
@endphp

@extends('layouts.app')

@section('content')

<div class="container-fluid">

    <div class="row">

        @include('layouts.student-sidebar')

        <div class="col-md-10 col-lg-10 p-4">

            {{-- HEADER --}}

            <div class="d-flex justify-content-between align-items-center flex-wrap gap-3 mb-4">

                <div>

                    <h2 class="fw-bold mb-1">
                        My Achievements
                    </h2>

                    <p class="text-muted mb-0">
                        Track badges, certificates, competitions, and extracurricular achievements.
                    </p>

                </div>

                <a href="{{ route('student.achievements.create') }}"
                   class="btn btn-primary">

                    <i class="fa fa-plus me-2"></i>

                    Upload Achievement

                </a>

            </div>

            @if(session('success'))

                <div class="alert alert-success">

                    {{ session('success') }}

                </div>

            @endif

            @if(session('error'))

                <div class="alert alert-danger">

                    {{ session('error') }}

                </div>

            @endif


            {{-- STATS SECTION --}}

            <div class="row g-4 mb-5">

                <div class="col-md-6 col-lg-3">

                    <div class="card shadow-sm border-0 h-100">

                        <div class="card-body d-flex align-items-center gap-3">

                            <div class="achievement-stat-icon bg-primary-subtle text-primary">

                                <i class="fa fa-trophy"></i>

                            </div>

                            <div>

                                <h6 class="mb-1 text-muted">
                                    Total Badges
                                </h6>

                                <h3 class="mb-0">
                                    {{ $results->count() }}
                                </h3>

                            </div>

                        </div>

                    </div>

                </div>

                <div class="col-md-6 col-lg-3">

                    <div class="card shadow-sm border-0 h-100">

                        <div class="card-body d-flex align-items-center gap-3">

                            <div class="achievement-stat-icon bg-warning-subtle text-warning">

                                <i class="fa fa-medal"></i>

                            </div>

                            <div>

                                <h6 class="mb-1 text-muted">
                                    Gold Badges
                                </h6>

                                <h3 class="mb-0">
                                    {{ $goldCount }}
                                </h3>

                            </div>

                        </div>

                    </div>

                </div>

                <div class="col-md-6 col-lg-3">

                    <div class="card shadow-sm border-0 h-100">

                        <div class="card-body d-flex align-items-center gap-3">

                            <div class="achievement-stat-icon bg-secondary-subtle text-secondary">

                                <i class="fa fa-award"></i>

                            </div>

                            <div>

                                <h6 class="mb-1 text-muted">
                                    Silver Badges
                                </h6>

                                <h3 class="mb-0">
                                    {{ $silverCount }}
                                </h3>

                            </div>

                        </div>

                    </div>

                </div>

                <div class="col-md-6 col-lg-3">

                    <div class="card shadow-sm border-0 h-100">

                        <div class="card-body d-flex align-items-center gap-3">

                            <div class="achievement-stat-icon bg-danger-subtle text-danger">

                                <i class="fa fa-certificate"></i>

                            </div>

                            <div>

                                <h6 class="mb-1 text-muted">
                                    Bronze Badges
                                </h6>

                                <h3 class="mb-0">
                                    {{ $bronzeCount }}
                                </h3>

                            </div>

                        </div>

                    </div>

                </div>

            </div>


            {{-- EARNED BADGES --}}

            <div class="card shadow border-0 mb-5">

                <div class="card-body p-4">

                    <div class="mb-4">

                        <h5 class="mb-1">
                            Earned Badges
                        </h5>

                        <p class="text-muted mb-0">
                            Badges earned through assessments and activities.
                        </p>

                    </div>

                    <div class="row g-4">

                        @forelse($results as $result)

                            <div class="col-md-6 col-lg-4">

                                <div class="card border-0 shadow-sm text-center h-100 p-4">

                                    <div class="mb-3">

                                        @if($result->badge == 'Gold')

                                            <i class="fa fa-trophy text-warning"
                                               style="font-size: 64px;"></i>

                                        @elseif($result->badge == 'Silver')

                                            <i class="fa fa-medal text-secondary"
                                               style="font-size: 64px;"></i>

                                        @elseif($result->badge == 'Bronze')

                                            <i class="fa fa-award text-danger"
                                               style="font-size: 64px;"></i>

                                        @endif

                                    </div>

                                    <h6 class="fw-semibold mb-2">

                                        {{ Str::limit($result->assessment->assessment_title ?? 'Assessment Deleted', 45) }}

                                    </h6>

                                    <p class="text-muted small mb-3">

                                        Score:
                                        {{ $result->score }}/{{ $result->total_marks }}

                                    </p>

                                    <span class="
                                        badge
                                        @if($result->badge == 'Gold')
                                            bg-warning text-dark
                                        @elseif($result->badge == 'Silver')
                                            bg-secondary
                                        @elseif($result->badge == 'Bronze')
                                            bg-danger
                                        @endif
                                    ">

                                        {{ $result->badge }} Badge

                                    </span>

                                </div>

                            </div>

                        @empty

                            <div class="col-12">

                                <div class="text-center py-5">

                                    <i class="fa fa-medal text-muted mb-4"
                                       style="font-size: 80px;"></i>

                                    <h5>
                                        No badges earned yet
                                    </h5>

                                    <p class="text-muted mb-4">
                                        Complete assessments to unlock achievement badges.
                                    </p>

                                    <a href="{{ route('student.assessment') }}"
                                       class="btn btn-primary">

                                        Take Assessment

                                    </a>

                                </div>

                            </div>

                        @endforelse

                    </div>

                </div>

            </div>


            {{-- CERTIFICATE PROGRESS --}}

            <div class="card shadow border-0 mb-5">

                <div class="card-body p-4">

                    <div class="d-flex justify-content-between align-items-center flex-wrap gap-3 mb-4">

                        <div>

                            <h5 class="mb-1">
                                Certificate Progress
                            </h5>

                            <p class="text-muted mb-0">
                                Earn 5 badges to unlock your certificate eligibility.
                            </p>

                        </div>

                        @if($certificateEligible)

                            <span class="badge bg-success px-3 py-2">

                                Eligible

                            </span>

                        @else

                            <span class="badge bg-warning text-dark px-3 py-2">

                                In Progress

                            </span>

                        @endif

                    </div>

                    <div class="progress mb-3"
                         style="height: 14px; border-radius: 10px;">

                        <div class="progress-bar"
                             role="progressbar"
                             style="width: {{ min(($results->count() / 5) * 100, 100) }}%">

                        </div>

                    </div>

                    <div class="d-flex justify-content-between flex-wrap gap-2 mb-4">

                        <small class="text-muted">

                            {{ $results->count() }} / 5 badges earned

                        </small>

                        @if($certificateEligible)

                            <small class="text-success fw-semibold">

                                Certificate unlocked

                            </small>

                        @else

                            <small class="text-primary fw-semibold">

                                Keep progressing

                            </small>

                        @endif

                    </div>

                    @if(
                        $certificateEligible &&
                        $certificate &&
                        $certificate->status != 'Revoked'
                    )

                        <div class="alert alert-success mb-0">

                            <div class="d-flex justify-content-between align-items-center flex-wrap gap-3">

                                <div>

                                    <strong>

                                        Certificate Generated

                                    </strong>

                                    <br>

                                    <small>

                                        Certificate Code:
                                        {{ $certificate->certificate_code }}

                                    </small>

                                </div>

                                <a href="{{ route('student.certificate') }}"
                                   class="btn btn-success">

                                    <i class="fa fa-download me-2"></i>

                                    View Certificate

                                </a>

                            </div>

                        </div>

                    @endif

                </div>

            </div>


            {{-- UPLOADED ACHIEVEMENTS --}}

            <div class="card shadow border-0">

                <div class="card-body p-4">

                    <div class="mb-4">

                        <h5 class="mb-1">
                            Uploaded Achievements
                        </h5>

                        <p class="text-muted mb-0">
                            Competitions, workshops, certifications, and extracurricular achievements.
                        </p>

                    </div>

                    <div class="row g-4">

                        @forelse($uploadedAchievements as $achievement)

                            <div class="col-md-6 col-lg-4">

                                <div class="card border-0 shadow-sm h-100">

                                    <div class="card-body d-flex flex-column">

                                        <div class="d-flex justify-content-between align-items-start mb-3">

                                            <div>

                                                <h6 class="fw-bold mb-1">

                                                    {{ $achievement->title }}

                                                </h6>

                                                <small class="text-muted">

                                                    {{ $achievement->achievement_type }}

                                                </small>

                                            </div>

                                            @if($achievement->verification_status == 'Approved')

                                                <span class="badge bg-success">

                                                    Verified

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

                                        </div>

                                        <div class="mb-2">

                                            <small class="text-muted d-block">
                                                Organizer
                                            </small>

                                            <span>
                                                {{ $achievement->organizer ?? '-' }}
                                            </span>

                                        </div>

                                        <div class="mb-2">

                                            <small class="text-muted d-block">
                                                Position
                                            </small>

                                            <span>
                                                {{ $achievement->position ?? '-' }}
                                            </span>

                                        </div>

                                        <div class="mb-3">

                                            <small class="text-muted d-block">
                                                Achievement Date
                                            </small>

                                            <span>

                                                @if($achievement->achievement_date)

                                                    {{ \Carbon\Carbon::parse($achievement->achievement_date)->format('d M Y') }}

                                                @else

                                                    -

                                                @endif

                                            </span>

                                        </div>

                                        <div class="mb-4 flex-grow-1">

                                            <p class="text-muted small mb-0">

                                                {{ $achievement->description }}

                                            </p>

                                        </div>

                                        @if($achievement->certificate_file)

                                            <a href="{{ asset('storage/' . $achievement->certificate_file) }}"
                                               target="_blank"
                                               class="btn btn-outline-primary btn-sm w-100">

                                                <i class="fa fa-file-pdf me-2"></i>

                                                View Certificate

                                            </a>

                                        @endif

                                    </div>

                                </div>

                            </div>

                        @empty

                            <div class="col-12">

                                <div class="text-center py-5">

                                    <i class="fa fa-trophy text-muted mb-3"
                                       style="font-size: 70px;"></i>

                                    <h5>

                                        No achievements uploaded yet

                                    </h5>

                                    <p class="text-muted mb-4">

                                        Upload your competitions, workshops, and certifications.

                                    </p>

                                    <a href="{{ route('student.achievements.create') }}"
                                       class="btn btn-primary">

                                        <i class="fa fa-plus me-2"></i>

                                        Upload Achievement

                                    </a>

                                </div>

                            </div>

                        @endforelse

                    </div>

                </div>

            </div>

        </div>

    </div>

</div>

@endsection