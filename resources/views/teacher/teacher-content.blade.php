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
                                <th width="160">Action</th>
                            </tr>
                        </thead>

                        <tbody>
                            @forelse($contents as $index => $content)
                                @php
                                    $teachingStatus = $teachingStatusByContentId[$content->id] ?? null;
                                @endphp
                                <tr>
                                    <td>{{ $index + 1 }}</td>
                                    <td>{{ $content->content_title }}</td>
                                    <td>{{ $content->assigned_class }}</td>
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
                                        @if($content->file_path && $content->status == 1)
                                            <button type="button"
                                                    class="btn btn-sm btn-outline-primary"
                                                    data-bs-toggle="modal"
                                                    data-bs-target="#contentPreviewModal{{ $content->id }}">
                                                View
                                            </button>
                                        @else
                                            <button class="btn btn-sm btn-outline-secondary" disabled>
                                                Locked
                                            </button>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="8" class="text-center text-muted">
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

@foreach($contents as $content)
    @if($content->file_path && $content->status == 1)
        @php
            $extension = strtolower(pathinfo($content->file_path, PATHINFO_EXTENSION));
            $previewUrl = route('content.preview', [$content->id, 'teacher']);
            $streamUrl = route('content.preview.stream', [$content->id, 'teacher']);
            $fileUrl = route('content.file.audience', [$content->id, 'teacher', 'file']);
        @endphp

        <div class="modal fade"
             id="contentPreviewModal{{ $content->id }}"
             tabindex="-1"
             aria-hidden="true">
            <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">{{ $content->content_title }}</h5>
                        <button type="button"
                                class="btn-close"
                                data-bs-dismiss="modal"
                                aria-label="Close">
                        </button>
                    </div>
                    <div class="modal-body">
                        @if($extension == 'pdf')
                            <div class="protected-preview-surface"
                                 data-watermark="InnovatEdge&#10;View Only"
                                 data-preview-scope="content-{{ $content->id }}">
                                <div class="protected-preview-content">
                                    <iframe src="{{ $previewUrl }}"
                                            width="100%"
                                            height="720"
                                            style="border: 0; border-radius: 8px; background: #f8f9fa;"
                                            oncontextmenu="return false;">
                                    </iframe>
                                    <div class="protected-preview-mouse-shield"
                                         aria-hidden="true">
                                    </div>
                                </div>
                            </div>
                        @elseif(in_array($extension, ['jpg', 'jpeg', 'png', 'gif', 'webp']))
                            <div class="text-center protected-preview-surface"
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
                                           height="620"
                                           controls
                                           controlsList="nodownload">
                                        <source src="{{ $fileUrl }}">
                                    </video>
                                </div>
                            </div>
                        @elseif(in_array($extension, ['mp3', 'wav']))
                            <audio controls controlsList="nodownload" class="w-100">
                                <source src="{{ $fileUrl }}">
                            </audio>
                        @else
                            <div class="alert alert-warning mb-0">
                                Inline preview is not available for this file type.
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    @endif
@endforeach

@include('content.preview-protection')

@endsection
