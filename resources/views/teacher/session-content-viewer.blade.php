@extends('layouts.app')

@section('content')

<div class="container-fluid">
    <div class="row">

        @include('layouts.teacher-sidebar')

        <div class="col-md-10 col-lg-10 p-4">

            <div class="d-flex justify-content-between align-items-center mb-4">
                <div>
                    <h3 class="mb-1">{{ $content->content_title }}</h3>
                    <p class="text-muted mb-0">Session content viewer</p>
                </div>

                <a href="{{ route('teacher.classes') }}"
                   class="btn btn-outline-secondary">
                    Back to My Classes
                </a>
            </div>

            @php
                $extension = strtolower(pathinfo($content->file_path, PATHINFO_EXTENSION));
                $previewExtensions = ['ppt', 'pptx', 'doc', 'docx'];
                $previewUrl = route('content.preview', [$content->id, 'teacher']);
                $streamUrl = route('content.preview.stream', [$content->id, 'teacher']);
                $fileVariant = in_array($extension, $previewExtensions) ? 'preview' : 'file';
                $fileUrl = route('content.file.audience', [$content->id, 'teacher', $fileVariant]);
            @endphp

            <div class="card shadow border-0">
                <div class="card-body">

                    @if($extension == 'pdf' || in_array($extension, $previewExtensions))
                        <div class="protected-preview-surface"
                             data-watermark="InnovatEdge&#10;View Only"
                             data-preview-scope="content-{{ $content->id }}">
                            <div class="protected-preview-content">
                                <iframe src="{{ $previewUrl }}"
                                        width="100%"
                                        height="750"
                                        style="border: none;"
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
                                     class="img-fluid rounded"
                                     alt="Content Preview">
                            </div>
                        </div>

                    @elseif(in_array($extension, ['mp4', 'webm', 'ogg', 'mov']))
                        <div class="protected-preview-surface"
                             data-watermark="InnovatEdge&#10;View Only"
                             data-preview-scope="content-{{ $content->id }}">
                            <div class="protected-preview-content">
                                <video width="100%"
                                       height="650"
                                       controls
                                       controlsList="nodownload">
                                    <source src="{{ $fileUrl }}">
                                    Your browser does not support video preview.
                                </video>
                            </div>
                        </div>

                    @elseif(in_array($extension, ['mp3', 'wav']))
                        <audio controls controlsList="nodownload" class="w-100">
                            <source src="{{ $fileUrl }}">
                            Your browser does not support audio preview.
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
</div>

@include('content.preview-protection')

@endsection
