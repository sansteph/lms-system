@extends('layouts.app')

@section('content')

<style>
    .review-surface {
        background: #f5f7fb;
        min-height: calc(100vh - 80px);
    }

    .review-card {
        border: 0;
        border-radius: 10px;
        box-shadow: 0 10px 28px rgba(15, 23, 42, 0.08);
    }

    .review-meta {
        display: flex;
        flex-wrap: wrap;
        gap: 8px;
    }

    .review-meta span {
        border: 1px solid #dbe3ef;
        border-radius: 999px;
        color: #475569;
        font-size: 13px;
        padding: 6px 10px;
        background: #ffffff;
    }

    .paper-frame {
        width: 100%;
        height: min(70vh, 720px);
        border: 0;
        border-radius: 8px;
        background: #eef2f7;
    }

    .answer-text {
        min-height: 220px;
        max-height: 520px;
        overflow: auto;
        white-space: pre-wrap;
        overflow-wrap: anywhere;
        word-break: break-word;
        line-height: 1.65;
        text-align: left;
        vertical-align: top;
        background: #fcfdff;
        color: #111827;
        font-size: 15px;
        padding: 16px 18px;
        display: block;
    }

    .answer-sheet {
        padding: 22px 24px 24px;
    }

    .answer-empty {
        color: #64748b;
        font-style: italic;
    }

    .evaluation-panel {
        position: sticky;
        top: 16px;
    }

    .status-chip {
        border-radius: 999px;
        padding: 7px 11px;
        background: #fff7ed;
        border: 1px solid #fed7aa;
        color: #9a3412;
        font-size: 13px;
        font-weight: 700;
    }

    @media (max-width: 991.98px) {
        .evaluation-panel {
            position: static;
        }

        .paper-frame {
            height: 520px;
        }
    }
</style>

<div class="review-surface">
    <div class="container-fluid">
        <div class="row">
            @if(session('user_role') == 'Teacher')
                @include('layouts.teacher-sidebar')
            @else
                @include('layouts.sidebar')
            @endif

            <div class="col-md-10 col-lg-10 p-4">
                <div class="page-header mb-4">
                    <h2 class="mb-1">Assessment Evaluation</h2>
                    <p class="text-muted mb-0">Review pending submissions and AI-evaluated assessments, then confirm or adjust final marks.</p>
                </div>

                @if(session('success'))
                    <div class="alert alert-success">{{ session('success') }}</div>
                @endif

                @if(session('error'))
                    <div class="alert alert-danger">{{ session('error') }}</div>
                @endif

                @if($errors->any())
                    <div class="alert alert-danger">Please check the marks, status, and feedback fields.</div>
                @endif

                <div class="card shadow-sm border-0 mb-4">
                    <div class="card-body">
                        <form method="GET" action="{{ route($reviewRouteName) }}" class="row g-3 align-items-end">
                            @if(session('user_role') == 'Admin')
                                <div class="col-md-3">
                                    <label class="form-label">Institute</label>
                                    <select name="institute" class="form-select">
                                        <option value="">All Institutes</option>
                                        @foreach($instituteOptions as $institute)
                                            <option value="{{ $institute }}" @selected(($selectedInstitute ?? '') === $institute)>{{ $institute }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            @endif

                            <div class="col-md-3">
                                <label class="form-label">Class</label>
                                <select name="student_class" class="form-select">
                                    <option value="">All Classes</option>
                                    @foreach($reviewClassOptions as $className)
                                        <option value="{{ $className }}" @selected(($selectedStudentClass ?? '') === $className)>{{ $className }}</option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="col-md-3">
                                <label class="form-label">Section</label>
                                <select name="student_section" class="form-select">
                                    <option value="">All Sections</option>
                                    @foreach($reviewSectionOptions as $sectionName)
                                        <option value="{{ $sectionName }}" @selected(($selectedStudentSection ?? '') === $sectionName)>{{ $sectionName }}</option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="col-md-2">
                                <label class="form-label">Status</label>
                                <select name="status" class="form-select">
                                    <option value="">All Statuses</option>
                                    <option value="Pending Review" @selected(($statusFilter ?? '') === 'Pending Review')>Pending Review</option>
                                    <option value="AI Evaluated" @selected(($statusFilter ?? '') === 'AI Evaluated')>AI Evaluated</option>
                                    <option value="Completed" @selected(($statusFilter ?? '') === 'Completed')>Completed</option>
                                </select>
                            </div>

                            <div class="col-md-2">
                                <label class="form-label">Search</label>
                                <input type="text" name="search" class="form-control" value="{{ $searchFilter ?? '' }}" placeholder="Student / ID">
                            </div>

                            <div class="col-12 d-flex gap-2 flex-wrap">
                                <button type="submit" class="btn btn-primary">Apply Filters</button>
                                <a href="{{ route($reviewRouteName) }}" class="btn btn-outline-secondary">Clear</a>
                            </div>
                        </form>
                    </div>
                </div>

                @if($showFilterPlaceholder ?? false)
                    @include('partials.filter-placeholder')
                @else
                    @forelse($pendingResults->getCollection()->groupBy(fn ($result) => $result->student ? trim(($result->student->class ?? '') . ' ' . ($result->student->section ?? '')) : 'Unassigned Class') as $classLabel => $classResults)
                        <div class="fw-bold text-primary mb-3">{{ $classLabel }}</div>
                        @foreach($classResults as $result)
                            @php
                                $assessment = $result->assessment;
                                $submission = $result->originalSubmission();
                                $isAiEvaluated = $result->status === 'Completed'
                                    && empty($result->evaluated_by)
                                    && $assessment
                                    && in_array($assessment->assessment_category, ['Monthly', 'Annual', 'Component Mastery']);
                                $statusLabel = $isAiEvaluated ? 'AI Evaluated' : ($result->status ?: 'Pending Review');
                                $paperUrl = null;
                                $paperExtension = $assessment ? strtolower(pathinfo($assessment->file_path ?? '', PATHINFO_EXTENSION)) : null;
                                $previewExtensions = ['ppt', 'pptx', 'doc', 'docx'];
                                $paperVariant = $assessment && in_array($paperExtension, $previewExtensions) && $assessment->question_paper_preview_path
                                    ? 'preview'
                                    : 'file';

                                if ($assessment && $assessment->file_path) {
                                    $paperUrl = route('assessment.paper', [$assessment->id, $paperVariant]) . '#toolbar=0&navpanes=0&scrollbar=1';
                                }
                            @endphp

                            <div class="card review-card mb-4">
                                <div class="card-body p-4">
                                    <div class="d-flex justify-content-between align-items-start flex-wrap gap-3 mb-4">
                                        <div>
                                            <div class="d-flex align-items-center flex-wrap gap-2 mb-2">
                                                <h4 class="mb-0">{{ $assessment->assessment_title ?? 'Assessment Deleted' }}</h4>
                                                <span class="status-chip">{{ $statusLabel }}</span>
                                            </div>

                                            <div class="text-muted mb-3">
                                                {{ $result->student->name ?? 'Student Deleted' }}
                                                @if($result->student)
                                                    ({{ $result->student->student_id }})
                                                @endif
                                            </div>

                                            <div class="review-meta">
                                                <span>{{ $assessment->assigned_class ?? 'Class Not Set' }}</span>
                                                <span>{{ $assessment->assessment_category ?? 'Monthly' }}</span>
                                                <span>{{ $result->total_marks }} max marks</span>
                                                <span>{{ $assessment && $assessment->assessment_date ? \Carbon\Carbon::parse($assessment->assessment_date)->format('d M Y') : 'Date Not Set' }}</span>
                                                <span>Submitted {{ $result->created_at ? $result->created_at->format('d M Y, h:i A') : 'N/A' }}</span>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="row g-4 align-items-start">
                                        <div class="col-xl-7">
                                            <div class="row g-4">
                                                <div class="col-12">
                                                    <div class="border rounded bg-white">
                                                        <div class="p-3 border-bottom d-flex justify-content-between align-items-center">
                                                            <div>
                                                                <strong>Question Paper</strong>
                                                                <div class="small text-muted">Approved paper used by the student.</div>
                                                            </div>
                                                            <span class="badge bg-light text-dark border">Reference</span>
                                                        </div>

                                                        <div class="p-3">
                                                            @if($paperUrl && ($paperExtension == 'pdf' || $paperVariant == 'preview'))
                                                                <iframe src="{{ $paperUrl }}" class="paper-frame" title="Question Paper"></iframe>
                                                            @elseif($paperUrl && in_array($paperExtension, ['jpg', 'jpeg', 'png', 'webp']))
                                                                <img src="{{ route('assessment.paper', [$assessment->id, $paperVariant]) }}" alt="Question Paper" class="img-fluid rounded border">
                                                            @else
                                                                <div class="text-muted">Question paper preview is unavailable.</div>
                                                            @endif
                                                        </div>
                                                    </div>
                                                </div>

                                                <div class="col-12">
                                                    <div class="border rounded bg-white answer-sheet">
                                                        <div class="d-flex justify-content-between align-items-center mb-3">
                                                            <div>
                                                                <strong>Student Answers</strong>
                                                            </div>
                                                            <span class="badge bg-light text-dark border">Submission</span>
                                                        </div>

                                                        @if($submission['text'] !== '')
                                                            <div class="answer-text border rounded">{{ $submission['text'] }}</div>
                                                        @endif
                                                        @foreach($submission['answers'] as $answer)
                                                            <div class="mt-3">
                                                                <strong>{{ $answer['question'] ?? ('Question ' . $answer['question_id']) }}</strong>
                                                                @if($answer['maximum_marks'] !== null)
                                                                    <div class="small text-muted">Maximum marks: {{ $answer['maximum_marks'] }}</div>
                                                                @endif
                                                                <div class="answer-text border rounded">{{ $answer['answer'] !== '' ? $answer['answer'] : 'No answer submitted.' }}</div>
                                                                @if($answer['marks_awarded'] !== null)
                                                                    <div class="small text-muted">Recorded marks: {{ $answer['marks_awarded'] }}</div>
                                                                @endif
                                                            </div>
                                                        @endforeach
                                                        @if($result->answer_file_path)
                                                            <a href="{{ route('assessment.answer.file', $result->id) }}" target="_blank" rel="noopener" class="btn btn-outline-primary mt-3">View Student Answer File</a>
                                                        @elseif($submission['text'] === '' && !$submission['answers'])
                                                            <div class="answer-empty">No original answer was stored for this result.</div>
                                                        @endif
                                                    </div>
                                                </div>
                                            </div>
                                        </div>

                                        <div class="col-xl-5">
                                            <div class="evaluation-panel">
                                                <div class="card border-0 shadow-sm">
                                                    <div class="card-body">
                                                        <div class="mb-3">
                                                            <div class="fw-semibold mb-1">Evaluation Status</div>
                                                            <div class="text-muted small">
                                                                @if($isAiEvaluated)
                                                                    AI has assigned a score. Confirm it or adjust the final marks.
                                                                @else
                                                                    Enter marks, feedback, and final decision.
                                                                @endif
                                                            </div>
                                                        </div>

                                                        <form method="POST" action="{{ route(session('user_role') == 'Teacher' ? 'assessment.review.submit' : 'admin.assessment.review.submit', $result->id) }}">
                                                            @csrf

                                                            <div class="mb-3">
                                                                <label class="form-label">Marks Awarded</label>
                                                                <input type="number" name="marks_awarded" class="form-control" min="0" max="{{ $result->total_marks }}" value="{{ old('marks_awarded', $result->score ?? '') }}" required>
                                                            </div>

                                                            <div class="mb-3">
                                                                <label class="form-label">Feedback</label>
                                                                <textarea name="feedback" rows="4" class="form-control">{{ old('feedback', $result->feedback ?? '') }}</textarea>
                                                            </div>

                                                            <div class="mb-3">
                                                                <label class="form-label">Passed</label>
                                                                <select name="passed" class="form-select" required>
                                                                    <option value="1" @selected(old('passed', $result->passed ?? '') == 1)>Pass</option>
                                                                    <option value="0" @selected(old('passed', $result->passed ?? '') == 0)>Fail</option>
                                                                </select>
                                                            </div>

                                                            <button type="submit" class="btn btn-primary w-100">Submit Evaluation</button>
                                                        </form>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    @empty
                        <div class="alert alert-light border text-muted mb-0">No pending evaluations found.</div>
                    @endforelse

                    @if(method_exists($pendingResults, 'links'))
                        <div class="mt-3">
                            {{ $pendingResults->links('pagination::bootstrap-5') }}
                        </div>
                    @endif
                @endif
            </div>
        </div>
    </div>
</div>

@endsection
