@extends('layouts.app')

@section('content')

<div class="container-fluid">
    <div class="row">
        @include('layouts.teacher-sidebar')

        <div class="col-md-10 col-lg-10 p-4">
            <div class="page-header mb-4">
                    <h2 class="mb-1">My Classes</h2>
                    <p class="text-muted mb-0">
                    Start sessions from released institute Teaching Plan content.
                    </p>
            </div>

            @if(session('success'))
                <div class="alert alert-success">{{ session('success') }}</div>
            @endif

            @if(session('error'))
                <div class="alert alert-danger">{{ session('error') }}</div>
            @endif

            <div class="card shadow border-0 mb-4">
                <div class="card-body">
                    <form method="GET" action="{{ route('teacher.classes') }}" class="row g-3 align-items-end">
                        <div class="col-md-4">
                            <label class="form-label">Class</label>
                            <select name="class" class="form-control">
                                <option value="">All Classes</option>
                                @foreach($classOptions as $classOption)
                                    <option value="{{ $classOption }}" {{ $selectedClass == $classOption ? 'selected' : '' }}>
                                        {{ $classOption }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-2">
                            <button type="submit" class="btn btn-primary w-100">Apply</button>
                        </div>
                        <div class="col-md-2">
                            <a href="{{ route('teacher.classes') }}" class="btn btn-outline-secondary w-100">Clear</a>
                        </div>
                    </form>
                </div>
            </div>

            @if($showFilterPlaceholder)
                @include('partials.filter-placeholder')
            @else
            <div class="row g-4 mb-4">
                <div class="col-md-4">
                    <div class="dashboard-card">
                        <h6>Approved Plans</h6>
                        <h2>{{ $releasedItems->pluck('teaching_plan_id')->unique()->count() }}</h2>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="dashboard-card">
                        <h6>Today's Sessions</h6>
                        <h2>{{ $sessions->count() }}</h2>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="dashboard-card">
                        <h6>Active Sessions</h6>
                        <h2>{{ $activeSessions->count() }}</h2>
                    </div>
                </div>
            </div>

            <div class="card shadow border-0 mb-4">
                <div class="card-body">
                    <form method="GET" action="{{ route('teacher.classes') }}" class="row g-3 align-items-end">
                        <div class="col-md-4">
                            <label class="form-label">Class</label>
                            <select name="class" class="form-control">
                                <option value="">All Classes</option>
                                @foreach($classOptions as $classOption)
                                    <option value="{{ $classOption }}" {{ $selectedClass == $classOption ? 'selected' : '' }}>
                                        {{ $classOption }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-2">
                            <button type="submit" class="btn btn-primary w-100">Apply</button>
                        </div>
                        <div class="col-md-2">
                            <a href="{{ route('teacher.classes') }}" class="btn btn-outline-secondary w-100">Clear</a>
                        </div>
                    </form>
                </div>
            </div>

            <div class="card shadow border-0 mb-4">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0">Start Session</h5>
                </div>
                <div class="card-body">
                    <div class="alert alert-info py-2 mb-3">
                        Session timing rule: minimum 30 minutes and maximum 50 minutes for completed or partially completed sessions.
                    </div>
                    <form method="POST"
                          action="{{ route('teacher.class-session.start') }}"
                          class="row g-3 align-items-end">
                        @csrf
                        <div class="col-lg-5">
                            <label class="form-label">Released Teaching Plan Content</label>
                            <select name="teaching_plan_item_id" class="form-control" required>
                                <option value="">Select released class, course, and content</option>
                                @foreach($releasedItems as $item)
                                    @php
                                        $effectiveAiSummary = $item->content?->effective_ai_summary;
                                        $gradeLevel = preg_replace('/\s+/', ' ', trim($item->plan?->class ?? ''));
                                        $contentPassKey = $item->content ? $item->content->id . '|' . ($gradeLevel ?: 'all') : null;
                                        $sourcePassKey = $item->content?->ai_quiz_content_id ? $item->content->ai_quiz_content_id . '|' . ($gradeLevel ?: 'all') : null;
                                        $contentAnyGradePassKey = $item->content ? $item->content->id . '|all' : null;
                                        $sourceAnyGradePassKey = $item->content?->ai_quiz_content_id ? $item->content->ai_quiz_content_id . '|all' : null;
                                        $aiTrainingApplies = $aiTrainingRequiredItemIds->contains($item->id);
                                        $prepCleared = $item->content
                                            && (
                                                $teacherPassedPrepKeys->contains($contentPassKey)
                                                || $teacherPassedPrepKeys->contains($sourcePassKey)
                                                || $teacherPassedPrepKeys->contains($contentAnyGradePassKey)
                                                || $teacherPassedPrepKeys->contains($sourceAnyGradePassKey)
                                            );
                                        $prepBlocksSession = $aiTrainingApplies
                                            && (
                                                !$item->content
                                                || !$effectiveAiSummary
                                                || $effectiveAiSummary->status != 'generated'
                                                || !$prepCleared
                                            );
                                    @endphp
                                    @continue($prepBlocksSession)
                                    <option value="{{ $item->id }}">
                                        {{ $item->plan->class }} {{ $item->plan->section }}
                                        - {{ $item->course->course_title ?? 'Course' }}
                                        - Week {{ $item->week->week_number ?? '-' }}
                                        - {{ $item->content->content_title ?? 'Content' }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-lg-2 col-md-4">
                            <label class="form-label">Day</label>
                            <select name="session_day" class="form-control" required>
                                @foreach(['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'] as $day)
                                    <option value="{{ $day }}" {{ now()->format('l') == $day ? 'selected' : '' }}>{{ $day }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-lg-2 col-md-4">
                            <label class="form-label">Date</label>
                            <input type="date"
                                   name="session_date"
                                   class="form-control"
                                   value="{{ now()->format('Y-m-d') }}"
                                   required>
                        </div>
                        <div class="col-lg-2 col-md-4">
                            <label class="form-label">Time</label>
                            <input type="time"
                                   name="start_time"
                                   class="form-control"
                                   value="{{ now()->format('H:i') }}"
                                   required>
                        </div>
                        <div class="col-lg-1">
                            <button type="submit" class="btn btn-primary w-100">
                                Start
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <div class="card shadow border-0 mb-4">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0">Released Teaching Plan Content</h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive lms-table-shell">
                        <table class="table table-bordered table-hover align-middle lms-table-fit">
                            <thead class="table-light">
                                <tr>
                                    <th>Class</th>
                                    <th>Course</th>
                                    <th>Released Content</th>
                                    <th>Week</th>
                                    <th>Training Assessment</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($releasedItems->groupBy(fn ($item) => trim(($item->plan->class ?? '') . ' ' . ($item->plan->section ?? '')) ?: 'Unassigned Class') as $classLabel => $items)
                                    <tr class="table-primary">
                                        <td colspan="5" class="fw-semibold">{{ $classLabel }}</td>
                                    </tr>
                                    @foreach($items as $item)
                                        <tr>
                                            <td>{{ $item->plan->class }} {{ $item->plan->section }}</td>
                                            <td>{{ $item->course->course_title ?? 'N/A' }}</td>
                                            <td>{{ $item->content->content_title ?? 'N/A' }}</td>
                                            <td>
                                                Week {{ $item->week->week_number ?? '-' }}
                                                <br>
                                                <small class="text-muted">
                                                    Released {{ $item->released_at ? \Carbon\Carbon::parse($item->released_at)->format('d M Y') : '-' }}
                                                </small>
                                            </td>
                                            <td>
                                                @php
                                                    $effectiveAiSummary = $item->content?->effective_ai_summary;
                                                    $gradeLevel = preg_replace('/\s+/', ' ', trim($item->plan?->class ?? ''));
                                                    $contentPassKey = $item->content ? $item->content->id . '|' . ($gradeLevel ?: 'all') : null;
                                                    $sourcePassKey = $item->content?->ai_quiz_content_id ? $item->content->ai_quiz_content_id . '|' . ($gradeLevel ?: 'all') : null;
                                                    $contentAnyGradePassKey = $item->content ? $item->content->id . '|all' : null;
                                                    $sourceAnyGradePassKey = $item->content?->ai_quiz_content_id ? $item->content->ai_quiz_content_id . '|all' : null;
                                                    $prepCleared = $item->content
                                                        && (
                                                            $teacherPassedPrepKeys->contains($contentPassKey)
                                                            || $teacherPassedPrepKeys->contains($sourcePassKey)
                                                            || $teacherPassedPrepKeys->contains($contentAnyGradePassKey)
                                                            || $teacherPassedPrepKeys->contains($sourceAnyGradePassKey)
                                                        );
                                                @endphp
                                                @if(!$aiTrainingRequiredItemIds->contains($item->id))
                                                    <span class="badge bg-light text-dark border">Not Required</span>
                                                @elseif($item->content && $effectiveAiSummary && $effectiveAiSummary->status == 'generated')
                                                    @if($prepCleared)
                                                        <span class="badge bg-success">Prep Cleared</span>
                                                    @else
                                                        <span class="badge bg-warning text-dark">Clear from Learning Content</span>
                                                    @endif
                                                @else
                                                    <span class="badge bg-secondary">Not Generated</span>
                                                @endif
                                            </td>
                                        </tr>
                                    @endforeach
                                @empty
                                    <tr>
                                        <td colspan="5" class="text-center text-muted">
                                            No released Teaching Plan content is available for your institute.
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <div class="card shadow border-0">
                <div class="card-header bg-light">
                    <h5 class="mb-0">Today's Session Execution</h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive lms-table-shell">
                        <table class="table table-bordered table-hover align-middle lms-table-fit">
                            <thead class="table-light">
                                <tr>
                                    <th>Class</th>
                                    <th>Course</th>
                                    <th>Planned Topic</th>
                                    <th>Date / Time</th>
                                    <th>Status</th>
                                    <th width="300">Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($sessions->groupBy(fn ($session) => trim(($session->class ?? '') . ' ' . ($session->section ?? '')) ?: 'Unassigned Class') as $classLabel => $classSessions)
                                    <tr class="table-primary">
                                        <td colspan="6" class="fw-semibold">{{ $classLabel }}</td>
                                    </tr>
                                    @foreach($classSessions as $session)
                                        <tr>
                                            <td>{{ $session->class }} {{ $session->section }}</td>
                                            <td>{{ $session->course->course_title ?? 'N/A' }}</td>
                                            <td>{{ $session->planned_topic ?? $session->content->content_title ?? 'N/A' }}</td>
                                            <td>
                                                {{ $session->session_date ? \Carbon\Carbon::parse($session->session_date)->format('d M Y') : '-' }}
                                                <br>
                                                <small class="text-muted">
                                                    {{ $session->start_time ? \Carbon\Carbon::parse($session->start_time)->format('h:i A') : '-' }}
                                                    @if($session->end_time)
                                                        - {{ \Carbon\Carbon::parse($session->end_time)->format('h:i A') }}
                                                    @endif
                                                </small>
                                            </td>
                                            <td>{{ str_replace('_', ' ', ucfirst($session->status)) }}</td>
                                            <td>
                                            @if($session->status == 'in_progress')
                                                @if($session->content_id)
                                                    <a href="{{ route('teacher.session.content', $session->content_id) }}"
                                                       class="btn btn-sm btn-outline-primary w-100 mb-2"
                                                       target="_blank"
                                                       rel="noopener">
                                                        Open Content
                                                    </a>
                                                @endif

                                                <form method="POST"
                                                      action="{{ route('teacher.class-session.end', $session->id) }}"
                                                      class="session-end-form"
                                                      data-auto-end-at="{{ $session->started_at ? \Carbon\Carbon::parse($session->started_at)->addMinutes(50)->timestamp : '' }}">
                                                    @csrf
                                                    <input type="hidden" name="auto_ended" value="0" class="auto-ended-input">
                                                    <div class="row g-2">
                                                        @if($session->started_at)
                                                            <div class="col-12">
                                                                <div class="small text-muted">
                                                                    Auto ends in <span class="session-auto-timer fw-semibold">calculating...</span>
                                                                </div>
                                                            </div>
                                                        @endif
                                                        <div class="col-md-6">
                                                            <select name="status" class="form-control form-control-sm" required>
                                                                <option value="completed">Completed</option>
                                                                <option value="partially_completed">Partially Completed</option>
                                                                <option value="cancelled">Cancelled</option>
                                                            </select>
                                                        </div>
                                                        <div class="col-md-6">
                                                            <input type="text"
                                                                   name="delivered_topic"
                                                                   class="form-control form-control-sm"
                                                                   placeholder="Delivered topic"
                                                                   value="{{ $session->planned_topic }}">
                                                        </div>
                                                        <div class="col-12">
                                                            <textarea name="remarks"
                                                                      class="form-control form-control-sm"
                                                                      rows="2"
                                                                      placeholder="Remarks"></textarea>
                                                        </div>
                                                        <div class="col-12">
                                                            <button type="submit" class="btn btn-sm btn-outline-danger w-100">
                                                                End Session
                                                            </button>
                                                        </div>
                                                    </div>
                                                </form>
                                            @elseif($session->status == 'completed' && $session->content && !$session->content->is_released)
                                                <form method="POST" action="{{ route('teacher.complete-topic', $session->content_id) }}">
                                                    @csrf
                                                    <button type="submit" class="btn btn-sm btn-success">
                                                        Mark Topic Complete
                                                    </button>
                                                </form>
                                            @elseif($session->content && $session->content->is_released)
                                                <span class="badge bg-success">Topic Released</span>
                                            @else
                                                <span class="text-muted small">No action available</span>
                                            @endif
                                            </td>
                                        </tr>
                                    @endforeach
                                @empty
                                    <tr>
                                        <td colspan="6" class="text-center text-muted">
                                            No sessions started today.
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

@if(session('sessionCompletionCelebration'))
    <div class="modal fade" id="sessionCompletionModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content border-0 shadow overflow-hidden">
                @if($sessionCompletionVideoUrl)
                    <div class="ratio ratio-16x9 bg-dark">
                        <video id="sessionCompletionVideo"
                               src="{{ $sessionCompletionVideoUrl }}"
                               autoplay
                               muted
                               playsinline
                               preload="auto">
                            Your browser does not support video playback.
                        </video>
                    </div>
                    <div class="modal-body text-center py-3">
                        <h5 class="fw-bold mb-1">Session Updated</h5>
                        <p class="text-muted mb-0">
                            The class session has been recorded successfully.
                        </p>
                    </div>
                @else
                    <div class="modal-body text-center p-5">
                        <div class="display-4 text-success mb-3">
                            <i class="fa fa-check-circle"></i>
                        </div>
                        <h4 class="fw-bold mb-2">Session Updated</h4>
                        <p class="text-muted mb-0">
                            The class session has been recorded successfully.
                        </p>
                        <p class="text-muted small mt-3 mb-0">
                            Add celebration videos in storage to play one here automatically.
                        </p>
                    </div>
                @endif
            </div>
        </div>
    </div>

            @endif

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const modalElement = document.getElementById('sessionCompletionModal');
            if (modalElement && window.bootstrap) {
                const modal = new bootstrap.Modal(modalElement);
                modal.show();

                modalElement.addEventListener('shown.bs.modal', function () {
                    const video = document.getElementById('sessionCompletionVideo');
                    if (video) {
                        video.currentTime = 0;
                        video.play().catch(function () {
                            video.muted = true;
                            video.play().catch(function () {});
                        });

                        video.addEventListener('ended', function () {
                            modal.hide();
                        }, { once: true });

                        video.addEventListener('error', function () {
                            setTimeout(function () {
                                modal.hide();
                            }, 1800);
                        }, { once: true });
                    }
                }, { once: true });
            }
        });
    </script>
@endif

@if(session('previewContentUrl'))
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            window.open(@json(session('previewContentUrl')), '_blank', 'noopener');
        });
    </script>
@endif

<script>
    document.addEventListener('DOMContentLoaded', function () {
        const forms = document.querySelectorAll('.session-end-form[data-auto-end-at]');

        forms.forEach(function (form) {
            const autoEndAt = parseInt(form.dataset.autoEndAt || '0', 10) * 1000;
            const timer = form.querySelector('.session-auto-timer');
            const autoEndedInput = form.querySelector('.auto-ended-input');
            const statusSelect = form.querySelector('select[name="status"]');

            if (!autoEndAt) {
                return;
            }

            const updateTimer = function () {
                const remainingSeconds = Math.ceil((autoEndAt - Date.now()) / 1000);

                if (remainingSeconds <= 0) {
                    if (form.dataset.autoSubmitted === '1') {
                        return;
                    }

                    form.dataset.autoSubmitted = '1';

                    if (timer) {
                        timer.textContent = 'ending now';
                    }

                    if (autoEndedInput) {
                        autoEndedInput.value = '1';
                    }

                    if (statusSelect) {
                        statusSelect.value = 'partially_completed';
                    }

                    form.submit();
                    return;
                }

                if (timer) {
                    const minutes = Math.floor(remainingSeconds / 60);
                    const seconds = remainingSeconds % 60;
                    timer.textContent = minutes + ':' + String(seconds).padStart(2, '0');
                }
            };

            updateTimer();
            setInterval(updateTimer, 1000);
        });
    });
</script>

@endsection
