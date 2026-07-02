@extends('layouts.app')

@section('content')

<div class="container-fluid py-4">
    <div class="row justify-content-center">
        <div class="col-12 col-xl-10">
            <div class="card shadow border-0">
                <div class="card-body p-4">
                    <div class="d-flex justify-content-between align-items-start flex-wrap gap-3 mb-4">
                        <div>
                            <h3 class="mb-1">Student Assessment</h3>
                            <p class="text-muted mb-0">Select an approved assessment, review the rules, and start when ready.</p>
                        </div>

                        <a href="{{ route('student.dashboard') }}" class="btn btn-outline-secondary">
                            Back
                        </a>
                    </div>

                    @if(session('success'))
                        <div class="alert alert-success">{{ session('success') }}</div>
                    @endif

                    @if(session('error'))
                        <div class="alert alert-danger">{{ session('error') }}</div>
                    @endif

                    <form method="GET" action="{{ route('student.assessment') }}">
                        <div class="row g-3 align-items-end">
                            <div class="col-lg-9">
                                <label class="form-label fw-semibold">Assessment</label>
                                <select name="assessment_id" class="form-select" required>
                                    <option value="">Select Assessment</option>
                                    @foreach($assessments as $assessment)
                                        <option value="{{ $assessment->id }}" {{ request('assessment_id') == $assessment->id ? 'selected' : '' }}>
                                            {{ $assessment->assessment_title }} - {{ $assessment->assessment_category ?? 'Monthly' }} - {{ $assessment->assessment_date ? \Carbon\Carbon::parse($assessment->assessment_date)->format('d M Y') : 'Date Not Set' }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="col-lg-3">
                                <button type="submit" class="btn btn-primary w-100">
                                    Load Assessment
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>

            @if($selectedAssessment)
                <div class="card shadow border-0 mt-4">
                    <div class="card-body p-4">
                        <div class="row align-items-center g-4">
                            <div class="col-lg-8">
                                <h4 class="mb-2">{{ $selectedAssessment->assessment_title }}</h4>

                                <div class="d-flex flex-wrap gap-2 mb-3">
                                    <span class="badge bg-light text-dark border">{{ $selectedAssessment->assessment_category ?? 'Monthly' }}</span>
                                    <span class="badge bg-light text-dark border">{{ $selectedAssessment->total_marks }} marks</span>
                                    <span class="badge bg-light text-dark border">{{ $selectedAssessment->duration }} minutes</span>
                                    <span class="badge bg-light text-dark border">
                                        {{ $selectedAssessment->assessment_date ? \Carbon\Carbon::parse($selectedAssessment->assessment_date)->format('d M Y') : 'Date Not Set' }}
                                    </span>
                                </div>

                                <div class="alert alert-warning mb-0">
                                    The assessment opens on a dedicated page. Tab switching, refresh, copy/paste, right-click, and selection shortcuts are restricted. Three violations will auto-submit the assessment.
                                </div>
                            </div>

                            <div class="col-lg-4">
                                <form method="POST" action="{{ route('assessment.session.start', $selectedAssessment->id) }}">
                                    @csrf
                                    <button type="submit" class="btn btn-success btn-lg w-100">
                                        Start Assessment
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            @endif
        </div>
    </div>
</div>

@endsection
