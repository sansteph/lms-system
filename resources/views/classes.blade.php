@extends('layouts.app')

@section('content')

<div class="container-fluid">
    <div class="row">

        @include('layouts.sidebar')

        <div class="col-md-10 col-lg-10 p-4">

            <div class="page-header d-flex justify-content-between align-items-center flex-wrap gap-3 mb-4">
                <div>
                    <h2 class="mb-1">Class Management</h2>
                    <p class="text-muted mb-0">
                        Manage classes, sections, STEM Engineers, and academic year details.
                    </p>
                </div>

                <button class="btn btn-primary btn-sm"
                        data-bs-toggle="modal"
                        data-bs-target="#addClassModal">
                    Add Class
                </button>
            </div>

            @if(session('success'))
                <div class="alert alert-success">{{ session('success') }}</div>
            @endif

            @if($errors->any())
                <div class="alert alert-danger">{{ $errors->first() }}</div>
            @endif

            <div class="card shadow border-0">
                <div class="card-body">

                    <form method="GET" action="{{ route('classes') }}" class="row mb-3">
                        <div class="col-md-4">
                            <input type="text"
                                   name="search"
                                   class="form-control"
                                   placeholder="Search by class or STEM Engineers"
                                   value="{{ request('search') }}">
                        </div>

                        <div class="col-md-2">
                            <button type="submit" class="btn btn-primary w-100">
                                Search
                            </button>
                        </div>
                    </form>

                    <table class="table table-bordered table-hover align-middle">
                        <thead class="table-light">
                            <tr>
                                <th>Sl. No</th>
                                <th>Class</th>
                                <th>Institute</th>
                                <th>Section</th>
                                <th>STEM Engineer</th>
                                <th>Academic Year</th>
                                <th>Status</th>
                                <th width="180">Actions</th>
                            </tr>
                        </thead>

                        <tbody>
                            @forelse($classes as $index => $class)

                                <tr>
                                    <td>{{ $index + 1 }}</td>
                                    <td>{{ $class->class_name }}</td>
                                    <td>{{ $class->institute ?? 'N/A' }}</td>
                                    <td>{{ $class->section }}</td>
                                    <td>{{ $class->class_teacher }}</td>
                                    <td>{{ $class->academic_year }}</td>
                                    <td>
                                        @if($class->status == 1)
                                            <span class="badge bg-success">Active</span>
                                        @else
                                            <span class="badge bg-danger">Inactive</span>
                                        @endif
                                    </td>

                                    <td>
                                        <button class="btn btn-sm btn-outline-primary"
                                                data-bs-toggle="modal"
                                                data-bs-target="#editClassModal{{ $class->id }}">
                                            Edit
                                        </button>

                                        <a href="{{ route('classes.delete', $class->id) }}"
                                           class="btn btn-sm btn-outline-danger"
                                           onclick="return confirm('Are you sure you want to delete this class?')">
                                            Delete
                                        </a>
                                    </td>
                                </tr>

                                <div class="modal fade" id="editClassModal{{ $class->id }}" tabindex="-1">
                                    <div class="modal-dialog modal-lg modal-dialog-centered">
                                        <div class="modal-content">

                                            <form method="POST" action="{{ route('classes.update', $class->id) }}">
                                                @csrf

                                                <div class="modal-header">
                                                    <h5 class="modal-title">Edit Class</h5>
                                                    <button type="button"
                                                            class="btn-close"
                                                            data-bs-dismiss="modal">
                                                    </button>
                                                </div>

                                                <div class="modal-body">

                                                    <div class="row g-3">

                                                        <div class="col-md-6">
                                                            <label class="form-label">Class Name</label>
                                                            <input type="text"
                                                                   name="class_name"
                                                                   class="form-control"
                                                                   value="{{ $class->class_name }}"
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
                                                                       value="{{ $class->institute }}"
                                                                       required>
                                                            @endif
                                                        </div>

                                                        <div class="col-md-6">
                                                            <label class="form-label">Section</label>
                                                            <input type="text"
                                                                   name="section"
                                                                   class="form-control"
                                                                   value="{{ $class->section }}"
                                                                   required>
                                                        </div>

                                                        <div class="col-md-6">
                                                            <label class="form-label">STEM Engineer</label>
                                                            <select name="class_teacher"
                                                                    class="form-control"
                                                                    required>
                                                                <option value="">
                                                                    Select STEM Engineer
                                                                </option>
                                                                @foreach($stemEngineers as $engineer)

                                                                    <option value="{{ $engineer->name }}"
                                                                        {{ $class->class_teacher == $engineer->name ? 'selected' : '' }}>

                                                                        {{ $engineer->name }} - {{ $engineer->email }}

                                                                    </option>
                                                                @endforeach
                                                            </select>
                                                        </div>

                                                        <div class="col-md-6">
                                                            <label class="form-label">Academic Year</label>
                                                            <input type="text"
                                                                   name="academic_year"
                                                                   class="form-control"
                                                                   value="{{ $class->academic_year }}"
                                                                   required>
                                                        </div>

                                                        <div class="col-md-6">
                                                            <label class="form-label">Status</label>
                                                            <select name="status"
                                                                    class="form-control"
                                                                    required>
                                                                <option value="1" {{ $class->status == 1 ? 'selected' : '' }}>
                                                                    Active
                                                                </option>

                                                                <option value="0" {{ $class->status == 0 ? 'selected' : '' }}>
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
                                                        Update Class
                                                    </button>
                                                </div>

                                            </form>

                                        </div>
                                    </div>
                                </div>

                            @empty

                                <tr>
                                    <td colspan="9" class="text-center text-muted">
                                        No classes found
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

<div class="modal fade" id="addClassModal" tabindex="-1">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content">

            <form method="POST" action="{{ route('classes.store') }}">
                @csrf

                <div class="modal-header">
                    <h5 class="modal-title">Add Class</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>

                <div class="modal-body">
                    <div class="row g-3">

                        <div class="col-md-6">
                            <label class="form-label">Class Name</label>
                            <input type="text"
                                   name="class_name"
                                   class="form-control"
                                   placeholder="Example: VIII"
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
                                       placeholder="Enter institute name"
                                       required>
                            @endif
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">Section</label>
                            <input type="text"
                                   name="section"
                                   class="form-control"
                                   placeholder="Example: A"
                                   required>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">STEM Engineer</label>
                            <select name="class_teacher"
                                    class="form-control"
                                    required>
                                <option value="">
                                    Select STEM Engineer
                                </option>
                                @foreach($stemEngineers as $engineer)

                                    <option value="{{ $engineer->name }}">
                                        {{ $engineer->name }} - {{ $engineer->email }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">Academic Year</label>
                            <input type="text"
                                   name="academic_year"
                                   class="form-control"
                                   placeholder="2026"
                                   required>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">Status</label>
                            <select name="status" class="form-control" required>
                                <option value="1">Active</option>
                                <option value="0">Inactive</option>
                            </select>
                        </div>

                    </div>
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Save Class</button>
                </div>

            </form>

        </div>
    </div>
</div>

@endsection
