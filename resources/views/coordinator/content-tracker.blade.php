@extends('layouts.app')

@section('content')

<div class="container-fluid">
    <div class="row">

        @include('layouts.coordinator-sidebar')

        <div class="col-md-10 col-lg-10 p-4">

            <div class="page-header mb-4">
                <h2 class="mb-1">Content Tracker</h2>
                <p class="text-muted mb-0">
                    Monitor uploaded lessons and release status.
                </p>
            </div>

            <div class="card shadow border-0">
                <div class="card-body">

                    <table class="table table-bordered table-hover align-middle">
                        <thead class="table-light">
                            <tr>
                                <th>Sl. No</th>
                                <th>Lesson</th>
                                <th>Course</th>
                                <th>Institute</th>
                                <th>Class</th>
                                <th>Type</th>
                                <th>Status</th>
                                <th>Release</th>
                            </tr>
                        </thead>

                        <tbody>
                            @forelse($contents as $index => $content)
                                <tr>
                                    <td>{{ $index + 1 }}</td>
                                    <td>
                                        {{ $content->content_title }}
                                        <br>
                                        <small class="text-muted">
                                            Lesson {{ $content->lesson_order }}
                                        </small>
                                    </td>
                                    <td>{{ $content->course->course_title ?? 'N/A' }}</td>
                                    <td>{{ $content->institute ?? 'N/A' }}</td>
                                    <td>{{ $content->assigned_class }}</td>
                                    <td>{{ $content->content_type }}</td>
                                    <td>
                                        @if($content->status == 1)
                                            <span class="badge bg-success">Active</span>
                                        @else
                                            <span class="badge bg-danger">Inactive</span>
                                        @endif
                                    </td>
                                    <td>
                                        @if($content->is_released)
                                            <span class="badge bg-success">Released</span>
                                        @else
                                            <span class="badge bg-warning text-dark">Not Released</span>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="8" class="text-center text-muted">
                                        No content found.
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

@endsection
