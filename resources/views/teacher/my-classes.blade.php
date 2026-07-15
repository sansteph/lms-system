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
                                        $prepRequired = $item->content
                                            && $effectiveAiSummary
                                            && $effectiveAiSummary->status == 'generated'
                                            && !$teacherPassedPrepContentIds->contains($item->content->id);
                                    @endphp
                                    <option value="{{ $item->id }}">
                                        {{ $item->plan->class }} {{ $item->plan->section }}
                                        - {{ $item->course->course_title ?? 'Course' }}
                                        - Week {{ $item->week->week_number ?? '-' }}
                                        - {{ $item->content->content_title ?? 'Content' }}
                                        {{ $prepRequired ? '- Prep Required' : '' }}
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
                    <div class="table-responsive">
                        <table class="table table-bordered table-hover align-middle">
                            <thead class="table-light">
                                <tr>
                                    <th>Class</th>
                                    <th>Course</th>
                                    <th>Released Content</th>
                                    <th>Week</th>
                                    <th>AI Prep</th>
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
                                                @php $effectiveAiSummary = $item->content?->effective_ai_summary; @endphp
                                                @if($item->content && $effectiveAiSummary && $effectiveAiSummary->status == 'generated')
                                                    <a href="{{ route('teacher.ai-prep', $item->content->id) }}"
                                                       class="btn btn-sm btn-outline-success">
                                                        Prep Quiz
                                                    </a>
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

            <div class="card shadow border-0 mb-4">
                <div class="card-header bg-warning text-dark">
                    <h5 class="mb-0">Unfinished Sessions</h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-bordered table-hover align-middle">
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
                                @forelse($unfinishedSessions->groupBy(fn ($session) => trim(($session->class ?? '') . ' ' . ($session->section ?? '')) ?: 'Unassigned Class') as $classLabel => $classSessions)
                                    <tr class="table-warning">
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
                                            <td>
                                                <span class="badge {{ $session->status == 'in_progress' ? 'bg-danger' : 'bg-warning text-dark' }}">
                                                    {{ str_replace('_', ' ', ucfirst($session->status)) }}
                                                </span>
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
                                                          action="{{ route('teacher.class-session.end', $session->id) }}">
                                                        @csrf
                                                        <div class="row g-2">
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
                                                @elseif($session->status == 'partially_completed')
                                                    @if($session->content_id)
                                                        <a href="{{ route('teacher.session.content', $session->content_id) }}"
                                                           class="btn btn-sm btn-outline-primary w-100 mb-2"
                                                           target="_blank"
                                                           rel="noopener">
                                                            Open Content
                                                        </a>
                                                    @endif
                                                    <span class="text-muted small">
                                                        Session ended as partially completed.
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
                    <h5 class="mb-0">Today's Session Execution</h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-bordered table-hover align-middle">
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
                                                      action="{{ route('teacher.class-session.end', $session->id) }}">
                                                    @csrf
                                                    <div class="row g-2">
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

@endsection
