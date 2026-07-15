@extends('layouts.app')

@section('content')

<div class="container-fluid">
    <div class="row">
        @include('layouts.student-sidebar')

        <div class="col-md-10 col-lg-10 p-4">
            <div class="d-flex justify-content-between align-items-center flex-wrap gap-3 mb-4">
                <div>
                    <h2 class="fw-bold mb-1">AI Review Quiz</h2>
                    <p class="text-muted mb-0">
                        Answer from what you studied. The lesson summary is intentionally hidden on this page.
                    </p>
                </div>

                <a href="{{ route('student.content.ai-review', $content->id) }}" class="btn btn-outline-secondary">
                    <i class="fa fa-arrow-left me-2"></i>
                    Back to Study
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
                        Latest AI Review:
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
                <div class="card-header bg-white border-0 px-4 pt-4">
                    <div class="text-muted small fw-semibold text-uppercase mb-2">
                        {{ $content->content_title }}
                    </div>
                    <h5 class="fw-bold mb-1">Review Questions</h5>
                    <p class="text-muted mb-0">
                        Passing score is 40%. Passing this review marks the lesson complete.
                    </p>
                </div>

                <div class="card-body p-4">
                    <form action="{{ route('student.content.ai-review.submit', $content->id) }}" method="POST">
                        @csrf

                        @foreach($quiz->questions()->orderBy('question_order')->get() as $question)
                            <div class="mb-4">
                                <div class="d-flex justify-content-between align-items-start gap-3 mb-2">
                                    <label for="answer-{{ $question->id }}" class="form-label fw-bold mb-0">
                                        {{ $question->question_order }}. {{ $question->question_text }}
                                    </label>
                                    <span class="badge bg-light text-dark border">
                                        {{ $question->marks }} marks
                                    </span>
                                </div>

                                <textarea id="answer-{{ $question->id }}"
                                          name="answers[{{ $question->id }}]"
                                          class="form-control @error('answers.' . $question->id) is-invalid @enderror"
                                          rows="4"
                                          placeholder="Type your answer here">{{ old('answers.' . $question->id) }}</textarea>

                                @error('answers.' . $question->id)
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        @endforeach

                        <div class="d-flex justify-content-end gap-2">
                            <a href="{{ route('student.content.ai-review', $content->id) }}" class="btn btn-outline-secondary">
                                Back to Study
                            </a>
                            <button type="submit" class="btn btn-success">
                                <i class="fa fa-paper-plane me-2"></i>
                                Submit AI Review
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
    textarea.form-control {
        line-height: 1.6;
        resize: vertical;
    }
</style>

@endsection
