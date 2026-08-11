@extends('layouts.app')

@section('content')

<div class="container-fluid">
    <div class="row">
        @include('layouts.teacher-sidebar')

        <div class="col-md-10 col-lg-10 p-4">
            <div class="page-header mb-4">
                <h2 class="mb-1">Pending Sessions</h2>
                <p class="text-muted mb-0">
                    Continue unfinished class sessions and catch up on admin-assigned lagged content.
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
                    <form method="GET" action="{{ route('teacher.pending-sessions') }}" class="row g-3 align-items-end" target="_self">
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
                            <a href="{{ route('teacher.pending-sessions') }}" class="btn btn-outline-secondary w-100">Clear</a>
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
                        <h6>Pending Attempts</h6>
                        <h2>{{ $pendingSessions->count() }}</h2>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="dashboard-card">
                        <h6>Lagged Content</h6>
                        <h2>{{ $laggedItems->count() }}</h2>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="dashboard-card">
                        <h6>Active Sessions</h6>
                        <h2>{{ $pendingSessions->where('status', 'in_progress')->count() }}</h2>
                    </div>
                </div>
            </div>

            <div class="card shadow border-0 mb-4">
                <div class="card-header bg-warning text-dark">
                    <h5 class="mb-0">Unfinished Sessions</h5>
                </div>
                <div class="card-body">
                    <div class="alert alert-info py-2 mb-3">
                        Partially completed and cancelled attempts stay here until the related Teaching Plan content is completed in a new session.
                    </div>
                    <div class="table-responsive lms-table-shell">
                        <table class="table table-bordered table-hover align-middle lms-table-tight">
                            <thead class="table-light">
                                <tr>
                                    <th>Class</th>
                                    <th>Course</th>
                                    <th>Planned Topic</th>
                                    <th>Date / Time</th>
                                    <th>Status</th>
                                    <th width="320">Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($pendingSessions->groupBy(fn ($session) => trim(($session->class ?? '') . ' ' . ($session->section ?? '')) ?: 'Unassigned Class') as $classLabel => $classSessions)
                                    <tr class="table-warning">
                                        <td colspan="6" class="fw-semibold">{{ $classLabel }}</td>
                                    </tr>
                                    @foreach($classSessions as $session)
                                        @php
                                            $statusClass = match ($session->status) {
                                                'in_progress' => 'bg-danger',
                                                'cancelled' => 'bg-secondary',
                                                default => 'bg-warning text-dark',
                                            };
                                            $canStartFollowUp = in_array($session->status, ['partially_completed', 'cancelled'])
                                                && $session->teachingPlanItem
                                                && $session->teachingPlanItem->status == 'released'
                                                && $session->content
                                                && $session->content->status == 1;
                                        @endphp
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
                                            <td>
                                                <span class="badge {{ $statusClass }}">
                                                    {{ str_replace('_', ' ', ucfirst($session->status)) }}
                                                </span>
                                                @if($session->status == 'in_progress' && $session->started_at)
                                                    <div class="small text-muted mt-1">
                                                        Started {{ \Carbon\Carbon::parse($session->started_at)->diffForHumans() }}
                                                    </div>
                                                @endif
                                            </td>
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
                                                        <div class="session-action-stack">
                                                            @if($session->started_at)
                                                                <div>
                                                                    <div class="small text-muted">
                                                                        Auto ends in <span class="session-auto-timer fw-semibold">calculating...</span>
                                                                    </div>
                                                                </div>
                                                            @endif
                                                            <div>
                                                                <select name="status" class="form-control form-control-sm" required>
                                                                    <option value="completed">Completed</option>
                                                                    <option value="partially_completed">Partially Completed</option>
                                                                    <option value="cancelled">Cancelled</option>
                                                                </select>
                                                            </div>
                                                            <div>
                                                                <input type="text"
                                                                       name="delivered_topic"
                                                                       class="form-control form-control-sm"
                                                                       placeholder="Delivered topic"
                                                                       value="{{ $session->planned_topic }}">
                                                            </div>
                                                            <div>
                                                                <textarea name="remarks"
                                                                          class="form-control form-control-sm"
                                                                          rows="2"
                                                                          placeholder="Remarks"></textarea>
                                                            </div>
                                                            <div>
                                                                <button type="submit" class="btn btn-sm btn-outline-danger w-100">
                                                                    End Session
                                                                </button>
                                                            </div>
                                                        </div>
                                                    </form>
                                                @elseif($canStartFollowUp)
                                                    <form method="POST" action="{{ route('teacher.class-session.start') }}">
                                                        @csrf
                                                        <input type="hidden" name="teaching_plan_item_id" value="{{ $session->teaching_plan_item_id }}">
                                                        <input type="hidden" name="session_day" value="{{ now()->format('l') }}">
                                                        <input type="hidden" name="session_date" value="{{ now()->format('Y-m-d') }}">
                                                        <input type="hidden" name="start_time" value="{{ now()->format('H:i') }}">
                                                        <button type="submit" class="btn btn-sm btn-primary w-100 mb-2">
                                                            Start Follow-up Session
                                                        </button>
                                                    </form>
                                                    @if($session->content_id)
                                                        <a href="{{ route('teacher.session.content', $session->content_id) }}"
                                                           class="btn btn-sm btn-outline-primary w-100"
                                                           target="_blank"
                                                           rel="noopener">
                                                            Open Previous Content
                                                        </a>
                                                    @endif
                                                @else
                                                    <span class="text-muted small">
                                                        This item is no longer available for follow-up.
                                                    </span>
                                                @endif
                                            </td>
                                        </tr>
                                    @endforeach
                                @empty
                                    <tr>
                                        <td colspan="6" class="text-center text-muted">
                                            No unfinished sessions.
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
                    <h5 class="mb-0">Lagged Content</h5>
                </div>
                <div class="card-body">
                    <div class="alert alert-warning py-2 mb-3">
                        These catch-up or repeat contents were added by Admin for your institute.
                    </div>
                    <div class="table-responsive lms-table-shell">
                        <table class="table table-bordered table-hover align-middle lms-table-fit">
                            <thead class="table-light">
                                <tr>
                                    <th>Class</th>
                                    <th>Course</th>
                                    <th>Content</th>
                                    <th>Week / Release</th>
                                    <th>Training Assessment</th>
                                    <th width="220">Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($laggedItems->groupBy(fn ($item) => trim(($item->plan->class ?? '') . ' ' . ($item->plan->section ?? '')) ?: 'Unassigned Class') as $classLabel => $items)
                                    <tr class="table-primary">
                                        <td colspan="6" class="fw-semibold">{{ $classLabel }}</td>
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
                                                    Released {{ $item->released_at ? \Carbon\Carbon::parse($item->released_at)->format('d M Y') : ($item->week?->release_date ? \Carbon\Carbon::parse($item->week->release_date)->format('d M Y') : '-') }}
                                                </small>
                                            </td>
                                            <td>
                                                <span class="badge bg-light text-dark border">Not Required</span>
                                            </td>
                                            <td>
                                                <form method="POST" action="{{ route('teacher.class-session.start') }}">
                                                    @csrf
                                                    <input type="hidden" name="teaching_plan_item_id" value="{{ $item->id }}">
                                                    <input type="hidden" name="session_day" value="{{ now()->format('l') }}">
                                                    <input type="hidden" name="session_date" value="{{ now()->format('Y-m-d') }}">
                                                    <input type="hidden" name="start_time" value="{{ now()->format('H:i') }}">
                                                    <button type="submit" class="btn btn-sm btn-primary w-100">
                                                        Start Session
                                                    </button>
                                                </form>
                                            </td>
                                        </tr>
                                    @endforeach
                                @empty
                                    <tr>
                                        <td colspan="6" class="text-center text-muted">
                                            No lagged content.
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
