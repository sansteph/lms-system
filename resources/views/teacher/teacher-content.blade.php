@extends('layouts.app')

@section('content')

<div class="container-fluid">
    <div class="row">

        @include('layouts.teacher-sidebar')

        <div class="col-md-10 col-lg-10 p-4">

            <div class="page-header mb-4">
                <h2 class="mb-1">Learning Content</h2>
                <p class="text-muted mb-0">
                    Access assigned STEM Engineer PPT lessons.
                </p>
            </div>

            <div class="row g-4 mb-4">
                <div class="col-md-3">
                    <div class="dashboard-card">
                        <h6>Total Content</h6>
                        <h2>{{ $contents->count() }}</h2>
                    </div>
                </div>

                <div class="col-md-3">
                    <div class="dashboard-card">
                        <h6>STEM Engineer PPTs</h6>
                        <h2>{{ $contents->where('content_type', 'PPT')->count() }}</h2>
                    </div>
                </div>

                <div class="col-md-3">
                    <div class="dashboard-card">
                        <h6>Released to Students</h6>
                        <h2>{{ $contents->where('is_released', true)->count() }}</h2>
                    </div>
                </div>

                <div class="col-md-3">
                    <div class="dashboard-card">
                        <h6>Student Docs Linked</h6>
                        <h2>{{ $contents->whereNotNull('student_file_path')->count() }}</h2>
                    </div>
                </div>
            </div>

            <div class="card shadow border-0 mb-4">
                <div class="card-body">
                    <form method="GET" action="{{ route('teacher.content') }}" class="row g-3 align-items-end">
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
                            <a href="{{ route('teacher.content') }}" class="btn btn-outline-secondary w-100">Clear</a>
                        </div>
                    </form>
                </div>
            </div>

            <div class="card shadow border-0">
                <div class="card-body">

                    <table class="table table-bordered table-hover align-middle">
                        <thead class="table-light">
                            <tr>
                                <th>Sl. No</th>
                                <th>Title</th>
                                <th>Class</th>
                                <th>Type</th>
                                <th>Lesson Order</th>
                                <th>Access Rule</th>
                                <th>Status</th>
                                <th>Training Assessment</th>
                                <th width="190">Action</th>
                            </tr>
                        </thead>

                        <tbody>
                            @php $rowNumber = 1; @endphp
                            @forelse($contents->groupBy(fn ($content) => $contentClassByContentId[$content->id] ?? $content->assigned_class ?? 'Unassigned Class') as $classLabel => $classContents)
                                <tr class="table-primary">
                                    <td colspan="9" class="fw-semibold">{{ $classLabel }}</td>
                                </tr>
                                @foreach($classContents as $content)
                                    @php
                                        $teachingStatus = $teachingStatusByContentId[$content->id] ?? null;
                                        $effectiveAiSummary = $content->effective_ai_summary;
                                        $aiTrainingApplies = $aiTrainingRequiredContentIds->contains($content->id);
                                        $gradeLevel = preg_replace('/\s+/', ' ', trim($contentGradeByContentId->get($content->id) ?? ''));
                                        $contentPassKey = $content->id . '|' . ($gradeLevel ?: 'all');
                                        $sourcePassKey = $content->ai_quiz_content_id ? $content->ai_quiz_content_id . '|' . ($gradeLevel ?: 'all') : null;
                                        $contentAnyGradePassKey = $content->id . '|all';
                                        $sourceAnyGradePassKey = $content->ai_quiz_content_id ? $content->ai_quiz_content_id . '|all' : null;
                                        $prepCleared = $teacherPassedPrepKeys->contains($contentPassKey)
                                            || $teacherPassedPrepKeys->contains($sourcePassKey)
                                            || $teacherPassedPrepKeys->contains($contentAnyGradePassKey)
                                            || $teacherPassedPrepKeys->contains($sourceAnyGradePassKey);
                                    @endphp
                                    <tr>
                                        <td>{{ $rowNumber++ }}</td>
                                        <td>{{ $content->content_title }}</td>
                                        <td>{{ $classLabel }}</td>
                                        <td>
                                            @if($content->file_path)
                                                <span class="badge bg-primary">PPT</span>
                                            @else
                                                <span class="badge bg-secondary">Missing</span>
                                            @endif
                                        </td>
                                        <td>{{ $content->lesson_order }}</td>
                                        <td>{{ $content->access_rule }}</td>
                                        <td>
                                            @if($content->status == 1)
                                                <span class="badge bg-success">Available</span>
                                            @else
                                                <span class="badge bg-secondary">Unavailable</span>
                                            @endif

                                            <div class="mt-2">
                                                @if($inProgressContentIds->contains($content->id))
                                                    <span class="badge bg-info text-dark">In Progress</span>
                                                @elseif($teachingStatus === 'completed')
                                                    <span class="badge bg-primary">Completed</span>
                                                @elseif($teachingStatus === 'released')
                                                    <span class="badge bg-warning text-dark">Released</span>
                                                @endif
                                            </div>

                                            <div class="small text-muted mt-1">
                                                {{ $content->is_released ? 'Student access enabled' : 'Not released to students' }}
                                            </div>
                                        </td>
                                        <td>
                                            @if(!$aiTrainingApplies)
                                                <span class="badge bg-light text-dark border">Not Required</span>
                                            @elseif($effectiveAiSummary && $effectiveAiSummary->status == 'generated')
                                                <span class="badge bg-success mb-2">Training Ready</span>
                                                <br>
                                                <a href="{{ route('teacher.ai-prep', ['id' => $content->id, 'grade' => $contentGradeByContentId->get($content->id)]) }}"
                                                   class="btn btn-sm btn-outline-success">
                                                    {{ $prepCleared ? 'AI Summary' : 'Prep Assessment' }}
                                                </a>
                                            @elseif($effectiveAiSummary && $effectiveAiSummary->status == 'failed')
                                                <span class="badge bg-danger">Training Failed</span>
                                            @else
                                                <span class="badge bg-secondary">Not Generated</span>
                                            @endif
                                        </td>
                                        <td>
                                            @if($content->file_path && $content->status == 1)
                                                <a href="{{ route('content.preview', [$content->id, 'teacher']) }}"
                                                   class="btn btn-sm btn-outline-primary"
                                                   target="_blank"
                                                   rel="noopener">
                                                    View
                                                </a>
                                            @else
                                                <button class="btn btn-sm btn-outline-secondary" disabled>
                                                    Locked
                                                </button>
                                            @endif
                                        </td>
                                    </tr>
                                @endforeach
                            @empty
                                <tr>
                                    <td colspan="9" class="text-center text-muted">
                                        No content assigned yet
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>

                    <div class="alert alert-info mt-3 mb-0">
                        Content uploaded by Admin will appear here for STEM Engineers.
                    </div>

                </div>
            </div>

        </div>

    </div>
</div>

@endsection
