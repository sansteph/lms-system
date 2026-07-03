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
                        Upload STEM Engineer PPTs with linked Student Word documents.
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

                    {{ $errors->first() }}

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
                            STEM Engineer PPTs
                        </h6>

                        <h2>

                            {{ $contents->whereNotNull('file_path')->count() }}

                        </h2>

                    </div>

                </div>

                <div class="col-md-3">

                    <div class="dashboard-card">

                        <h6>
                            Student Documents
                        </h6>

                        <h2>

                            {{ $contents->whereNotNull('student_file_path')->count() }}

                        </h2>

                    </div>

                </div>

                <div class="col-md-3">

                    <div class="dashboard-card">

                        <h6>
                            Released Lessons
                        </h6>

                        <h2>

                            {{ $contents->where('is_released', true)->count() }}

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
                                    Institute
                                </th>

                                <th>
                                    Course
                                </th>

                                <th>
                                    Materials
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
                                        {{ $content->institute ?? 'N/A' }}
                                    </td>

                                    <td>

                                        {{ $content->course->course_title ?? 'No Course' }}

                                    </td>

                                    <td>

                                        @if($content->file_path)
                                            <span class="badge bg-primary">
                                                STEM Engineer PPT
                                            </span>
                                        @endif

                                        @if($content->student_file_path)
                                            <span class="badge bg-info">
                                                Student Word Doc
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

                                        <button class="btn btn-sm btn-outline-primary"
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

                                    <td colspan="9"
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
                            <label class="form-label">Institute</label>

                            @if(session('user_role') == 'InstituteAdmin')

                                <input type="hidden"
                                    name="institute"
                                    value="{{ session('user_institute') }}">

                                <input type="text"
                                    class="form-control"
                                    value="{{ session('user_institute') }}"
                                    readonly>

                            @else

                                <input type="text"
                                    name="institute"
                                    class="form-control"
                                    required>

                            @endif
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
                                STEM Engineer Material
                            </label>

                            <select name="content_type"
                                    class="form-control"
                                    required>

                                <option value="PPT">
                                    PPT
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
                                Upload STEM Engineer PPT
                            </label>

                            <input type="file"
                                   name="file"
                                   class="form-control"
                                   accept=".ppt,.pptx"
                                   required>

                        </div>

                        <div class="col-md-6">

                            <label class="form-label">
                                Upload Linked Student Word Document
                            </label>

                            <input type="file"
                                   name="student_file"
                                   class="form-control"
                                   accept=".doc,.docx"
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
                            class="btn btn-outline-secondary"
                            data-bs-dismiss="modal">

                        Cancel

                    </button>

                    <button type="submit"
                        class="btn btn-primary">

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
                            <label class="form-label">Institute</label>

                            @if(session('user_role') == 'InstituteAdmin')

                                <input type="hidden"
                                    name="institute"
                                    value="{{ session('user_institute') }}">

                                <input type="text"
                                    class="form-control"
                                    value="{{ session('user_institute') }}"
                                    readonly>

                            @else

                                <input type="text"
                                    name="institute"
                                    class="form-control"
                                    value="{{ $content->institute }}"
                                    required>

                            @endif
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
                                STEM Engineer Material
                            </label>

                            <select name="content_type"
                                    class="form-control"
                                    required>

                                <option value="PPT"
                                    {{ $content->content_type == 'PPT' ? 'selected' : '' }}>

                                    PPT

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
                                Replace STEM Engineer PPT
                            </label>

                            <input type="file"
                                   name="file"
                                   class="form-control"
                                   accept=".ppt,.pptx">

                            <small class="text-muted">

                                Leave empty to keep existing PPT.

                            </small>

                        </div>

                        <div class="col-md-6">

                            <label class="form-label">
                                Replace Linked Student Word Document
                            </label>

                            <input type="file"
                                   name="student_file"
                                   class="form-control"
                                   accept=".doc,.docx">

                            <small class="text-muted">

                                Leave empty to keep existing student document.

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
                            class="btn btn-outline-secondary"
                            data-bs-dismiss="modal">

                        Cancel

                    </button>

                    <button type="submit"
                        class="btn btn-primary">

                        Update Lesson

                    </button>

                </div>

            </form>

        </div>

    </div>

</div>

@endforeach

@endsection

