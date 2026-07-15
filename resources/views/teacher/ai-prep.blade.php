@extends('layouts.app')

@section('content')

<div class="container-fluid">
    <div class="row">
        @include('layouts.teacher-sidebar')

        <div class="col-md-10 col-lg-10 p-4">
            <div class="d-flex justify-content-between align-items-center flex-wrap gap-3 mb-4">
                <div>
                    <h2 class="fw-bold mb-1">AI Prep Study</h2>
                    <p class="text-muted mb-0">
                        Study the AI summary and key points before taking the prep quiz.
                    </p>
                </div>

                <a href="{{ route('teacher.content') }}" class="btn btn-outline-secondary">
                    <i class="fa fa-arrow-left me-2"></i>
                    Back
                </a>
            </div>

            @if(session('success'))
                <div class="alert alert-success">{{ session('success') }}</div>
            @endif

            @if(session('error'))
                <div class="alert alert-danger">{{ session('error') }}</div>
            @endif

            @if($latestAttempt)
                <div class="alert {{ $latestAttempt->status == 'passed' ? 'alert-success' : 'alert-warning' }}">
                    <div class="fw-bold mb-1">
                        Latest Prep Result:
                        {{ ucfirst($latestAttempt->status) }}
                        @if(!is_null($latestAttempt->percentage))
                            - {{ number_format($latestAttempt->percentage, 2) }}%
                        @endif
                    </div>
                    @if($latestAttempt->feedback)
                        <div>{{ $latestAttempt->feedback }}</div>
                    @endif
                </div>
            @endif

            <div class="card shadow border-0">
                <div class="card-body p-4 p-lg-5">
                    <div class="text-muted small fw-semibold text-uppercase mb-2">
                        Teaching Content
                    </div>
                    <h3 class="fw-bold mb-4">{{ $content->content_title }}</h3>

                    <h5 class="fw-bold mb-3">AI Summary</h5>
                    <div class="ai-summary-text mb-4">
                        {{ $summary->summary }}
                    </div>

                    @if(!empty($summary->key_points))
                        <h5 class="fw-bold mb-3">Key Points</h5>
                        <div class="row g-3 mb-4">
                            @foreach($summary->key_points as $point)
                                <div class="col-md-6">
                                    <div class="border rounded p-3 h-100 bg-light">
                                        <i class="fa fa-check-circle text-success me-2"></i>
                                        {{ $point }}
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @endif

                    <div class="d-flex justify-content-end gap-2">
                        <a href="{{ route('teacher.content') }}" class="btn btn-outline-secondary">
                            Cancel
                        </a>
                        <a href="{{ route('teacher.ai-prep.quiz', $content->id) }}" class="btn btn-success">
                            Start Prep Quiz
                            <i class="fa fa-arrow-right ms-2"></i>
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
    .ai-summary-text {
        color: #39465f;
        font-size: 1rem;
        line-height: 1.75;
        white-space: pre-line;
    }
</style>

@endsection
