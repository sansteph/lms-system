@extends('layouts.app')

@section('content')
<div class="container-fluid py-4">
    <div class="row justify-content-center">
        <div class="col-12 col-xl-10">
            <div class="card shadow border-0 mb-4">
                <div class="card-body p-4">
                    <div class="d-flex justify-content-between align-items-start flex-wrap gap-3">
                        <div>
                            <div class="text-uppercase small fw-bold text-primary mb-2">AI-powered certification path</div>
                            <h3 class="mb-2">Component Mastery</h3>
                            <p class="text-muted mb-0">Build mastery across practical topics, complete a rigorous assessment, and qualify for the normal certificate approval process.</p>
                        </div>
                        <a href="{{ route('student.dashboard') }}" class="btn btn-outline-secondary">Back</a>
                    </div>
                </div>
            </div>

            @if(session('success'))
                <div class="alert alert-success">{{ session('success') }}</div>
            @endif

            @if(session('error'))
                <div class="alert alert-danger">{{ session('error') }}</div>
            @endif

            @forelse($masteryAssessments as $item)
                @php
                    $offer = $item['offer'];
                    $assessment = $item['assessment'];
                    $result = $item['result'];
                    $isReady = $assessment && !$result;
                @endphp
                <div class="card shadow-sm border-0 mb-3">
                    <div class="card-body p-4">
                        <div class="d-flex justify-content-between align-items-start flex-wrap gap-3">
                            <div>
                                <div class="d-flex align-items-center gap-2 mb-2">
                                    <span class="badge rounded-pill bg-primary">Component</span>
                                    <span class="badge rounded-pill bg-light text-dark border">Course completed</span>
                                </div>
                                <h4 class="mb-2">Basics in {{ $offer['component_label'] }}</h4>
                                <p class="text-muted mb-2">{{ implode(', ', $offer['course_titles']) }}</p>
                                <p class="text-muted mb-2">{{ collect($offer['content_titles'])->take(5)->implode(', ') }}</p>
                                <div class="small fw-semibold {{ $result?->passed ? 'text-success' : 'text-secondary' }}">
                                    Status: {{ $item['status'] }}
                                </div>
                                @if($result && $result->status === 'Completed')
                                    <div class="small text-muted mt-1">Score: {{ $result->percentage }}%</div>
                                @endif
                            </div>

                            <div class="d-flex flex-column gap-2" style="min-width: 190px;">
                                @if($isReady)
                                    <form method="POST" action="{{ route('assessment.session.start', $assessment->id) }}">
                                        @csrf
                                        <button type="submit" class="btn btn-primary w-100">Take Assessment</button>
                                    </form>
                                @elseif(!$result)
                                    <form method="POST" action="{{ route('student.component-assessments.generate', $offer['component_key']) }}">
                                        @csrf
                                        <button type="submit" class="btn btn-outline-primary w-100">Prepare Assessment</button>
                                    </form>
                                @else
                                    <a href="{{ route('student.history') }}" class="btn btn-outline-secondary">View Result</a>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
            @empty
                <div class="card shadow-sm border-0">
                    <div class="card-body p-5 text-center">
                        <i class="fa-solid fa-microchip fa-2x text-primary mb-3"></i>
                        <h4>No Component Mastery assessments yet</h4>
                        <p class="text-muted mb-0">No completed-course microcontroller or microprocessor assessments are available yet.</p>
                    </div>
                </div>
            @endforelse
        </div>
    </div>
</div>
@endsection
