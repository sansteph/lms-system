@extends('layouts.app')

@section('content')

<div class="container-fluid">
    <div class="row">

        @include('layouts.sidebar')

        <div class="col-md-10 col-lg-10 p-4">

            <h2 class="mb-4">
                Courses Management
            </h2>

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

            <div class="card mb-4 shadow border-0">
                <div class="card-body">

                    <form action="{{ route('courses.store') }}" method="POST">
                        @csrf

                        <div class="row g-3">

                            <div class="col-md-6">
                                <label class="form-label">Course Title</label>
                                <input type="text"
                                       name="course_title"
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

                            <div class="col-md-12">
                                <label class="form-label">Description</label>
                                <textarea name="description"
                                          class="form-control"
                                          rows="3"></textarea>
                            </div>

                            <div class="col-md-4">
                                <label class="form-label">Target</label>
                                <select name="target" class="form-select">
                                    <option value="Student">Student</option>
                                    <option value="Teacher">STEM Engineer</option>
                                    <option value="Both">Both</option>
                                </select>
                            </div>

                            <div class="col-md-4">
                                <label class="form-label">Assigned Class</label>
                                <input type="text"
                                       name="assigned_class"
                                       class="form-control">
                            </div>

                            <div class="col-md-4">
                                <label class="form-label">Course Price (₹)</label>
                                <input type="number"
                                       step="0.01"
                                       name="price"
                                       class="form-control"
                                       value="0"
                                       required>
                            </div>

                            <div class="col-md-6">
                                <label class="form-label">Availability</label>
                                <select name="availability_type"
                                        class="form-select"
                                        required>
                                    <option value="Institute">Institute Only</option>
                                    <option value="Independent">Hybrid Learners Only</option>
                                    <option value="Both">Both</option>
                                </select>
                            </div>

                            <div class="col-md-6">
                                <label class="form-label">Active Status</label>
                                <select name="is_active"
                                        class="form-select"
                                        required>
                                    <option value="1">Active</option>
                                    <option value="0">Inactive</option>
                                </select>
                            </div>

                        </div>

                        <button type="submit" class="btn btn-primary mt-4">
                            Create Course
                        </button>

                    </form>

                </div>
            </div>

            <div class="card shadow border-0">
                <div class="card-body">

                    <div class="table-responsive">
                        <table class="table table-bordered table-hover align-middle">

                            <thead class="table-light">
                                <tr>
                                    <th>Course</th>
                                    <th>Institute</th>
                                    <th>Target</th>
                                    <th>Class</th>
                                    <th>Price</th>
                                    <th>Availability</th>
                                    <th>Status</th>
                                    <th style="width: 120px;">Actions</th>
                                </tr>
                            </thead>

                            <tbody>
                                @forelse($courses as $course)
                                    <tr>
                                        <td>{{ $course->course_title }}</td>

                                        <td>{{ $course->institute ?? 'N/A' }}</td>

                                        <td>{{ $course->target }}</td>

                                        <td>{{ $course->assigned_class ?? '-' }}</td>

                                        <td>₹{{ number_format($course->price ?? 0, 2) }}</td>

                                        <td>{{ $course->availability_type ?? 'Institute' }}</td>

                                        <td>
                                            @if($course->is_active)
                                                <span class="badge bg-success">Active</span>
                                            @else
                                                <span class="badge bg-danger">Inactive</span>
                                            @endif
                                        </td>

                                        <td>
                                            <div class="d-flex flex-column gap-2">

                                                <button class="btn btn-sm btn-warning"
                                                        data-bs-toggle="modal"
                                                        data-bs-target="#editCourseModal{{ $course->id }}">
                                                    Edit
                                                </button>

                                                <a href="{{ route('courses.delete', $course->id) }}"
                                                   class="btn btn-sm btn-danger"
                                                   onclick="return confirm('Delete this course?')">
                                                    Delete
                                                </a>

                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="8" class="text-center text-muted">
                                            No courses created
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
</div>

@foreach($courses as $course)

<div class="modal fade"
     id="editCourseModal{{ $course->id }}"
     tabindex="-1">

    <div class="modal-dialog modal-lg modal-dialog-centered">

        <div class="modal-content">

            <form method="POST"
                  action="{{ route('courses.update', $course->id) }}">

                @csrf

                <div class="modal-header">
                    <h5 class="modal-title">
                        Edit Course
                    </h5>

                    <button type="button"
                            class="btn-close"
                            data-bs-dismiss="modal">
                    </button>
                </div>

                <div class="modal-body">

                    <div class="row g-3">

                        <div class="col-md-6">
                            <label class="form-label">Course Title</label>
                            <input type="text"
                                   name="course_title"
                                   class="form-control"
                                   value="{{ $course->course_title }}"
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
                                       value="{{ $course->institute }}"
                                       required>
                            @endif
                        </div>

                        <div class="col-md-12">
                            <label class="form-label">Description</label>
                            <textarea name="description"
                                      class="form-control"
                                      rows="3">{{ $course->description }}</textarea>
                        </div>

                        <div class="col-md-4">
                            <label class="form-label">Target</label>
                            <select name="target" class="form-select">
                                <option value="Student" {{ $course->target == 'Student' ? 'selected' : '' }}>
                                    Student
                                </option>

                                <option value="Teacher" {{ $course->target == 'Teacher' ? 'selected' : '' }}>
                                    STEM Engineer
                                </option>

                                <option value="Both" {{ $course->target == 'Both' ? 'selected' : '' }}>
                                    Both
                                </option>
                            </select>
                        </div>

                        <div class="col-md-4">
                            <label class="form-label">Assigned Class</label>
                            <input type="text"
                                   name="assigned_class"
                                   class="form-control"
                                   value="{{ $course->assigned_class }}">
                        </div>

                        <div class="col-md-4">
                            <label class="form-label">Course Price (₹)</label>
                            <input type="number"
                                   step="0.01"
                                   name="price"
                                   class="form-control"
                                   value="{{ $course->price ?? 0 }}"
                                   required>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">Availability</label>
                            <select name="availability_type"
                                    class="form-select"
                                    required>
                                <option value="Institute" {{ $course->availability_type == 'Institute' ? 'selected' : '' }}>
                                    Institute Only
                                </option>

                                <option value="Independent" {{ $course->availability_type == 'Independent' ? 'selected' : '' }}>
                                    Hybrid Learners Only
                                </option>

                                <option value="Both" {{ $course->availability_type == 'Both' ? 'selected' : '' }}>
                                    Both
                                </option>
                            </select>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">Active Status</label>
                            <select name="is_active"
                                    class="form-select"
                                    required>
                                <option value="1" {{ $course->is_active == 1 ? 'selected' : '' }}>
                                    Active
                                </option>

                                <option value="0" {{ $course->is_active == 0 ? 'selected' : '' }}>
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
                        Update Course
                    </button>
                </div>

            </form>

        </div>

    </div>

</div>

@endforeach

@endsection