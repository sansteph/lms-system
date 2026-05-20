@extends('layouts.app')

@section('content')

<div class="container-fluid">

    <div class="row">

        @include('layouts.sidebar')

        <div class="col-md-10 col-lg-10 p-4">

            <div class="page-header d-flex justify-content-between align-items-center flex-wrap gap-3 mb-4">

                <div>

                    <h2 class="mb-1">
                        Content Management
                    </h2>

                    <p class="text-muted mb-0">
                        Upload structured lessons under courses for sequential learning.
                    </p>

                </div>

                <button class="btn btn-primary btn-sm"
                        data-bs-toggle="modal"
                        data-bs-target="#uploadContentModal">

                    Upload Lesson

                </button>

            </div>

            @if(session('success'))

                <div class="alert alert-success">

                    {{ session('success') }}

                </div>

            @endif

            @if($errors->any())

                <div class="alert alert-danger">

                    Please fill all required fields correctly.

                </div>

            @endif

            <div class="row g-4 mb-4">

                <div class="col-md-3">

                    <div class="dashboard-card">

                        <h6>
                            Total Lessons
                        </h6>

                        <h2>

                            {{ $contents->count() }}

                        </h2>

                    </div>

                </div>

                <div class="col-md-3">

                    <div class="dashboard-card">

                        <h6>
                            PDF Files
                        </h6>

                        <h2>

                            {{ $contents->where('content_type', 'PDF')->count() }}

                        </h2>

                    </div>

                </div>

                <div class="col-md-3">

                    <div class="dashboard-card">

                        <h6>
                            PPT Files
                        </h6>

                        <h2>

                            {{ $contents->where('content_type', 'PPT')->count() }}

                        </h2>

                    </div>

                </div>

                <div class="col-md-3">

                    <div class="dashboard-card">

                        <h6>
                            Video Files
                        </h6>

                        <h2>

                            {{ $contents->where('content_type', 'Video')->count() }}

                        </h2>

                    </div>

                </div>

            </div>

            <div class="card shadow border-0">

                <div class="card-body">

                    <form method="GET"
                          action="{{ route('content') }}"
                          class="row mb-3">

                        <div class="col-md-4">

                            <input type="text"
                                   name="search"
                                   class="form-control"
                                   placeholder="Search lesson or course"
                                   value="{{ request('search') }}">

                        </div>

                        <div class="col-md-2">

                            <button type="submit"
                                    class="btn btn-primary w-100">

                                Search

                            </button>

                        </div>

                    </form>

                    <table class="table table-bordered table-hover align-middle">

                        <thead class="table-light">

                            <tr>

                                <th>
                                    Sl. No
                                </th>

                                <th>
                                    Lesson Title
                                </th>

                                <th>
                                    Course
                                </th>

                                <th>
                                    Type
                                </th>

                                <th>
                                    Lesson Order
                                </th>

                                <th>
                                    Assigned Class
                                </th>

                                <th>
                                    Status
                                </th>

                                <th width="180">
                                    Actions
                                </th>

                            </tr>

                        </thead>

                        <tbody>

                            @forelse($contents as $index => $content)

                                <tr>

                                    <td>

                                        {{ $index + 1 }}

                                    </td>

                                    <td>

                                        {{ $content->content_title }}

                                    </td>

                                    <td>

                                        {{ $content->course->course_title ?? 'No Course' }}

                                    </td>

                                    <td>

                                        @if($content->content_type == 'PDF')

                                            <span class="badge bg-info">
                                                PDF
                                            </span>

                                        @elseif($content->content_type == 'PPT')

                                            <span class="badge bg-primary">
                                                PPT
                                            </span>

                                        @else

                                            <span class="badge bg-danger">
                                                Video
                                            </span>

                                        @endif

                                    </td>

                                    <td>

                                        {{ $content->lesson_order }}

                                    </td>

                                    <td>

                                        {{ $content->assigned_class }}

                                    </td>

                                    <td>

                                        @if($content->status == 1)

                                            <span class="badge bg-success">
                                                Active
                                            </span>

                                        @else

                                            <span class="badge bg-danger">
                                                Inactive
                                            </span>

                                        @endif

                                    </td>

                                    <td>

                                        <button class="btn btn-sm btn-outline-warning"
                                                data-bs-toggle="modal"
                                                data-bs-target="#editContentModal{{ $content->id }}">

                                            Edit

                                        </button>

                                        <a href="{{ route('content.delete', $content->id) }}"
                                           class="btn btn-sm btn-outline-danger"
                                           onclick="return confirm('Are you sure you want to delete this content?')">

                                            Delete

                                        </a>

                                    </td>

                                </tr>

                            @empty

                                <tr>

                                    <td colspan="8"
                                        class="text-center text-muted">

                                        No content found

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

<!-- Upload Modal -->

<div class="modal fade"
     id="uploadContentModal"
     tabindex="-1">

    <div class="modal-dialog modal-lg modal-dialog-centered">

        <div class="modal-content">

            <form method="POST"
                  action="{{ route('content.store') }}"
                  enctype="multipart/form-data">

                @csrf

                <div class="modal-header">

                    <h5 class="modal-title">
                        Upload Lesson
                    </h5>

                    <button type="button"
                            class="btn-close"
                            data-bs-dismiss="modal">
                    </button>

                </div>

                <div class="modal-body">

                    <div class="row g-3">

                        <div class="col-md-6">

                            <label class="form-label">
                                Lesson Title
                            </label>

                            <input type="text"
                                   name="content_title"
                                   class="form-control"
                                   required>

                        </div>

                        <div class="col-md-6">

                            <label class="form-label">
                                Course
                            </label>

                            <select name="course_id"
                                    class="form-control"
                                    required>

                                <option value="">
                                    Select Course
                                </option>

                                @foreach($courses as $course)

                                    <option value="{{ $course->id }}">

                                        {{ $course->course_title }}

                                    </option>

                                @endforeach

                            </select>

                        </div>

                        <div class="col-md-6">

                            <label class="form-label">
                                Content Type
                            </label>

                            <select name="content_type"
                                    class="form-control"
                                    required>

                                <option value="">
                                    Select Content Type
                                </option>

                                <option value="PDF">
                                    PDF
                                </option>

                                <option value="PPT">
                                    PPT
                                </option>

                                <option value="Video">
                                    Video
                                </option>

                            </select>

                        </div>

                        <div class="col-md-6">

                            <label class="form-label">
                                Lesson Order
                            </label>

                            <input type="number"
                                   name="lesson_order"
                                   class="form-control"
                                   required>

                        </div>

                        <div class="col-md-6">

                            <label class="form-label">
                                Assigned Class
                            </label>

                            <input type="text"
                                   name="assigned_class"
                                   class="form-control"
                                   required>

                        </div>

                        <div class="col-md-6">

                            <label class="form-label">
                                Upload File
                            </label>

                            <input type="file"
                                   name="file"
                                   class="form-control"
                                   required>

                        </div>

                        <div class="col-md-6">

                            <label class="form-label">
                                Status
                            </label>

                            <select name="status"
                                    class="form-control"
                                    required>

                                <option value="1">
                                    Active
                                </option>

                                <option value="0">
                                    Inactive
                                </option>

                            </select>

                        </div>

                    </div>

                </div>

                <div class="modal-footer">

                    <button type="button"
                            class="btn btn-light"
                            data-bs-dismiss="modal">

                        Cancel

                    </button>

                    <button type="submit"
                            class="btn btn-success">

                        Upload Lesson

                    </button>

                </div>

            </form>

        </div>

    </div>

</div>

@foreach($contents as $content)

<div class="modal fade"
     id="editContentModal{{ $content->id }}"
     tabindex="-1">

    <div class="modal-dialog modal-lg modal-dialog-centered">

        <div class="modal-content">

            <form method="POST"
                  action="{{ route('content.update', $content->id) }}"
                  enctype="multipart/form-data">

                @csrf

                <div class="modal-header">

                    <h5 class="modal-title">
                        Edit Lesson
                    </h5>

                    <button type="button"
                            class="btn-close"
                            data-bs-dismiss="modal">
                    </button>

                </div>

                <div class="modal-body">

                    <div class="row g-3">

                        <div class="col-md-6">

                            <label class="form-label">
                                Lesson Title
                            </label>

                            <input type="text"
                                   name="content_title"
                                   class="form-control"
                                   value="{{ $content->content_title }}"
                                   required>

                        </div>

                        <div class="col-md-6">

                            <label class="form-label">
                                Course
                            </label>

                            <select name="course_id"
                                    class="form-control"
                                    required>

                                @foreach($courses as $course)

                                    <option value="{{ $course->id }}"
                                        {{ $content->course_id == $course->id ? 'selected' : '' }}>

                                        {{ $course->course_title }}

                                    </option>

                                @endforeach

                            </select>

                        </div>

                        <div class="col-md-6">

                            <label class="form-label">
                                Content Type
                            </label>

                            <select name="content_type"
                                    class="form-control"
                                    required>

                                <option value="PPT"
                                    {{ $content->content_type == 'PPT' ? 'selected' : '' }}>

                                    PPT

                                </option>

                                <option value="PDF"
                                    {{ $content->content_type == 'PDF' ? 'selected' : '' }}>

                                    PDF

                                </option>

                                <option value="Video"
                                    {{ $content->content_type == 'Video' ? 'selected' : '' }}>

                                    Video

                                </option>

                            </select>

                        </div>

                        <div class="col-md-6">

                            <label class="form-label">
                                Lesson Order
                            </label>

                            <input type="number"
                                   name="lesson_order"
                                   class="form-control"
                                   value="{{ $content->lesson_order }}"
                                   required>

                        </div>

                        <div class="col-md-6">

                            <label class="form-label">
                                Assigned Class
                            </label>

                            <input type="text"
                                   name="assigned_class"
                                   class="form-control"
                                   value="{{ $content->assigned_class }}"
                                   required>

                        </div>

                        <div class="col-md-6">

                            <label class="form-label">
                                Replace File
                            </label>

                            <input type="file"
                                   name="file"
                                   class="form-control">

                            <small class="text-muted">

                                Leave empty to keep existing file.

                            </small>

                        </div>

                        <div class="col-md-6">

                            <label class="form-label">
                                Status
                            </label>

                            <select name="status"
                                    class="form-control"
                                    required>

                                <option value="1"
                                    {{ $content->status == 1 ? 'selected' : '' }}>

                                    Active

                                </option>

                                <option value="0"
                                    {{ $content->status == 0 ? 'selected' : '' }}>

                                    Inactive

                                </option>

                            </select>

                        </div>

                    </div>

                </div>

                <div class="modal-footer">

                    <button type="button"
                            class="btn btn-light"
                            data-bs-dismiss="modal">

                        Cancel

                    </button>

                    <button type="submit"
                            class="btn btn-success">

                        Update Lesson

                    </button>

                </div>

            </form>

        </div>

    </div>

</div>

@endforeach

@endsection