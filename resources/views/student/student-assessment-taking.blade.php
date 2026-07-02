@extends('layouts.app')

@section('content')

<style>
    body {
        background: #f5f7fb;
    }

    .taking-shell {
        min-height: 100vh;
        background: #f5f7fb;
    }

    .taking-card {
        border: 0;
        border-radius: 10px;
        box-shadow: 0 10px 28px rgba(15, 23, 42, 0.08);
    }

    .paper-frame {
        width: 100%;
        height: calc(100vh - 245px);
        min-height: 560px;
        border: 0;
        border-radius: 8px;
        background: #eef2f7;
    }

    .answer-area {
        min-height: calc(100vh - 390px);
        resize: vertical;
        line-height: 1.65;
        font-size: 15px;
    }

    .timer-pill {
        border-radius: 999px;
        background: #fff7ed;
        border: 1px solid #fed7aa;
        color: #9a3412;
        font-weight: 700;
        padding: 9px 14px;
        white-space: nowrap;
    }

    @media (max-width: 991.98px) {
        .paper-frame {
            height: 520px;
            min-height: 420px;
        }

        .answer-area {
            min-height: 420px;
        }
    }
</style>

@php
    $extension = strtolower(pathinfo($assessment->file_path, PATHINFO_EXTENSION));
    $previewExtensions = ['ppt', 'pptx', 'doc', 'docx'];
    $paperVariant = in_array($extension, $previewExtensions) && $assessment->question_paper_preview_path
        ? 'preview'
        : 'file';
    $paperUrl = route('assessment.paper', [$assessment->id, $paperVariant]) . '#toolbar=0&navpanes=0&scrollbar=1';
@endphp

<div class="taking-shell py-4">
    <div class="container-fluid">
        <div class="card taking-card mb-4">
            <div class="card-body p-4">
                <div class="d-flex justify-content-between align-items-center flex-wrap gap-3">
                    <div>
                        <h3 class="mb-2">{{ $assessment->assessment_title }}</h3>
                        <div class="d-flex flex-wrap gap-2">
                            <span class="badge bg-light text-dark border">{{ $assessment->assessment_category ?? 'Monthly' }}</span>
                            <span class="badge bg-light text-dark border">{{ $assessment->total_marks }} marks</span>
                            <span class="badge bg-light text-dark border">{{ $assessment->duration }} minutes</span>
                            <span class="badge bg-light text-dark border">
                                {{ $assessment->assessment_date ? \Carbon\Carbon::parse($assessment->assessment_date)->format('d M Y') : 'Date Not Set' }}
                            </span>
                        </div>
                    </div>

                    <div class="timer-pill">
                        Time Left: <span id="timer">{{ $assessment->duration }}:00</span>
                    </div>
                </div>
            </div>
        </div>

        @if(session('error'))
            <div class="alert alert-danger">{{ session('error') }}</div>
        @endif

        @if($errors->any())
            <div class="alert alert-danger">Please type your answer before submitting.</div>
        @endif

        <form method="POST" action="{{ route('assessment-results.store') }}" id="assessmentForm">
            @csrf
            <input type="hidden" name="student_id" value="{{ session('student_id') }}">
            <input type="hidden" name="assessment_id" value="{{ $assessment->id }}">
            <input type="hidden" name="total_marks" value="{{ $assessment->total_marks }}">
            <input type="hidden" name="auto_submitted" id="autoSubmitted" value="0">

            <div class="row g-4 align-items-start">
                <div class="col-lg-7">
                    <div class="card taking-card">
                        <div class="card-header bg-white d-flex justify-content-between align-items-center">
                            <div>
                                <strong>Question Paper</strong>
                                <div class="small text-muted">Reference this while typing your answer.</div>
                            </div>
                            <span class="badge bg-light text-dark border">View only</span>
                        </div>

                        <div class="card-body p-3">
                            @if($extension == 'pdf' || $paperVariant == 'preview')
                                <iframe src="{{ $paperUrl }}"
                                        class="paper-frame"
                                        title="Question Paper"
                                        oncontextmenu="return false;">
                                </iframe>
                            @elseif(in_array($extension, ['jpg', 'jpeg', 'png', 'webp']))
                                <img src="{{ route('assessment.paper', [$assessment->id, $paperVariant]) }}"
                                     class="img-fluid rounded border"
                                     alt="Question Paper"
                                     oncontextmenu="return false;">
                            @else
                                <div class="alert alert-warning mb-0">
                                    Inline preview is not available for this question paper type.
                                </div>
                            @endif
                        </div>
                    </div>
                </div>

                <div class="col-lg-5">
                    <div class="card taking-card">
                        <div class="card-header bg-white">
                            <strong>Your Typed Answer</strong>
                            <div class="small text-muted">Answer submission is typed-response only.</div>
                        </div>

                        <div class="card-body p-4">
                            <textarea name="answer_text"
                                      id="answerText"
                                      class="form-control answer-area"
                                      placeholder="Type your answer here.">{{ old('answer_text') }}</textarea>

                            <div class="alert alert-light border small mt-3">
                                Copy/paste, tab switching, refresh, right-click, and selection shortcuts are restricted during the assessment.
                            </div>

                            <button type="submit" class="btn btn-success btn-lg w-100">
                                Submit Assessment
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </form>
    </div>
</div>

<script>
    let minutes = parseInt("{{ $assessment->duration }}");
    let seconds = 0;
    let submitting = false;

    const timerElement = document.getElementById("timer");
    const assessmentForm = document.getElementById("assessmentForm");
    const autoSubmitted = document.getElementById("autoSubmitted");
    const violationUrl = "{{ route('assessment.session.violation', $assessmentSession->id) }}";
    const csrfToken = "{{ csrf_token() }}";

    function submitAssessment(isAuto = false) {
        if (!submitting) {
            submitting = true;
            autoSubmitted.value = isAuto ? "1" : "0";
            assessmentForm.submit();
        }
    }

    assessmentForm.addEventListener("submit", function () {
        submitting = true;
    });

    const countdown = setInterval(function () {
        if (seconds === 0) {
            if (minutes === 0) {
                clearInterval(countdown);
                alert("Time is up! Your assessment will be submitted now.");
                submitAssessment(true);
                return;
            }
            minutes--;
            seconds = 59;
        } else {
            seconds--;
        }
        timerElement.innerText = minutes + ":" + (seconds < 10 ? "0" + seconds : seconds);
    }, 1000);

    function recordViolation() {
        fetch(violationUrl, {
            method: "POST",
            headers: {
                "X-CSRF-TOKEN": csrfToken,
                "Accept": "application/json",
                "Content-Type": "application/json"
            },
            body: JSON.stringify({})
        })
        .then(response => response.json())
        .then(data => {
            if (data.auto_submit) {
                alert("Assessment automatically submitted due to repeated violations.");
                submitAssessment(true);
            } else {
                alert("Warning: Do not switch tabs/windows during the assessment. Violation " + data.violation_count + " of 3.");
            }
        });
    }

    document.addEventListener("visibilitychange", function () {
        if (document.hidden && !submitting) {
            recordViolation();
        }
    });

    window.addEventListener("beforeunload", function (event) {
        if (!submitting) {
            event.preventDefault();
            event.returnValue = "";
        }
    });

    document.addEventListener("contextmenu", function (event) {
        event.preventDefault();
    });

    document.addEventListener("copy", function (event) {
        event.preventDefault();
    });

    document.addEventListener("cut", function (event) {
        event.preventDefault();
    });

    document.addEventListener("paste", function (event) {
        event.preventDefault();
    });

    document.addEventListener("keydown", function (event) {
        const key = event.key.toLowerCase();

        if ((event.ctrlKey || event.metaKey) && ["a", "c", "p", "s", "u", "v", "x"].includes(key)) {
            event.preventDefault();
        }

        if (key === "f5" || ((event.ctrlKey || event.metaKey) && key === "r")) {
            event.preventDefault();
        }
    });
</script>

@endsection
