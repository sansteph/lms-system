@extends('layouts.app')

@section('content')

<style>
    .course-content-list {
        max-height: 340px;
        overflow-y: auto;
        border: 1px solid #e5e7eb;
        border-radius: 8px;
        background: #f8fafc;
        padding: 8px;
    }

    .course-upload-list {
        max-height: 320px;
        overflow-y: auto;
        border: 1px solid #e5e7eb;
        border-radius: 8px;
        background: #f8fafc;
        padding: 8px;
    }

    .course-edit-modal .modal-content {
        max-height: calc(100vh - 3rem);
    }

    .course-edit-modal .modal-body {
        overflow-y: auto;
        padding-bottom: 1.25rem;
    }

    .course-edit-modal .modal-footer {
        position: sticky;
        bottom: 0;
        z-index: 2;
        flex-wrap: wrap;
        gap: 0.5rem;
        background: #ffffff;
        border-top: 1px solid #e5e7eb;
        box-shadow: 0 -8px 18px rgba(15, 23, 42, 0.06);
    }

    .course-institute-section {
        border: 1px solid #e5e7eb;
        border-radius: 10px;
        background: #ffffff;
        box-shadow: 0 10px 24px rgba(15, 23, 42, 0.06);
        overflow: hidden;
    }

    .course-institute-header {
        background: #f8fafc;
        border-bottom: 1px solid #e5e7eb;
        padding: 16px 18px;
    }

    .course-institute-body {
        padding: 18px;
    }
</style>

<div class="container-fluid">
    <div class="row">
        @include('layouts.sidebar')

        <div class="col-md-10 col-lg-10 p-4">
            <div class="page-header d-flex justify-content-between align-items-center flex-wrap gap-3 mb-4">
                <div>
                    <h2 class="mb-1">Courses Management</h2>
                    <p class="text-muted mb-0">
                        Create courses, upload lessons, and manage lesson order.
                    </p>
                </div>
            </div>

            @if(session('user_role') == 'Admin')
                <div class="card shadow border-0 mb-4">
                    <div class="card-body">
                        <form method="GET" action="{{ route('courses') }}" class="row g-3 align-items-end">
                            <div class="col-md-6">
                                <label class="form-label">Filter by Institute</label>
                                <select name="institute" class="form-select">
                                    <option value="">All Courses</option>
                                    <option value="__template_sources" {{ request('institute') == '__template_sources' ? 'selected' : '' }}>
                                        Template Source Courses
                                    </option>
                                    @foreach($institutes as $institute)
                                        <option value="{{ $institute->institute_name }}" {{ request('institute') == $institute->institute_name ? 'selected' : '' }}>
                                            {{ $institute->institute_name }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-6 d-flex gap-2">
                                <button type="submit" class="btn btn-primary">Apply Filter</button>
                                <a href="{{ route('courses') }}" class="btn btn-outline-secondary">Clear</a>
                            </div>
                        </form>
                    </div>
                </div>
            @endif

            @if(session('success'))
                <div class="alert alert-success">{{ session('success') }}</div>
            @endif

            @if($errors->any())
                <div class="alert alert-danger">{{ $errors->first() }}</div>
            @endif

            <div class="card mb-4 shadow border-0">
                <div class="card-body">
                    <h5 class="mb-3">Create Course</h5>

                    <form action="{{ route('courses.store') }}" method="POST" enctype="multipart/form-data">
                        @csrf

                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label">Course Title</label>
                                <input type="text" name="course_title" class="form-control" required>
                            </div>

                            <div class="col-md-6">
                                <label class="form-label">Institute</label>
                                @if(session('user_role') == 'InstituteAdmin')
                                    <input type="hidden" name="institute" value="{{ session('user_institute') }}">
                                    <input type="text" class="form-control" value="{{ session('user_institute') }}" readonly>
                                @else
                                    <select name="institute" id="createCourseInstitute" class="form-select" required>
                                        <option value="">Select Institute</option>
                                        @foreach($institutes as $institute)
                                            <option value="{{ $institute->institute_name }}">{{ $institute->institute_name }}</option>
                                        @endforeach
                                    </select>
                                @endif
                            </div>

                            @if(session('user_role') == 'Admin')
                                <div class="col-md-12">
                                    <label class="form-check">
                                        <input type="checkbox"
                                               name="is_template_source"
                                               value="1"
                                               id="createTemplateSourceToggle"
                                               class="form-check-input">
                                        <span class="form-check-label">
                                            Use as reusable Template Source course
                                        </span>
                                    </label>
                                    <small class="text-muted d-block">
                                        Template Source courses are not tied to an institute. Use them to create Teaching Plan Templates, then deploy copies to institutes.
                                    </small>
                                </div>
                            @endif

                            <div class="col-md-12">
                                <label class="form-label">Description</label>
                                <textarea name="description" class="form-control" rows="3"></textarea>
                            </div>

                            <div class="col-md-3">
                                <label class="form-label">Target</label>
                                <select name="target" class="form-select">
                                    <option value="Student">Student</option>
                                    <option value="Teacher">STEM Engineer</option>
                                    <option value="Both">Both</option>
                                </select>
                            </div>

                            <div class="col-md-3">
                                <label class="form-label">Assigned Class</label>
                                <input type="text" name="assigned_class" class="form-control">
                            </div>

                            <div class="col-md-3">
                                <label class="form-label">Course Price</label>
                                <input type="number" step="0.01" name="price" class="form-control" value="0" required>
                            </div>

                            <div class="col-md-3">
                                <label class="form-label">Availability</label>
                                <select name="availability_type" class="form-select" required>
                                    <option value="Institute">Institute Only</option>
                                    <option value="Independent">Hybrid Learners Only</option>
                                    <option value="Both">Both</option>
                                </select>
                            </div>

                            <div class="col-md-3">
                                <label class="form-label">Active Status</label>
                                <select name="is_active" class="form-select" required>
                                    <option value="1">Active</option>
                                    <option value="0">Inactive</option>
                                </select>
                            </div>

                            <div class="col-12">
                                <div class="d-flex justify-content-between align-items-center mb-2">
                                    <label class="form-label mb-0">Initial Course Contents <span class="text-muted">(Optional)</span></label>
                                    <button type="button" class="btn btn-sm btn-outline-primary" id="addCreateCourseContentRow">
                                        Add File
                                    </button>
                                </div>
                                <div class="course-upload-list">
                                    <div id="createCourseContentRows">
                                        <div class="course-upload-row border rounded p-3 mb-3 bg-white">
                                            <div class="row g-3">
                                                <div class="col-md-3">
                                                    <label class="form-label">Title</label>
                                                    <input type="text" name="contents[0][title]" class="form-control" placeholder="Auto from filename">
                                                </div>
                                                <div class="col-md-3">
                                                    <label class="form-label">Description</label>
                                                    <input type="text" name="contents[0][description]" class="form-control">
                                                </div>
                                                <div class="col-md-2">
                                                    <label class="form-label">Type</label>
                                                    <input type="text" name="contents[0][content_type]" class="form-control" placeholder="Auto">
                                                </div>
                                                <div class="col-md-2">
                                                    <label class="form-label">Order</label>
                                                    <input type="number" name="contents[0][sort_order]" class="form-control" min="1">
                                                </div>
                                                <div class="col-md-2">
                                                    <label class="form-label">Status</label>
                                                    <select name="contents[0][status]" class="form-select">
                                                        <option value="active">Active</option>
                                                        <option value="draft">Draft</option>
                                                        <option value="archived">Archived</option>
                                                    </select>
                                                </div>
                                                <div class="col-md-6">
                                                    <label class="form-label">STEM Engineer File</label>
                                                    <input type="file" name="contents[0][file]" class="form-control" accept=".pdf">
                                                </div>
                                                <div class="col-md-6">
                                                    <label class="form-label">Student File</label>
                                                    <input type="file" name="contents[0][student_file]" class="form-control" accept=".pdf">
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <button type="submit" class="btn btn-primary mt-4">Create Course</button>
                    </form>
                </div>
            </div>

            @forelse($courseGroups as $instituteName => $instituteCourses)
                <div class="course-institute-section mb-4">
                    <div class="course-institute-header d-flex justify-content-between align-items-center flex-wrap gap-2">
                        <div>
                            <h5 class="mb-1">{{ $instituteName }}</h5>
                            <div class="text-muted small">
                                {{ $instituteCourses->count() }} {{ Str::plural('course', $instituteCourses->count()) }}
                            </div>
                        </div>
                    </div>

                    <div class="course-institute-body">
                        <div class="row g-4">
                            @foreach($instituteCourses as $course)
                                <div class="col-12">
                                    <div class="card shadow-sm border">
                                        <div class="card-body">
                                            <div class="d-flex justify-content-between align-items-start flex-wrap gap-3 mb-3">
                                                <div>
                                                    <h5 class="mb-1">{{ $course->course_title }}</h5>
                                                    <div class="text-muted small">
                                                        {{ $course->assigned_class ?? 'No class set' }} |
                                                        {{ $course->availability_type ?? 'Institute' }}
                                                    </div>
                                                </div>

                                                <div class="d-flex gap-2 flex-wrap">
                                                    <button class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#editCourseModal{{ $course->id }}">
                                                        Edit Course
                                                    </button>
                                                    <button class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#uploadContentModal{{ $course->id }}">
                                                        Upload Content
                                                    </button>
                                                    <a href="{{ route('courses.delete', $course->id) }}"
                                                       class="btn btn-sm btn-outline-danger"
                                                       onclick="return confirm('Delete this course? Related course links and teaching plans will be removed.')">
                                                        Delete
                                                    </a>
                                                </div>
                                            </div>

                                            <div class="course-content-list">
                                                <table class="table table-sm table-bordered align-middle mb-0 bg-white">
                                                    <thead class="table-light">
                                                        <tr>
                                                            <th style="width: 90px;">Order</th>
                                                            <th>Content</th>
                                                            <th>Type</th>
                                                            <th>Status</th>
                                                            <th style="width: 280px;">Actions</th>
                                                        </tr>
                                                    </thead>
                                                    <tbody>
                                                        @forelse($course->courseContents->sortBy('sort_order') as $courseContent)
                                                            <tr>
                                                                <td>{{ $courseContent->sort_order }}</td>
                                                                <td>
                                                                    <strong>{{ $courseContent->content->content_title ?? 'Content' }}</strong>
                                                                    <div class="text-muted small">
                                                                        {{ $courseContent->content->assigned_class ?? $course->assigned_class ?? 'No class set' }}
                                                                    </div>
                                                                </td>
                                                                <td>{{ $courseContent->content->content_type ?? '-' }}</td>
                                                                <td>{{ ucfirst($courseContent->status) }}</td>
                                                                <td>
                                                                    @if($courseContent->content)
                                                                        @if($courseContent->content->aiSummary && $courseContent->content->aiSummary->status == 'generated')
                                                                            <span class="badge bg-info text-dark w-100 mb-2 py-2">
                                                                                AI Summary Ready
                                                                            </span>
                                                                        @elseif($courseContent->content->aiSummary && $courseContent->content->aiSummary->status == 'failed')
                                                                            <span class="badge bg-danger w-100 mb-2 py-2">
                                                                                AI Failed
                                                                            </span>
                                                                            @if($courseContent->content->aiSummary->error_message)
                                                                                <div class="alert alert-warning small py-2 px-3 mb-2">
                                                                                    {{ $courseContent->content->aiSummary->error_message }}
                                                                                </div>
                                                                            @endif
                                                                        @else
                                                                            <span class="badge bg-secondary w-100 mb-2 py-2">
                                                                                AI Not Generated
                                                                            </span>
                                                                        @endif

                                                                        <form method="POST"
                                                                              action="{{ route('content.ai-summary.generate', $courseContent->content->id) }}"
                                                                              class="mb-2"
                                                                              onsubmit="return confirm('Generate AI summary for this content?');">
                                                                            @csrf
                                                                            <button type="submit" class="btn btn-sm btn-outline-success w-100">
                                                                                {{ $courseContent->content->aiSummary && $courseContent->content->aiSummary->status == 'generated' ? 'Refresh AI' : 'Generate AI' }}
                                                                            </button>
                                                                        </form>

                                                                        <a href="{{ route('content.preview', [$courseContent->content->id, 'teacher']) }}"
                                                                           class="btn btn-sm btn-outline-primary w-100 mb-2"
                                                                           target="_blank"
                                                                           rel="noopener">
                                                                            View
                                                                        </a>
                                                                        <button type="button"
                                                                                class="btn btn-sm btn-outline-secondary w-100 mb-2"
                                                                                data-bs-toggle="modal"
                                                                                data-bs-target="#editCourseContentModal"
                                                                                data-action="{{ route('content.update', $courseContent->content->id) }}"
                                                                                data-course-id="{{ $course->id }}"
                                                                                data-institute="{{ $course->is_template_source ? '' : ($course->institute ?? session('user_institute')) }}"
                                                                                data-title="{{ $courseContent->content->content_title }}"
                                                                                data-type="{{ $courseContent->content->content_type }}"
                                                                                data-order="{{ $courseContent->content->lesson_order ?? $courseContent->sort_order }}"
                                                                                data-description="{{ $courseContent->content->description }}"
                                                                                data-assigned-class="{{ $courseContent->content->assigned_class ?? $course->assigned_class }}"
                                                                                data-status="{{ $courseContent->content->status ? 1 : 0 }}">
                                                                            Edit Content
                                                                        </button>
                                                                    @endif

                                                                    <form method="POST"
                                                                          action="{{ route('courses.contents.order', [$course->id, $courseContent->id]) }}"
                                                                          class="d-flex gap-2 mb-2">
                                                                        @csrf
                                                                        <input type="number" name="sort_order" class="form-control form-control-sm" value="{{ $courseContent->sort_order }}" min="1" required>
                                                                        <select name="status" class="form-select form-select-sm">
                                                                            @foreach(['active', 'draft', 'archived'] as $status)
                                                                                <option value="{{ $status }}" {{ $courseContent->status == $status ? 'selected' : '' }}>
                                                                                    {{ ucfirst($status) }}
                                                                                </option>
                                                                            @endforeach
                                                                        </select>
                                                                        <button class="btn btn-sm btn-outline-primary">Save</button>
                                                                    </form>

                                                                    <form method="POST"
                                                                          action="{{ route('courses.contents.detach', [$course->id, $courseContent->id]) }}"
                                                                          onsubmit="return confirm('Remove this content from the course? The uploaded file will be deleted.');">
                                                                        @csrf
                                                                        <button class="btn btn-sm btn-outline-danger w-100">Detach</button>
                                                                    </form>
                                                                </td>
                                                            </tr>
                                                        @empty
                                                            <tr>
                                                                <td colspan="5" class="text-center text-muted">
                                                                    No content attached yet.
                                                                </td>
                                                            </tr>
                                                        @endforelse
                                                    </tbody>
                                                </table>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>
            @empty
                <div class="card shadow border-0">
                    <div class="card-body text-center text-muted">
                        No courses created.
                    </div>
                </div>
            @endforelse
        </div>
    </div>
</div>

@foreach($courses as $course)
    <div class="modal fade" id="editCourseModal{{ $course->id }}" tabindex="-1">
        <div class="modal-dialog modal-lg modal-dialog-scrollable course-edit-modal">
            <div class="modal-content">
                <form method="POST" action="{{ route('courses.update', $course->id) }}">
                    @csrf
                    <div class="modal-header">
                        <h5 class="modal-title">Edit Course</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label">Course Title</label>
                                <input type="text" name="course_title" class="form-control" value="{{ $course->course_title }}" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Institute</label>
                                @if(session('user_role') == 'InstituteAdmin')
                                    <input type="hidden" name="institute" value="{{ session('user_institute') }}">
                                    <input type="text" class="form-control" value="{{ session('user_institute') }}" readonly>
                                @else
                                    <select name="institute"
                                            id="editCourseInstitute{{ $course->id }}"
                                            class="form-select"
                                            {{ $course->is_template_source ? '' : 'required' }}>
                                        <option value="">Select Institute</option>
                                        @foreach($institutes as $institute)
                                            <option value="{{ $institute->institute_name }}" {{ $course->institute == $institute->institute_name ? 'selected' : '' }}>
                                                {{ $institute->institute_name }}
                                            </option>
                                        @endforeach
                                    </select>
                                @endif
                            </div>
                            @if(session('user_role') == 'Admin')
                                <div class="col-md-12">
                                    <label class="form-check">
                                        <input type="checkbox"
                                               name="is_template_source"
                                               value="1"
                                               class="form-check-input edit-template-source-toggle"
                                               data-institute-field="editCourseInstitute{{ $course->id }}"
                                               {{ $course->is_template_source ? 'checked' : '' }}>
                                        <span class="form-check-label">
                                            Use as reusable Template Source course
                                        </span>
                                    </label>
                                    <small class="text-muted d-block">
                                        Template Source courses can be used to create Teaching Plan Templates and deploy institute copies.
                                    </small>
                                </div>
                            @endif
                            <div class="col-md-12">
                                <label class="form-label">Description</label>
                                <textarea name="description" class="form-control" rows="3">{{ $course->description }}</textarea>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Target</label>
                                <select name="target" class="form-select">
                                    @foreach(['Student' => 'Student', 'Teacher' => 'STEM Engineer', 'Both' => 'Both'] as $value => $label)
                                        <option value="{{ $value }}" {{ $course->target == $value ? 'selected' : '' }}>{{ $label }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Assigned Class</label>
                                <input type="text" name="assigned_class" class="form-control" value="{{ $course->assigned_class }}">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Course Price</label>
                                <input type="number" step="0.01" name="price" class="form-control" value="{{ $course->price ?? 0 }}" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Availability</label>
                                <select name="availability_type" class="form-select" required>
                                    @foreach(['Institute' => 'Institute Only', 'Independent' => 'Hybrid Learners Only', 'Both' => 'Both'] as $value => $label)
                                        <option value="{{ $value }}" {{ $course->availability_type == $value ? 'selected' : '' }}>{{ $label }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Active Status</label>
                                <select name="is_active" class="form-select" required>
                                    <option value="1" {{ $course->is_active == 1 ? 'selected' : '' }}>Active</option>
                                    <option value="0" {{ $course->is_active == 0 ? 'selected' : '' }}>Inactive</option>
                                </select>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">Update Course</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="modal fade" id="uploadContentModal{{ $course->id }}" tabindex="-1">
        <div class="modal-dialog modal-xl modal-dialog-scrollable">
            <div class="modal-content">
                <form method="POST" action="{{ route('courses.upload-content', $course->id) }}" enctype="multipart/form-data">
                    @csrf
                    <div class="modal-header">
                        <h5 class="modal-title">Upload Content to {{ $course->course_title }}</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <div class="text-muted small">
                                Uploaded files are stored directly as course contents.
                            </div>
                            <button type="button" class="btn btn-sm btn-outline-primary add-course-upload-row" data-course="{{ $course->id }}">
                                Add File
                            </button>
                        </div>

                        <div class="course-upload-list">
                            <div id="courseUploadRows{{ $course->id }}">
                                <div class="course-upload-row border rounded p-3 mb-3 bg-white">
                                    <div class="row g-3">
                                        <div class="col-md-3">
                                            <label class="form-label">Title</label>
                                            <input type="text" name="contents[0][title]" class="form-control" placeholder="Auto from filename">
                                        </div>
                                        <div class="col-md-3">
                                            <label class="form-label">Description</label>
                                            <input type="text" name="contents[0][description]" class="form-control">
                                        </div>
                                        <div class="col-md-2">
                                            <label class="form-label">Type</label>
                                            <input type="text" name="contents[0][content_type]" class="form-control" placeholder="Auto">
                                        </div>
                                        <div class="col-md-2">
                                            <label class="form-label">Order</label>
                                            <input type="number" name="contents[0][sort_order]" class="form-control" min="1">
                                        </div>
                                        <div class="col-md-3">
                                            <label class="form-label">Class</label>
                                            <input type="text" name="contents[0][assigned_class]" class="form-control" value="{{ $course->assigned_class }}">
                                        </div>
                                        <div class="col-md-2">
                                            <label class="form-label">Section</label>
                                            <input type="text" name="contents[0][section]" class="form-control">
                                        </div>
                                        <div class="col-md-2">
                                            <label class="form-label">Status</label>
                                            <select name="contents[0][status]" class="form-select">
                                                <option value="active">Active</option>
                                                <option value="draft">Draft</option>
                                                <option value="archived">Archived</option>
                                            </select>
                                        </div>
                                        <div class="col-md-3">
                                            <label class="form-label">STEM Engineer File</label>
                                            <input type="file" name="contents[0][file]" class="form-control" accept=".pdf" required>
                                        </div>
                                        <div class="col-md-2">
                                            <label class="form-label">Student File</label>
                                            <input type="file" name="contents[0][student_file]" class="form-control" accept=".pdf">
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">Upload and Attach</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

@endforeach

<div class="modal fade" id="editCourseContentModal" tabindex="-1">
    <div class="modal-dialog modal-lg modal-dialog-scrollable">
        <div class="modal-content">
            <form method="POST" action="#" enctype="multipart/form-data" id="editCourseContentForm">
                @csrf
                <input type="hidden" name="course_id" id="editContentCourseId">
                <input type="hidden" name="institute" id="editContentInstitute">

                <div class="modal-header">
                    <h5 class="modal-title">Edit Content</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>

                <div class="modal-body">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">Title</label>
                            <input type="text" name="content_title" id="editContentTitle" class="form-control" required>
                        </div>

                        <div class="col-md-3">
                            <label class="form-label">Type</label>
                            <input type="text" name="content_type" id="editContentType" class="form-control" required>
                        </div>

                        <div class="col-md-3">
                            <label class="form-label">Lesson Order</label>
                            <input type="number" name="lesson_order" id="editContentOrder" class="form-control" min="1" required>
                        </div>

                        <div class="col-md-12">
                            <label class="form-label">Description</label>
                            <textarea name="description" id="editContentDescription" class="form-control" rows="3"></textarea>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">Assigned Class</label>
                            <input type="text" name="assigned_class" id="editContentAssignedClass" class="form-control" required>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">Status</label>
                            <select name="status" id="editContentStatus" class="form-select" required>
                                <option value="1">Active</option>
                                <option value="0">Inactive</option>
                            </select>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">Replace STEM Engineer File</label>
                            <input type="file" name="file" id="editContentFile" class="form-control" accept=".pdf">
                            <small class="text-muted">Leave empty to keep the current file.</small>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">Replace Student File</label>
                            <input type="file" name="student_file" id="editContentStudentFile" class="form-control" accept=".pdf">
                            <small class="text-muted">Leave empty to keep the current student file.</small>
                        </div>
                    </div>
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Update Content</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        const rowCounts = {};
        let createCourseContentIndex = 1;
        const createRows = document.getElementById('createCourseContentRows');
        const addCreateRowButton = document.getElementById('addCreateCourseContentRow');
        const createTemplateToggle = document.getElementById('createTemplateSourceToggle');
        const createInstituteField = document.getElementById('createCourseInstitute');

        function syncTemplateSourceField(toggle, instituteField) {
            if (!toggle || !instituteField) {
                return;
            }

            if (toggle.checked) {
                instituteField.value = '';
                instituteField.required = false;
                instituteField.disabled = true;
                instituteField.placeholder = 'Not required for Template Source';
            } else {
                instituteField.disabled = false;
                instituteField.required = true;
                instituteField.placeholder = '';
            }
        }

        syncTemplateSourceField(createTemplateToggle, createInstituteField);

        if (createTemplateToggle && createInstituteField) {
            createTemplateToggle.addEventListener('change', function () {
                syncTemplateSourceField(createTemplateToggle, createInstituteField);
            });
        }

        document.querySelectorAll('.edit-template-source-toggle').forEach(function (toggle) {
            const instituteField = document.getElementById(toggle.dataset.instituteField);
            syncTemplateSourceField(toggle, instituteField);

            toggle.addEventListener('change', function () {
                syncTemplateSourceField(toggle, instituteField);
            });
        });

        const editContentModal = document.getElementById('editCourseContentModal');
        const editContentForm = document.getElementById('editCourseContentForm');

        if (editContentModal && editContentForm) {
            editContentModal.addEventListener('show.bs.modal', function (event) {
                const button = event.relatedTarget;

                if (!button) {
                    return;
                }

                editContentForm.action = button.dataset.action || '#';
                document.getElementById('editContentCourseId').value = button.dataset.courseId || '';
                document.getElementById('editContentInstitute').value = button.dataset.institute || '';
                document.getElementById('editContentTitle').value = button.dataset.title || '';
                document.getElementById('editContentType').value = button.dataset.type || '';
                document.getElementById('editContentOrder').value = button.dataset.order || '1';
                document.getElementById('editContentDescription').value = button.dataset.description || '';
                document.getElementById('editContentAssignedClass').value = button.dataset.assignedClass || '';
                document.getElementById('editContentStatus').value = button.dataset.status || '1';
                document.getElementById('editContentFile').value = '';
                document.getElementById('editContentStudentFile').value = '';
            });
        }

        if (createRows && addCreateRowButton) {
            addCreateRowButton.addEventListener('click', function () {
                const index = createCourseContentIndex;
                const row = document.createElement('div');
                row.className = 'course-upload-row border rounded p-3 mb-3 bg-white';
                row.innerHTML = `
                    <div class="row g-3">
                        <div class="col-md-3"><label class="form-label">Title</label><input type="text" name="contents[${index}][title]" class="form-control" placeholder="Auto from filename"></div>
                        <div class="col-md-3"><label class="form-label">Description</label><input type="text" name="contents[${index}][description]" class="form-control"></div>
                        <div class="col-md-2"><label class="form-label">Type</label><input type="text" name="contents[${index}][content_type]" class="form-control" placeholder="Auto"></div>
                        <div class="col-md-2"><label class="form-label">Order</label><input type="number" name="contents[${index}][sort_order]" class="form-control" min="1"></div>
                        <div class="col-md-2"><label class="form-label">Status</label><select name="contents[${index}][status]" class="form-select"><option value="active">Active</option><option value="draft">Draft</option><option value="archived">Archived</option></select></div>
                        <div class="col-md-6"><label class="form-label">STEM Engineer File</label><input type="file" name="contents[${index}][file]" class="form-control" accept=".pdf"></div>
                        <div class="col-md-6"><label class="form-label">Student File</label><input type="file" name="contents[${index}][student_file]" class="form-control" accept=".pdf"></div>
                        <div class="col-md-12 text-end"><button type="button" class="btn btn-sm btn-outline-danger remove-course-upload-row">Remove</button></div>
                    </div>
                `;
                createRows.appendChild(row);
                createCourseContentIndex += 1;
            });
        }

        document.querySelectorAll('.add-course-upload-row').forEach(function (button) {
            button.addEventListener('click', function () {
                const courseId = button.dataset.course;
                const rows = document.getElementById('courseUploadRows' + courseId);
                rowCounts[courseId] = (rowCounts[courseId] || 1);
                const index = rowCounts[courseId];
                const row = document.createElement('div');
                row.className = 'course-upload-row border rounded p-3 mb-3 bg-white';
                row.innerHTML = `
                    <div class="row g-3">
                        <div class="col-md-3"><label class="form-label">Title</label><input type="text" name="contents[${index}][title]" class="form-control" placeholder="Auto from filename"></div>
                        <div class="col-md-3"><label class="form-label">Description</label><input type="text" name="contents[${index}][description]" class="form-control"></div>
                        <div class="col-md-2"><label class="form-label">Type</label><input type="text" name="contents[${index}][content_type]" class="form-control" placeholder="Auto"></div>
                        <div class="col-md-2"><label class="form-label">Order</label><input type="number" name="contents[${index}][sort_order]" class="form-control" min="1"></div>
                        <div class="col-md-3"><label class="form-label">Class</label><input type="text" name="contents[${index}][assigned_class]" class="form-control"></div>
                        <div class="col-md-2"><label class="form-label">Section</label><input type="text" name="contents[${index}][section]" class="form-control"></div>
                        <div class="col-md-2"><label class="form-label">Status</label><select name="contents[${index}][status]" class="form-select"><option value="active">Active</option><option value="draft">Draft</option><option value="archived">Archived</option></select></div>
                        <div class="col-md-3"><label class="form-label">STEM Engineer File</label><input type="file" name="contents[${index}][file]" class="form-control" accept=".pdf" required></div>
                        <div class="col-md-2"><label class="form-label">Student File</label><input type="file" name="contents[${index}][student_file]" class="form-control" accept=".pdf"></div>
                        <div class="col-md-12 text-end"><button type="button" class="btn btn-sm btn-outline-danger remove-course-upload-row">Remove</button></div>
                    </div>
                `;
                rows.appendChild(row);
                rowCounts[courseId] += 1;
            });
        });

        document.addEventListener('click', function (event) {
            const removeButton = event.target.closest('.remove-course-upload-row');
            if (removeButton) {
                removeButton.closest('.course-upload-row').remove();
            }
        });
    });
</script>

@endsection
