@extends('layouts.app')

@section('content')

<div class="container-fluid">
    <div class="row">
        @include('layouts.sidebar')

        <div class="col-md-10 col-lg-10 p-4">
            <div class="page-header mb-4">
                <h2 class="mb-1">Question Paper Approval</h2>
                <p class="text-muted mb-0">Review uploaded assessment question papers before students can use them.</p>
            </div>

            @if(session('success'))
                <div class="alert alert-success">{{ session('success') }}</div>
            @endif

            <div class="card shadow border-0">
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-bordered table-hover align-middle">
                            <thead class="table-light">
                                <tr>
                                    <th>Sl. No</th>
                                    <th>Assessment</th>
                                    <th>STEM Engineer</th>
                                    <th>Institute</th>
                                    <th>Class</th>
                                    <th>Category</th>
                                    <th>Assessment Date</th>
                                    <th>Marks</th>
                                    <th>Paper</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @php
                                    $groupedAssessments = $assessments
                                        ->sortBy([
                                            ['institute', 'asc'],
                                            ['assigned_class', 'asc'],
                                            ['assessment_date', 'desc'],
                                        ])
                                        ->groupBy(fn ($assessment) => $assessment->institute ?: 'Unassigned Institute');
                                    $rowNumber = 1;
                                @endphp

                                @forelse($groupedAssessments as $instituteName => $instituteAssessments)
                                    <tr class="table-primary">
                                        <td colspan="11" class="fw-semibold">
                                            {{ $instituteName }} · {{ $instituteAssessments->count() }} question paper{{ $instituteAssessments->count() == 1 ? '' : 's' }}
                                        </td>
                                    </tr>

                                    @foreach($instituteAssessments->groupBy(fn ($assessment) => $assessment->assigned_class ?: 'Unassigned Class') as $classLabel => $classAssessments)
                                        <tr class="table-light">
                                            <td colspan="11" class="fw-semibold ps-4">
                                                {{ $classLabel }} · {{ $classAssessments->count() }} question paper{{ $classAssessments->count() == 1 ? '' : 's' }}
                                            </td>
                                        </tr>

                                        @foreach($classAssessments as $assessment)
                                            @php
                                                $extension = strtolower(pathinfo($assessment->file_path ?? '', PATHINFO_EXTENSION));
                                                $previewExtensions = ['ppt', 'pptx', 'doc', 'docx'];
                                                $paperVariant = in_array($extension, $previewExtensions) && $assessment->question_paper_preview_path ? 'preview' : 'file';
                                            @endphp
                                            <tr>
                                                <td>{{ $rowNumber++ }}</td>
                                                <td>{{ $assessment->assessment_title }}</td>
                                                <td>{{ $assessment->teacher->name ?? 'N/A' }}</td>
                                                <td>{{ $assessment->institute ?? 'N/A' }}</td>
                                                <td>{{ $assessment->assigned_class }}</td>
                                                <td>{{ $assessment->assessment_category ?? 'Monthly' }}</td>
                                                <td>{{ $assessment->assessment_date ? \Carbon\Carbon::parse($assessment->assessment_date)->format('d M Y') : 'Not Set' }}</td>
                                                <td>{{ $assessment->total_marks }}</td>
                                                <td>
                                                    @if($assessment->file_path)
                                                        <button type="button" class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#paperModal{{ $assessment->id }}">View Inline</button>
                                                    @else
                                                        <span class="badge bg-secondary">Missing</span>
                                                    @endif
                                                </td>
                                                <td>
                                                    @if($assessment->question_paper_status == 'Approved')
                                                        <span class="badge bg-success">Approved</span>
                                                    @elseif($assessment->question_paper_status == 'Rejected')
                                                        <span class="badge bg-danger">Rejected</span>
                                                    @else
                                                        <span class="badge bg-warning text-dark">Pending Approval</span>
                                                    @endif
                                                    @if($assessment->questionPaperReviewer)
                                                        <div class="small text-muted mt-1">By {{ $assessment->questionPaperReviewer->name }}</div>
                                                    @endif
                                                </td>
                                                <td>
                                                    <div class="d-flex flex-column gap-2">
                                                        <form method="POST" action="{{ route('admin.question-papers.approve', $assessment->id) }}">
                                                            @csrf
                                                            <button type="submit" class="btn btn-sm btn-success" {{ $assessment->question_paper_status == 'Approved' ? 'disabled' : '' }}>Approve</button>
                                                        </form>

                                                        <form method="POST" action="{{ route('admin.question-papers.reject', $assessment->id) }}">
                                                            @csrf
                                                            <button type="submit" class="btn btn-sm btn-outline-danger">Reject</button>
                                                        </form>
                                                    </div>
                                                </td>
                                            </tr>
                                        @endforeach
                                    @endforeach
                                @empty
                                    <tr><td colspan="11" class="text-center text-muted">No question papers uploaded yet.</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

@foreach($assessments as $assessment)
    @if($assessment->file_path)
        @php
            $extension = strtolower(pathinfo($assessment->file_path ?? '', PATHINFO_EXTENSION));
            $previewExtensions = ['ppt', 'pptx', 'doc', 'docx'];
            $paperVariant = in_array($extension, $previewExtensions) && $assessment->question_paper_preview_path ? 'preview' : 'file';
            $paperUrl = route('assessment.paper', [$assessment->id, $paperVariant]);
        @endphp
        <div class="modal fade" id="paperModal{{ $assessment->id }}" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">{{ $assessment->assessment_title }}</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        @if($extension == 'pdf' || $paperVariant == 'preview')
                            <iframe src="{{ $paperUrl }}" width="100%" height="720" style="border: 0; border-radius: 8px; background: #f8f9fa;"></iframe>
                        @elseif(in_array($extension, ['jpg', 'jpeg', 'png', 'webp']))
                            <img src="{{ $paperUrl }}" class="img-fluid rounded border" alt="Question Paper">
                        @else
                            <div class="alert alert-warning mb-0">Inline preview is not available for this question paper type.</div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    @endif
@endforeach

@endsection

