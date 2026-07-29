@extends('layouts.app')

@section('content')

<style>
    .bulk-upload-modal {
        height: calc(100vh - 32px);
        margin-top: 16px;
        margin-bottom: 16px;
        align-items: stretch;
    }

    .bulk-upload-modal .modal-content {
        height: 100%;
        max-height: none;
        overflow: hidden;
    }

    .bulk-upload-modal form {
        display: flex;
        flex-direction: column;
        height: 100%;
        min-height: 0;
    }

    .bulk-upload-modal .modal-header,
    .bulk-upload-modal .modal-footer {
        flex: 0 0 auto;
        background: #fff;
        z-index: 2;
    }

    .bulk-upload-modal .modal-body {
        flex: 1 1 auto;
        min-height: 0;
        overflow-y: auto;
        overflow-x: hidden;
    }

    .bulk-upload-lesson-scroll {
        max-height: 340px;
        overflow-y: auto;
        overflow-x: hidden;
        padding: 4px 8px 4px 4px;
        border: 1px solid #e5e7eb;
        border-radius: 8px;
        background: #f8fafc;
    }

    .bulk-upload-lesson-scroll .bulk-content-row:last-child {
        margin-bottom: 0 !important;
    }

    @media (max-height: 760px) {
        .bulk-upload-lesson-scroll {
            max-height: 240px;
        }
    }
</style>

<div class="container-fluid">

    <div class="row">

        @include('layouts.sidebar')

        <div class="col-md-10 col-lg-10 p-4">

            <div class="page-header d-flex justify-content-between align-items-center flex-wrap gap-3 mb-4">

                <div>

                    <h2 class="mb-1">
                        Course Contents
                    </h2>

                    <p class="text-muted mb-0">
                        Upload course contents, manage lesson order, and control content status.
                    </p>

                </div>

                <div class="d-flex gap-2">
                    <button class="btn btn-primary btn-sm"
                            data-bs-toggle="modal"
                            data-bs-target="#bulkUploadContentModal">

                        Attach Contents

                    </button>

                </div>

            </div>

            @if(session('success'))

                <div class="alert alert-success">

                    {{ session('success') }}

                </div>

            @endif

            @if(session('error'))

                <div class="alert alert-danger">

                    {{ session('error') }}

                </div>

            @endif

            @if($errors->any())

                <div class="alert alert-danger">

                    {{ $errors->first() }}

                </div>

            @endif

            @php
                $contentRows = method_exists($contents, 'getCollection') ? $contents->getCollection() : collect($contents);
            @endphp

            @include('partials.section-navigator', ['sectionPager' => $sectionPager ?? null])

            <div class="row g-4 mb-4">

                <div class="col-md-3">

                    <div class="dashboard-card">

                        <h6>
                            Attached Contents
                        </h6>

                        <h2>

                            {{ $contentRows->count() }}

                        </h2>

                    </div>

                </div>

                <div class="col-md-3">

                    <div class="dashboard-card">

                        <h6>
                            STEM Engineer PPTs
                        </h6>

                        <h2>

                            {{ $contentRows->whereNotNull('file_path')->count() }}

                        </h2>

                    </div>

                </div>

                <div class="col-md-3">

                    <div class="dashboard-card">

                        <h6>
                            Student Documents
                        </h6>

                        <h2>

                            {{ $contentRows->whereNotNull('student_file_path')->count() }}

                        </h2>

                    </div>

                </div>

                <div class="col-md-3">

                    <div class="dashboard-card">

                        <h6>
                            Released Lessons
                        </h6>

                        <h2>

                            {{ $contentRows->where('is_released', true)->count() }}

                        </h2>

                    </div>

                </div>

            </div>

            <div class="card shadow border-0">

                <div class="card-body">

                    <form method="GET"
                          action="{{ route('content') }}"
                          class="row g-3 mb-3">

                        <div class="col-md-4">

                            <input type="text"
                                   name="search"
                                   class="form-control"
                                   placeholder="Search lesson or course"
                                   value="{{ request('search') }}">

                        </div>

                        <div class="col-md-4">

                            <select name="course_id" class="form-control">
                                <option value="">All Courses</option>
                                @foreach($courses as $course)
                                    <option value="{{ $course->id }}" {{ (string) $selectedCourseId === (string) $course->id ? 'selected' : '' }}>
                                        {{ $course->course_title }}
                                    </option>
                                @endforeach
                            </select>

                        </div>

                        <div class="col-md-2">

                            <button type="submit"
                                    class="btn btn-primary w-100">

                                Search

                            </button>

                        </div>

                        <div class="col-md-2">

                            <a href="{{ route('content') }}"
                               class="btn btn-outline-secondary w-100">
                                Reset
                            </a>

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
                                    Lesson Order / Status
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

                                        @if($content->courseContent)
                                            <form method="POST"
                                                  action="{{ route('content.course-content.order', $content->courseContent->id) }}"
                                                  class="d-flex gap-2 align-items-center">
                                                @csrf
                                                <input type="number"
                                                       name="sort_order"
                                                       class="form-control form-control-sm"
                                                       value="{{ $content->courseContent->sort_order }}"
                                                       min="1"
                                                       style="width: 82px"
                                                       required>
                                                <select name="status"
                                                        class="form-control form-control-sm"
                                                        style="width: 105px"
                                                        required>
                                                    @foreach(['active', 'draft', 'archived'] as $status)
                                                        <option value="{{ $status }}" {{ $content->courseContent->status == $status ? 'selected' : '' }}>
                                                            {{ ucfirst($status) }}
                                                        </option>
                                                    @endforeach
                                                </select>
                                                <button type="submit"
                                                        class="btn btn-sm btn-outline-primary">
                                                    Save
                                                </button>
                                            </form>
                                        @else
                                            {{ $content->lesson_order }}
                                        @endif

                                    </td>

                                    <td>

                                        {{ $content->assigned_class }}

                                    </td>

                                    <td>

                                        @php
                                            $displayStatus = $content->courseContent->status ?? ($content->status == 1 ? 'active' : 'draft');
                                        @endphp

                                        @if($displayStatus == 'active')

                                            <span class="badge bg-success">
                                                Active
                                            </span>

                                        @elseif($displayStatus == 'archived')

                                            <span class="badge bg-secondary">
                                                Archived
                                            </span>

                                        @else

                                            <span class="badge bg-warning text-dark">
                                                Draft
                                            </span>

                                        @endif

                                    </td>

                                    <td>

                                        <button class="btn btn-sm btn-outline-primary"
                                                data-bs-toggle="modal"
                                                data-bs-target="#editContentModal{{ $content->id }}">

                                            Edit

                                        </button>

                                        @if($content->courseContent)
                                            <form method="POST"
                                                  action="{{ route('content.course-content.detach', $content->courseContent->id) }}"
                                                  class="d-inline"
                                                  onsubmit="return confirm('Remove this content from the course? The uploaded file will be deleted.');">
                                                @csrf
                                                <button type="submit"
                                                        class="btn btn-sm btn-outline-danger">
                                                    Detach
                                                </button>
                                            </form>
                                        @else
                                            <a href="{{ route('content.delete', $content->id) }}"
                                               class="btn btn-sm btn-outline-danger"
                                               onclick="return confirm('Are you sure you want to delete this content?')">

                                                Delete

                                            </a>
                                        @endif

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

                @if(method_exists($contents, 'links'))
                    <div class="px-3 pb-3">
                        {{ $contents->links('pagination::bootstrap-5') }}
                    </div>
                @endif

            </div>

        </div>

    </div>

</div>

<!-- Bulk Upload Modal -->

<div class="modal fade"
     id="bulkUploadContentModal"
     tabindex="-1">

    <div class="modal-dialog modal-xl bulk-upload-modal">

        <div class="modal-content">

            <form method="POST"
                  action="{{ route('content.bulk-store') }}"
                  enctype="multipart/form-data">

                @csrf

                <div class="modal-header">

                    <h5 class="modal-title">
                        Bulk Upload Course Contents
                    </h5>

                    <button type="button"
                            class="btn-close"
                            data-bs-dismiss="modal">
                    </button>

                </div>

                <div class="modal-body">

                    <div class="row g-3 mb-4">

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
                                    id="bulkCourseSelect"
                                    class="form-control"
                                    required>

                                <option value="">
                                    Select Course
                                </option>

                                @foreach($courses as $course)

                                    <option value="{{ $course->id }}"
                                            data-class="{{ $course->assigned_class }}">

                                        {{ $course->course_title }}

                                    </option>

                                @endforeach

                            </select>

                        </div>

                        <div class="col-md-6">

                            <label class="form-label">
                                Assigned Class
                            </label>

                            <input type="text"
                                   id="bulkAssignedClass"
                                   class="form-control"
                                   readonly>

                        </div>

                        <div class="col-md-6">
                            <div class="alert alert-info mb-0">
                                Each row below can use its own class, section, content type, order, and status.
                            </div>

                        </div>

                    </div>

                    <div class="d-flex justify-content-between align-items-center mb-3">

                        <div>
                            <h6 class="mb-1">
                                Lesson Files
                            </h6>
                            <small class="text-muted">
                                Each row becomes a course content item for the selected course.
                            </small>
                        </div>

                        <button type="button"
                                class="btn btn-sm btn-outline-primary"
                                id="addBulkContentRow">
                            Add Lesson
                        </button>

                    </div>

                    <div class="bulk-upload-lesson-scroll">
                        <div id="bulkContentRows">
                        <div class="border rounded p-3 mb-3 bulk-content-row"
                             data-row-index="0">
                            <div class="row g-3 align-items-end">
                                <div class="col-md-3">
                                    <label class="form-label">Lesson Title</label>
                                    <input type="text"
                                           name="contents[0][content_title]"
                                           class="form-control"
                                           required>
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label">Description</label>
                                    <input type="text"
                                           name="contents[0][description]"
                                           class="form-control">
                                </div>
                                <div class="col-md-2">
                                    <label class="form-label">Content Type</label>
                                    <select name="contents[0][content_type]"
                                            class="form-control"
                                            required>
                                        <option value="PPT">PPT</option>
                                        <option value="Document">Document</option>
                                        <option value="PDF">PDF</option>
                                    </select>
                                </div>
                                <div class="col-md-2">
                                    <label class="form-label">Lesson Order</label>
                                    <input type="number"
                                           name="contents[0][lesson_order]"
                                           class="form-control"
                                           min="1"
                                           required>
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label">Class</label>
                                    <input type="text"
                                           name="contents[0][assigned_class]"
                                           class="form-control"
                                           placeholder="Defaults to course class">
                                </div>
                                <div class="col-md-2">
                                    <label class="form-label">Section</label>
                                    <input type="text"
                                           name="contents[0][section]"
                                           class="form-control">
                                </div>
                                <div class="col-md-2">
                                    <label class="form-label">Status</label>
                                    <select name="contents[0][status]"
                                            class="form-control"
                                            required>
                                        <option value="active">Active</option>
                                        <option value="draft">Draft</option>
                                        <option value="archived">Archived</option>
                                    </select>
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label">Content File</label>
                                    <input type="file"
                                           name="contents[0][file]"
                                           class="form-control"
                                           accept=".pdf"
                                           required>
                                </div>
                                <div class="col-md-2">
                                    <label class="form-label">Student File <span class="text-muted">(Optional)</span></label>
                                    <input type="file"
                                           name="contents[0][student_file]"
                                           class="form-control"
                                           accept=".pdf">
                                </div>
                                <div class="col-12 text-end">
                                    <button type="button"
                                            class="btn btn-sm btn-outline-danger remove-bulk-content-row"
                                            disabled>
                                        Remove
                                    </button>
                                </div>
                            </div>
                        </div>
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

                        Upload Course Contents

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
                        Edit Attached Lesson
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

                        <div class="col-md-12">

                            <label class="form-label">
                                Description
                            </label>

                            <textarea name="description"
                                      class="form-control"
                                      rows="2">{{ $content->description }}</textarea>

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
                                   accept=".pdf">

                            <small class="text-muted">

                                Leave empty to keep existing PPT.

                            </small>

                        </div>

                        <div class="col-md-6">

                            <label class="form-label">
                                Replace Linked Student Word Document
                                <span class="text-muted">(Optional)</span>
                            </label>

                            <input type="file"
                                   name="student_file"
                                   class="form-control"
                                   accept=".pdf">

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

<script>
    document.addEventListener('DOMContentLoaded', function () {
        const rowsContainer = document.getElementById('bulkContentRows');
        const addRowButton = document.getElementById('addBulkContentRow');
        const bulkCourseSelect = document.getElementById('bulkCourseSelect');
        const bulkAssignedClass = document.getElementById('bulkAssignedClass');
        let rowIndex = 1;

        function syncBulkAssignedClass() {
            if (!bulkCourseSelect || !bulkAssignedClass) {
                return;
            }

            const selectedOption = bulkCourseSelect.options[bulkCourseSelect.selectedIndex];
            bulkAssignedClass.value = selectedOption ? (selectedOption.dataset.class || '') : '';
        }

        function updateRemoveButtons() {
            const rows = rowsContainer.querySelectorAll('.bulk-content-row');

            rows.forEach(function (row) {
                const removeButton = row.querySelector('.remove-bulk-content-row');

                if (removeButton) {
                    removeButton.disabled = rows.length === 1;
                }
            });
        }

        function createBulkRow(index) {
            const row = document.createElement('div');
            row.className = 'border rounded p-3 mb-3 bulk-content-row';
            row.dataset.rowIndex = index;
            row.innerHTML = `
                <div class="row g-3 align-items-end">
                    <div class="col-md-3">
                        <label class="form-label">Lesson Title</label>
                        <input type="text"
                               name="contents[${index}][content_title]"
                               class="form-control"
                               required>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Description</label>
                        <input type="text"
                               name="contents[${index}][description]"
                               class="form-control">
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">Content Type</label>
                        <select name="contents[${index}][content_type]"
                                class="form-control"
                                required>
                            <option value="PPT">PPT</option>
                            <option value="Document">Document</option>
                            <option value="PDF">PDF</option>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">Lesson Order</label>
                        <input type="number"
                               name="contents[${index}][lesson_order]"
                               class="form-control"
                               min="1"
                               required>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Class</label>
                        <input type="text"
                               name="contents[${index}][assigned_class]"
                               class="form-control"
                               placeholder="Defaults to course class">
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">Section</label>
                        <input type="text"
                               name="contents[${index}][section]"
                               class="form-control">
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">Status</label>
                        <select name="contents[${index}][status]"
                                class="form-control"
                                required>
                            <option value="active">Active</option>
                            <option value="draft">Draft</option>
                            <option value="archived">Archived</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Content File</label>
                        <input type="file"
                               name="contents[${index}][file]"
                               class="form-control"
                               accept=".pdf"
                               required>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">Student File <span class="text-muted">(Optional)</span></label>
                        <input type="file"
                               name="contents[${index}][student_file]"
                               class="form-control"
                               accept=".pdf">
                    </div>
                    <div class="col-12 text-end">
                        <button type="button"
                                class="btn btn-sm btn-outline-danger remove-bulk-content-row">
                            Remove
                        </button>
                    </div>
                </div>
            `;

            return row;
        }

        if (addRowButton && rowsContainer) {
            addRowButton.addEventListener('click', function () {
                rowsContainer.appendChild(createBulkRow(rowIndex));
                rowIndex += 1;
                updateRemoveButtons();
            });

            rowsContainer.addEventListener('click', function (event) {
                const removeButton = event.target.closest('.remove-bulk-content-row');

                if (!removeButton) {
                    return;
                }

                const rows = rowsContainer.querySelectorAll('.bulk-content-row');

                if (rows.length <= 1) {
                    return;
                }

                removeButton.closest('.bulk-content-row').remove();
                updateRemoveButtons();
            });

            updateRemoveButtons();
        }

        if (bulkCourseSelect) {
            bulkCourseSelect.addEventListener('change', syncBulkAssignedClass);
            syncBulkAssignedClass();
        }
    });
</script>

@endsection
