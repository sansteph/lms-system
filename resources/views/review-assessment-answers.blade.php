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
                    <p class="text-muted mb-0">Review the approved question paper and student submission together, then enter final marks manually.</p>
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

                @if(session('user_role') == 'Admin')
                    @include('partials.section-navigator', [
                        'sectionPager' => $sectionPager ?? null,
                        'sectionDescription' => 'Pending assessment evaluations are shown one institute at a time to keep review pages fast.',
                    ])
                @endif

                @include('partials.section-navigator', [
                    'sectionPager' => $classSectionPager ?? null,
                    'sectionDescription' => 'Browse pending evaluations one class at a time.',
                ])

                @include('partials.section-navigator', [
                    'sectionPager' => $studentSectionPager ?? null,
                    'sectionDescription' => 'Showing pending evaluations for this section only.',
                ])

                @if(!empty($selectedStudentClass))
                    <div class="alert alert-light border d-flex justify-content-between align-items-center flex-wrap gap-2">
                        <div>
                            <span class="fw-semibold">Current evaluation scope:</span>
                            Class {{ $selectedStudentClass }}
                            @if(!empty($selectedStudentSection))
                                &middot; Section {{ $selectedStudentSection }}
                            @endif
                        </div>

                        @if(!empty($currentInstitute))
                            <span class="text-muted small">{{ $currentInstitute }}</span>
                        @endif
                    </div>
                @endif

                @forelse($pendingResults->getCollection()->groupBy(fn ($result) => $result->student ? trim($result->student->class . ' ' . $result->student->section) : 'Unassigned Class') as $classLabel => $classResults)
                    <div class="fw-bold text-primary mb-3">{{ $classLabel }}</div>
                    @foreach($classResults as $result)
                    @php
                        $assessment = $result->assessment;
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
                                        <span class="status-chip">Pending Review</span>
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
                                                        <iframe src="{{ $paperUrl }}"
                                                                class="paper-frame"
                                                                title="Question Paper">
                                                        </iframe>
                                                    @elseif($paperUrl && in_array($paperExtension, ['jpg', 'jpeg', 'png', 'webp']))
                                                        <img src="{{ route('assessment.paper', [$assessment->id, $paperVariant]) }}"
                                                             class="img-fluid rounded border"
                                                             alt="Question Paper">
                                                    @else
                                                        <div class="alert alert-warning mb-0">Question paper preview is not available.</div>
                                                    @endif
                                                </div>
                                            </div>
                                        </div>

                                        <div class="col-12">
                                            <div class="border rounded bg-white">
                                                <div class="p-3 border-bottom">
                                                    <strong>Student Submission</strong>
                                                    <div class="small text-muted">Typed answer submitted by the student.</div>
                                                </div>

                                                <div class="answer-sheet">
                                                    <div class="fw-semibold mb-2">Typed Answer</div>
                                                    <div class="border rounded answer-text">@if(trim((string) $result->answer_text) !== ''){{ $result->answer_text }}@else<span class="answer-empty">No typed answer submitted.</span>@endif</div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div class="col-xl-5">
                                    <div class="card border-0 bg-white evaluation-panel">
                                        <div class="card-header bg-white">
                                            <strong>Manual Evaluation</strong>
                                            <div class="small text-muted">Enter final marks, feedback, and pass/fail status.</div>
                                        </div>

                                        <div class="card-body">
                                            <form method="POST" action="{{ route(session('user_role') == 'Teacher' ? 'assessment.review.submit' : 'admin.assessment.review.submit', $result->id) }}" class="row g-3">
                                                @csrf

                                                <div class="col-md-6">
                                                    <label class="form-label fw-semibold">Final Marks</label>
                                                    <div class="input-group">
                                                        <input type="number"
                                                               name="marks_awarded"
                                                               class="form-control"
                                                               min="0"
                                                               max="{{ $result->total_marks }}"
                                                               value="{{ old('marks_awarded') }}"
                                                               required>
                                                        <span class="input-group-text">/ {{ $result->total_marks }}</span>
                                                    </div>
                                                </div>

                                                <div class="col-md-6">
                                                    <label class="form-label fw-semibold">Evaluation Status</label>
                                                    <select name="passed" class="form-select" required>
                                                        <option value="">Select status</option>
                                                        <option value="1" {{ old('passed') === '1' ? 'selected' : '' }}>Pass</option>
                                                        <option value="0" {{ old('passed') === '0' ? 'selected' : '' }}>Fail</option>
                                                    </select>
                                                </div>

                                                <div class="col-12">
                                                    <label class="form-label fw-semibold">Feedback</label>
                                                    <textarea name="feedback"
                                                              class="form-control"
                                                              rows="7"
                                                              maxlength="2000"
                                                              placeholder="Add concise feedback for the student.">{{ old('feedback') }}</textarea>
                                                    <div class="small text-muted mt-1">This feedback will be stored with the evaluated result.</div>
                                                </div>

                                                <div class="col-12">
                                                    <div class="alert alert-light border small mb-0">
                                                        Results, reports, certificates, and achievements will use these manually entered final marks.
                                                    </div>
                                                </div>

                                                <div class="col-12">
                                                    <button type="submit" class="btn btn-primary btn-lg w-100">
                                                        Submit Evaluation
                                                    </button>
                                                </div>
                                            </form>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    @endforeach
                @empty
                    <div class="card review-card">
                        <div class="card-body text-center text-muted py-5">
                            No pending submissions for review.
                        </div>
                    </div>
                @endforelse

                @if($pendingResults->hasPages())
                    <div class="mt-3">
                        {{ $pendingResults->links('pagination::bootstrap-5') }}
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>

@endsection

