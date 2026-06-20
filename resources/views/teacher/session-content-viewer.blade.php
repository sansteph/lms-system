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
                $fileUrl = asset('storage/' . $content->file_path);
                $extension = strtolower(pathinfo($content->file_path, PATHINFO_EXTENSION));
            @endphp

            <div class="card shadow border-0">
                <div class="card-body">

                    @if(in_array($extension, ['pdf']))
                        <iframe src="{{ $fileUrl }}"
                                width="100%"
                                height="750"
                                style="border: none;">
                        </iframe>

                    @elseif(in_array($extension, ['jpg', 'jpeg', 'png', 'gif', 'webp']))
                        <div class="text-center">
                            <img src="{{ $fileUrl }}"
                                 class="img-fluid rounded"
                                 alt="Content Preview">
                        </div>

                    @elseif(in_array($extension, ['mp4', 'webm', 'ogg']))
                        <video width="100%"
                               height="650"
                               controls>
                            <source src="{{ $fileUrl }}">
                            Your browser does not support video preview.
                        </video>

                    @elseif(in_array($extension, ['mp3', 'wav']))
                        <audio controls class="w-100">
                            <source src="{{ $fileUrl }}">
                            Your browser does not support audio preview.
                        </audio>

                    @elseif(in_array($extension, ['ppt', 'pptx']))

                        @if($content->preview_pdf_path)

                            <iframe src="{{ asset('storage/' . $content->preview_pdf_path) }}"
                                    width="100%"
                                    height="750"
                                    style="border: none;">
                            </iframe>

                        @else

                            <div class="alert alert-warning">
                                PDF preview is not available for this presentation.
                            </div>

                            <a href="{{ asset('storage/' . $content->file_path) }}"
                            class="btn btn-primary"
                            target="_blank">
                                Download Presentation
                            </a>

                        @endif

                    @endif

                </div>
            </div>

        </div>

    </div>
</div>

@endsection