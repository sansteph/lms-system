@extends('layouts.app')

@section('content')

<div class="container-fluid">
    <div class="row">

        @include('layouts.teacher-sidebar')

        <div class="col-md-10 col-lg-10 p-4">

            <div class="page-header mb-4">
                <h2 class="mb-1">Learning Content</h2>
                <p class="text-muted mb-0">
                    Access assigned PPT, PDF, and video lessons.
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
                        <h6>PPT Files</h6>
                        <h2>{{ $contents->where('content_type', 'PPT')->count() }}</h2>
                    </div>
                </div>

                <div class="col-md-3">
                    <div class="dashboard-card">
                        <h6>PDF Files</h6>
                        <h2>{{ $contents->where('content_type', 'PDF')->count() }}</h2>
                    </div>
                </div>

                <div class="col-md-3">
                    <div class="dashboard-card">
                        <h6>Video Files</h6>
                        <h2>{{ $contents->where('content_type', 'Video')->count() }}</h2>
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
                                <th>Priority</th>
                                <th>Access Rule</th>
                                <th>Status</th>
                                <th width="160">Action</th>
                            </tr>
                        </thead>

                        <tbody>
                            @forelse($contents as $index => $content)
                                <tr>
                                    <td>{{ $index + 1 }}</td>
                                    <td>{{ $content->content_title }}</td>
                                    <td>{{ $content->assigned_class }}</td>
                                    <td>
                                        @if($content->content_type == 'PDF')
                                            <span class="badge bg-info">PDF</span>
                                        @elseif($content->content_type == 'PPT')
                                            <span class="badge bg-primary">PPT</span>
                                        @else
                                            <span class="badge bg-danger">Video</span>
                                        @endif
                                    </td>
                                    <td>{{ $content->priority }}</td>
                                    <td>{{ $content->access_rule }}</td>
                                    <td>
                                        @if($content->status == 1)
                                            <span class="badge bg-success">Available</span>
                                        @else
                                            <span class="badge bg-secondary">Unavailable</span>
                                        @endif
                                    </td>
                                    <td>
                                        @if($content->file_path && $content->status == 1)
                                            <a href="{{ asset('storage/' . $content->file_path) }}"
                                               target="_blank"
                                               class="btn btn-sm btn-primary">
                                                Open
                                            </a>
                                        @else
                                            <button class="btn btn-sm btn-secondary" disabled>
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
                        Content uploaded by Admin will appear here for teachers.
                    </div>

                </div>
            </div>

        </div>

    </div>
</div>

@endsection