@extends('layouts.app')

@section('content')

<div class="container py-5">

    @if(session('error'))

        <div class="alert alert-danger">

            {{ session('error') }}

        </div>

    @endif

    <div class="card shadow border-0 mb-4">

        <div class="card-body">

            <h2>
                {{ $course->course_title }}
            </h2>

            <p class="text-muted mb-0">

                {{ $course->description }}

            </p>

        </div>

    </div>

    <div class="card shadow border-0">

        <div class="card-body">

            <h4 class="mb-4">
                Course Lessons
            </h4>

            <div class="card shadow border-0 mb-4">
                <div class="card-body">

                    <div class="d-flex justify-content-between align-items-center mb-2">

                        <h5 class="mb-0">
                            Course Progress
                        </h5>

                        <strong>
                            {{ $progressPercentage }}%
                        </strong>

                    </div>

                    @php
                        $progressWidth = max(0, min(100, (float) $progressPercentage));
                    @endphp

                    <progress class="w-100"
                              value="{{ $progressWidth }}"
                              max="100"
                              aria-label="Course progress"
                              style="height: 12px;">
                    </progress>

                    <p class="text-muted mt-2 mb-0">
                            {{ $completedLessons }} of {{ $totalLessons }} lessons completed.
                    </p>

                </div>

            </div>

            @if($enrollment->is_completed)

                <div class="alert alert-success">

                    <i class="fa fa-check-circle me-2"></i>

                    Course Completed Successfully

                    @if($enrollment->completed_at)

                        <br>

                        <small>
                            Completed on:
                            {{ \Carbon\Carbon::parse($enrollment->completed_at)->format('d M Y') }}
                        </small>

                    @endif

                </div>

            @endif
 

            @forelse($contents as $content)
                @php
                    $completed = \App\Models\LessonProgress::where('independent_learner_id', session('independent_learner_id'))
                        ->where('content_id', $content->id)
                        ->where('is_completed', true)
                        ->exists();
                @endphp

                <div class="border rounded p-3 mb-3">

                    <div class="d-flex justify-content-between align-items-center">

                        <div>

                            <h5 class="mb-1">

                                Lesson {{ $content->lesson_order }}

                            </h5>

                            <p class="mb-0">

                                {{ $content->content_title }}

                            </p>

                        </div>

                        <div>

                            <span class="badge bg-primary">

                                {{ $content->content_type }}

                            </span>

                        </div>

                    </div>

                    <hr>

                    @if($content->student_file_path || $content->file_path)

                        @php
                            $studentMaterialPath = $content->student_file_path ?: $content->file_path;
                            $studentPreviewPath = $content->student_preview_pdf_path ?: $content->preview_pdf_path;
                            $extension = strtolower(pathinfo($studentMaterialPath, PATHINFO_EXTENSION));
                            $previewExtensions = ['doc', 'docx', 'ppt', 'pptx'];
                            $streamVariant = in_array($extension, $previewExtensions) && $studentPreviewPath
                                ? 'preview'
                                : 'file';
                            $previewUrl = route('content.preview', [$content->id, 'student']);
                            $streamUrl = route('content.preview.stream', [$content->id, 'student']);
                            $fileUrl = route('content.file.audience', [$content->id, 'student', $streamVariant]);
                        @endphp

                        <div class="mb-3">
                            @if($extension == 'pdf' || $streamVariant == 'preview')
                                <div class="protected-preview-surface"
                                     data-watermark="InnovatEdge&#10;View Only"
                                     data-preview-scope="content-{{ $content->id }}">
                                    <div class="protected-preview-content">
                                        <iframe src="{{ $streamUrl }}#toolbar=0&navpanes=0&scrollbar=1&zoom=page-width"
                                                width="100%"
                                                height="420"
                                                style="border: 0; border-radius: 8px; background: #f8f9fa;"
                                                oncontextmenu="return false;">
                                        </iframe>
                                        <div class="protected-preview-mouse-shield"
                                             aria-hidden="true">
                                        </div>
                                    </div>
                                </div>
                            @elseif(in_array($extension, ['jpg', 'jpeg', 'png', 'gif', 'webp']))
                                <div class="protected-preview-surface"
                                     data-watermark="InnovatEdge&#10;View Only"
                                     data-preview-scope="content-{{ $content->id }}">
                                    <div class="protected-preview-content">
                                        <img src="{{ $fileUrl }}"
                                             class="img-fluid rounded border"
                                             alt="Content Preview">
                                    </div>
                                </div>
                            @elseif(in_array($extension, ['mp4', 'webm', 'ogg', 'mov']))
                                <div class="protected-preview-surface"
                                     data-watermark="InnovatEdge&#10;View Only"
                                     data-preview-scope="content-{{ $content->id }}">
                                    <div class="protected-preview-content">
                                        <video width="100%"
                                               height="360"
                                               controls
                                               controlsList="nodownload">
                                            <source src="{{ $fileUrl }}">
                                        </video>
                                    </div>
                                </div>
                            @else
                                <div class="alert alert-warning mb-0">
                                    Inline preview is not available for this file type.
                                </div>
                            @endif
                        </div>

                    @endif

                    @if($completed)

                        <span class="badge bg-success">
                            Completed
                        </span>

                    @else

                        <form method="POST"
                            action="{{ route('independent.lesson.complete', $content->id) }}"
                            class="d-inline">

                            @csrf

                            <button type="submit"
                                    class="btn btn-sm btn-primary">

                                Mark Complete

                            </button>

                        </form>

                    @endif

                </div>

                

            @empty

                <div class="alert alert-info">

                    No lessons available for this course yet.

                </div>

            @endforelse

        </div>

    </div>

</div>

@include('content.preview-protection')

@endsection
