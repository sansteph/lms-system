@extends('layouts.app')

@section('content')

<div class="container-fluid">
    <div class="row">

        @include('layouts.sidebar')

        <div class="col-md-10 col-lg-10 p-4">

            <div class="page-header d-flex justify-content-between align-items-center flex-wrap gap-3 mb-4">
                <div>
                    <h2 class="mb-1">Student Management</h2>
                    <p class="text-muted mb-0">
                        Manage student records, sections, and status.
                    </p>
                </div>

                <div class="d-flex gap-2 flex-wrap">
                    <button class="btn btn-outline-success btn-sm">
                        Import Excel
                    </button>

                    <button class="btn btn-primary btn-sm"
                            data-bs-toggle="modal"
                            data-bs-target="#addStudentModal">
                        Add Student
                    </button>
                </div>
            </div>

            <div class="card shadow border-0">
                <div class="card-body">

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

                    <form method="GET" action="{{ route('students') }}" class="row mb-3">
                        <div class="col-md-4">
                            <input type="text"
                                name="search"
                                class="form-control"
                                placeholder="Search by name or ID"
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
                                <th>Student ID</th>
                                <th>Name</th>
                                <th>Institute</th>
                                <th>Class</th>
                                <th>Section</th>
                                <th>Contact</th>
                                <th>Status</th>
                                <th width="180">Actions</th>
                            </tr>
                        </thead>

                        <tbody>
                            @forelse($students as $index => $student)
                                <tr>
                                    <td>{{ $index + 1 }}</td>
                                    <td>{{ $student->student_id }}</td>
                                    <td>{{ $student->name }}</td>
                                    <td>{{ $student->institute }}</td>
                                    <td>{{ $student->class }}</td>
                                    <td>{{ $student->section }}</td>
                                    <td>{{ $student->contact }}</td>
                                    <td>
                                        @if($student->status)
                                            <span class="badge bg-success">Active</span>
                                        @else
                                            <span class="badge bg-danger">Inactive</span>
                                        @endif
                                    </td>
                                    <td>
                                        <button class="btn btn-sm btn-outline-warning"
                                                data-bs-toggle="modal"
                                                data-bs-target="#editStudentModal{{ $student->id }}">
                                            Edit
                                        </button>

                                        <a href="{{ route('students.delete', $student->id) }}"
                                           class="btn btn-sm btn-outline-danger"
                                           onclick="return confirm('Are you sure you want to delete this student?')">
                                            Delete
                                        </a>
                                    </td>
                                </tr>

                                <div class="modal fade" id="editStudentModal{{ $student->id }}" tabindex="-1">
                                    <div class="modal-dialog modal-lg modal-dialog-centered">
                                        <div class="modal-content">

                                            <form method="POST" action="{{ route('students.update', $student->id) }}">
                                                @csrf

                                                <div class="modal-header">
                                                    <h5 class="modal-title">Edit Student</h5>
                                                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                                </div>

                                                <div class="modal-body">
                                                    <div class="row g-3">
                                                        <div class="col-md-6">
                                                            <label class="form-label">Student ID</label>
                                                            <input type="text" name="student_id" class="form-control" value="{{ $student->student_id }}" required>
                                                        </div>

                                                        <div class="col-md-6">
                                                            <label class="form-label">Student Name</label>
                                                            <input type="text" name="name" class="form-control" value="{{ $student->name }}" required>
                                                        </div>

                                                        <div class="col-md-6">
                                                            <label class="form-label">Institute</label>
                                                            <input type="text" name="institute" class="form-control" value="{{ $student->institute }}" required>
                                                        </div>

                                                        <div class="col-md-3">
                                                            <label class="form-label">Class</label>
                                                            <input type="text" name="class" class="form-control" value="{{ $student->class }}" required>
                                                        </div>

                                                        <div class="col-md-3">
                                                            <label class="form-label">Section</label>
                                                            <input type="text" name="section" class="form-control" value="{{ $student->section }}" required>
                                                        </div>

                                                        <div class="col-md-6">
                                                            <label class="form-label">Contact</label>
                                                            <input type="text" name="contact" class="form-control" value="{{ $student->contact }}" required>
                                                        </div>
                                                        
                                                        <div class="col-md-6">
                                                            <label class="form-label">Password</label>
                                                            <input type="password" name="password" class="form-control" placeholder="Leave blank to keep existing password">
                                                        </div>

                                                        <div class="col-md-6">
                                                            <label class="form-label">Status</label>
                                                            <select name="status" class="form-control" required>
                                                                <option value="1" {{ $student->status == 1 ? 'selected' : '' }}>Active</option>
                                                                <option value="0" {{ $student->status == 0 ? 'selected' : '' }}>Inactive</option>
                                                            </select>
                                                        </div>
                                                    </div>
                                                </div>

                                                <div class="modal-footer">
                                                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                                                    <button type="submit" class="btn btn-success">Update Student</button>
                                                </div>
                                            </form>

                                        </div>
                                    </div>
                                </div>

                            @empty
                                <tr>
                                    <td colspan="9" class="text-center text-muted">
                                        No students found
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

<div class="modal fade" id="addStudentModal" tabindex="-1">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content">

            <form method="POST" action="{{ route('students.store') }}">
                @csrf

                <div class="modal-header">
                    <h5 class="modal-title">Add Student</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>

                <div class="modal-body">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">Student ID</label>
                            <input type="text" name="student_id" class="form-control" required>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">Student Name</label>
                            <input type="text" name="name" class="form-control" required>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">Institute</label>
                            <input type="text" name="institute" class="form-control" required>
                        </div>

                        <div class="col-md-3">
                            <label class="form-label">Class</label>
                            <input type="text" name="class" class="form-control" required>
                        </div>

                        <div class="col-md-3">
                            <label class="form-label">Section</label>
                            <input type="text" name="section" class="form-control" required>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">Contact</label>
                            <input type="text" name="contact" class="form-control" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Password</label>
                            <input type="password" name="password" class="form-control" placeholder="Enter Password" required>
                        </div>
                    </div>
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-success">Save Student</button>
                </div>
            </form>

        </div>
    </div>
</div>

@endsection