@extends('layouts.app')

@section('content')

<div class="container py-4">

    <div class="row justify-content-center">

        <div class="col-lg-8">

            <div class="card shadow border-0">

                <div class="card-body p-4">

                    <h3 class="text-center mb-3">
                        Student Assessment
                    </h3>

                    <p class="text-muted text-center mb-4">
                        Select and complete your assessment.
                    </p>

                    @if(session('error'))
                        <div class="alert alert-danger">
                            {{ session('error') }}
                        </div>
                    @endif

                    <form method="GET"
                          action="{{ route('student.assessment') }}">

                        <div class="mb-3">

                            <label class="form-label">
                                Select Assessment
                            </label>

                            <select name="assessment_id"
                                    class="form-control"
                                    required>

                                <option value="">
                                    Select Assessment
                                </option>

                                @foreach($assessments as $assessment)

                                    <option value="{{ $assessment->id }}"
                                        {{ request('assessment_id') == $assessment->id ? 'selected' : '' }}>

                                        {{ $assessment->assessment_title }}

                                    </option>

                                @endforeach

                            </select>

                        </div>

                        <button type="submit"
                                class="btn btn-primary w-100">

                            Load Assessment

                        </button>

                    </form>

                </div>

            </div>

            @if($selectedAssessment)

                @if(!session('active_assessment_session_id'))

                    <div class="card shadow border-0 mt-4">
                        <div class="card-body text-center">

                            <h5>Assessment Instructions</h5>

                            <ul class="text-start mt-3">
                                <li>Do not switch tabs or windows.</li>
                                <li>Do not refresh the page.</li>
                                <li>Content access will be locked during assessment.</li>
                                <li>3 violations will auto-submit the assessment.</li>
                            </ul>

                            <form method="POST"
                                action="{{ route('assessment.session.start', $selectedAssessment->id) }}">

                                @csrf

                                <button type="submit"
                                        class="btn btn-success">

                                    Start Assessment

                                </button>

                            </form>

                        </div>
                    </div>

                @else

                    <div class="card shadow border-0 mt-4">

                        <div class="card-body">

                            <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">

                                <div>

                                    <h5 class="mb-1">
                                        {{ $selectedAssessment->assessment_title }}
                                    </h5>

                                    <p class="text-muted mb-0">
                                        Total Marks:
                                        {{ $questions->sum('marks') }}

                                        |

                                        Duration:
                                        {{ $selectedAssessment->duration }} Minutes
                                    </p>

                                </div>

                                <div class="alert alert-warning mb-0">

                                    Time Left:
                                    <strong id="timer">
                                        {{ $selectedAssessment->duration }}:00
                                    </strong>

                                </div>

                            </div>

                        </div>

                    </div>
                    <form method="POST"
                      action="{{ route('assessment-results.store') }}"
                      class="mt-4"
                      id="assessmentForm">

                        @csrf

                        <input type="hidden"
                            name="student_id"
                            value="{{ session('student_id') }}">

                        <input type="hidden"
                            name="assessment_id"
                            value="{{ $selectedAssessment->id }}">

                        <input type="hidden" 
                                name="total_marks" 
                                value="{{ $questions->sum('marks') }}">

                        @forelse($questions as $index => $question)

                            <div class="card shadow border-0 mb-3">

                                <div class="card-body">

                                    <h6 class="mb-3">

                                        {{ $index + 1 }}.
                                        {{ $question->question }}

                                    </h6>

                                    <div class="form-check mb-2">

                                        <input class="form-check-input"
                                            type="radio"
                                            name="answers[{{ $question->id }}]"
                                            value="A">

                                        <label class="form-check-label">
                                            {{ $question->option_a }}
                                        </label>

                                    </div>

                                    <div class="form-check mb-2">

                                        <input class="form-check-input"
                                            type="radio"
                                            name="answers[{{ $question->id }}]"
                                            value="B">

                                        <label class="form-check-label">
                                            {{ $question->option_b }}
                                        </label>

                                    </div>

                                    <div class="form-check mb-2">

                                        <input class="form-check-input"
                                            type="radio"
                                            name="answers[{{ $question->id }}]"
                                            value="C">

                                        <label class="form-check-label">
                                            {{ $question->option_c }}
                                        </label>

                                    </div>

                                    <div class="form-check">

                                        <input class="form-check-input"
                                            type="radio"
                                            name="answers[{{ $question->id }}]"
                                            value="D">

                                        <label class="form-check-label">
                                            {{ $question->option_d }}
                                        </label>

                                    </div>

                                </div>

                            </div>

                        @empty

                            <div class="alert alert-warning">

                                No questions added for this assessment yet.

                            </div>

                        @endforelse

                        @if($questions->count() > 0)

                            <button type="submit"
                                    class="btn btn-success w-100">

                                Submit Assessment

                            </button>

                        @endif

                    </form>


                    
                @endif
                
            @endif

        </div>

    </div>

</div>

@if($selectedAssessment && session('active_assessment_session_id'))

<script>
    let minutes = parseInt("{{ $selectedAssessment->duration }}");
    let seconds = 0;
    let submitting = false;

    const timerElement = document.getElementById("timer");
    const assessmentForm = document.getElementById("assessmentForm");

    const violationUrl = "{{ route('assessment.session.violation', session('active_assessment_session_id')) }}";
    const csrfToken = "{{ csrf_token() }}";

    function submitAssessment() {
        if (!submitting) {
            submitting = true;
            assessmentForm.submit();
        }
    }

    const countdown = setInterval(function () {
        if (seconds === 0) {
            if (minutes === 0) {
                clearInterval(countdown);
                alert("Time is up! Your assessment will be submitted now.");
                submitAssessment();
                return;
            }

            minutes--;
            seconds = 59;
        } else {
            seconds--;
        }

        timerElement.innerText =
            minutes + ":" + (seconds < 10 ? "0" + seconds : seconds);

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
                alert(
                    "Assessment automatically submitted due to repeated tab switching."
                );
                submitAssessment();
            }
            else {
                alert(
                    "Warning: Do not switch tabs/windows during the assessment. Violation "
                    + data.violation_count +
                    " of 3."
                );
            }
        });
    }

    document.addEventListener("visibilitychange", function () {
        if (document.hidden && !submitting) {
            recordViolation();
        }
    });

    window.addEventListener("blur", function () {
        if (!submitting) {
            recordViolation();
        }
    });

    assessmentForm.addEventListener("submit", function () {
        submitting = true;
    });
</script>

@endif

@endsection