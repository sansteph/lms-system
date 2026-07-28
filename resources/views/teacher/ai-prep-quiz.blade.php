@extends('layouts.app')

@section('content')

<div class="container-fluid">
    <div class="row">
        <div class="col-12 p-4 p-lg-5">
            <div class="d-flex justify-content-between align-items-center flex-wrap gap-3 mb-4">
                <div>
                    <h2 class="fw-bold mb-1">Prep Quiz</h2>
                    <p class="text-muted mb-0">
                        Answer from your preparation. Navigation, refresh, tab switching, copy/paste, and right-click are restricted.
                    </p>
                </div>
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
                <div class="card-header bg-white border-0 px-4 pt-4">
                    <div class="text-muted small fw-semibold text-uppercase mb-2">
                        {{ $content->content_title }}
                    </div>
                    <h5 class="fw-bold mb-1">Prep Questions</h5>
                    <p class="text-muted mb-0">
                        Passing score is {{ rtrim(rtrim(number_format(config('ai.content.teacher_passing_percentage', 50), 2), '0'), '.') }}%. This score is required before starting an AI-enabled session.
                    </p>
                </div>

                <div class="card-body p-4">
                    @if($latestAttempt && $latestAttempt->status == 'passed')
                        <div class="alert alert-success mb-0">
                            Prep quiz already cleared. No further attempts are needed.
                        </div>
                    @else
                    <form id="lockedPrepQuizForm" action="{{ route('teacher.ai-prep.submit', $content->id) }}" method="POST">
                        @csrf
                        <input type="hidden" name="auto_submitted" id="autoSubmitted" value="0">

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
                            <button type="submit" class="btn btn-success">
                                <i class="fa fa-paper-plane me-2"></i>
                                Submit Prep Quiz
                            </button>
                        </div>
                    </form>
                    @endif
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

    .locked-prep-warning {
        position: fixed;
        inset: 0;
        z-index: 9999;
        display: none;
        align-items: center;
        justify-content: center;
        padding: 24px;
        background: rgba(15, 23, 42, 0.78);
        backdrop-filter: blur(8px);
    }

    .locked-prep-warning.active {
        display: flex;
    }

    .locked-prep-warning-card {
        width: min(460px, 100%);
        border-radius: 16px;
        background: #ffffff;
        border: 1px solid #e2e8f0;
        box-shadow: 0 24px 60px rgba(15, 23, 42, 0.22);
        padding: 28px;
        text-align: center;
    }

    .locked-prep-warning-card i {
        color: #dc2626;
        font-size: 2rem;
        margin-bottom: 14px;
    }
</style>

@unless($latestAttempt && $latestAttempt->status == 'passed')
    <div class="locked-prep-warning" id="lockedPrepWarning" role="alert" aria-live="assertive">
        <div class="locked-prep-warning-card">
            <i class="fa fa-triangle-exclamation"></i>
            <h4 class="fw-bold mb-2" id="lockedPrepWarningTitle">Restricted Action</h4>
            <p class="text-muted mb-0" id="lockedPrepWarningText">
                Prep quiz is in progress. Do not switch tabs, refresh, go back, copy/paste, right-click, or use restricted shortcuts.
            </p>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const warning = document.getElementById('lockedPrepWarning');
            const warningTitle = document.getElementById('lockedPrepWarningTitle');
            const warningText = document.getElementById('lockedPrepWarningText');
            const form = document.getElementById('lockedPrepQuizForm');
            const autoSubmitted = document.getElementById('autoSubmitted');
            let submitting = false;
            let warningTimer = null;
            let tabSwitchAttempted = false;
            let violationCount = 0;

            if (form) {
                form.addEventListener('submit', function () {
                    submitting = true;
                });
            }

            history.pushState(null, '', window.location.href);

            const showWarning = function (message) {
                if (!warning) {
                    return;
                }

                if (warningTitle) {
                    warningTitle.textContent = violationCount >= 3
                        ? 'Assessment Auto-Submitting'
                        : 'Restricted Action';
                }

                if (warningText) {
                    warningText.textContent = message || 'Prep quiz is in progress. Do not switch tabs, refresh, go back, copy/paste, right-click, or use restricted shortcuts.';
                }

                warning.classList.add('active');
                clearTimeout(warningTimer);
                warningTimer = setTimeout(function () {
                    warning.classList.remove('active');
                }, 2200);
            };

            const submitForViolation = function () {
                if (!form || submitting) {
                    return;
                }

                submitting = true;

                if (autoSubmitted) {
                    autoSubmitted.value = '1';
                }

                showWarning('Third restricted action detected. Your prep assessment is being submitted automatically.');

                setTimeout(function () {
                    form.submit();
                }, 450);
            };

            const recordViolation = function () {
                if (submitting) {
                    return;
                }

                violationCount++;

                if (violationCount >= 3) {
                    submitForViolation();
                    return;
                }

                showWarning('Warning ' + violationCount + ' of 2. On the third restricted action, the prep assessment will be submitted automatically.');
            };

            const blockEvent = function (event) {
                if (event) {
                    event.preventDefault();
                    event.stopPropagation();
                }

                recordViolation();
                return false;
            };

            window.addEventListener('popstate', function () {
                history.pushState(null, '', window.location.href);
                recordViolation();
            });

            window.addEventListener('beforeunload', function (event) {
                if (submitting) {
                    return;
                }

                event.preventDefault();
                event.returnValue = '';
            });

            document.addEventListener('visibilitychange', function () {
                if (document.hidden && !submitting) {
                    tabSwitchAttempted = true;
                    return;
                }

                if (tabSwitchAttempted && !submitting) {
                    tabSwitchAttempted = false;
                    recordViolation();
                }
            });

            ['contextmenu', 'copy', 'cut', 'paste', 'dragstart', 'selectstart'].forEach(function (eventName) {
                document.addEventListener(eventName, blockEvent);
            });

            document.addEventListener('keydown', function (event) {
                const key = String(event.key || '').toLowerCase();
                const blocked =
                    key === 'f5' ||
                    key === 'printscreen' ||
                    (event.ctrlKey && ['r', 's', 'p', 'u', 'c', 'v', 'x', 'a'].includes(key)) ||
                    (event.metaKey && ['r', 's', 'p', 'u', 'c', 'v', 'x', 'a'].includes(key)) ||
                    (event.altKey && ['arrowleft', 'arrowright'].includes(key)) ||
                    (event.ctrlKey && event.shiftKey && ['i', 'j', 'c'].includes(key));

                if (blocked) {
                    blockEvent(event);
                }
            }, true);
        });
    </script>
@endunless

@endsection
