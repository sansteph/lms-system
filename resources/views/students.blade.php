@extends('layouts.app')

@section('content')

<div class="container-fluid">
    <div class="row">

        @include('layouts.sidebar')

        <div class="col-md-9 col-lg-10 p-4">

            <div class="d-flex justify-content-between align-items-center mb-4">
                <h2>Student Management</h2>

                <div>
                    <button class="btn btn-success me-2">Import Excel</button>
                    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addStudentModal">
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

                    <div class="mb-3">
                        <input type="text" class="form-control" placeholder="Search student...">
                    </div>

                    <table class="table table-bordered table-hover align-middle">
                        <thead class="table-dark">
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
                                        <button class="btn btn-sm btn-warning"
                                                data-bs-toggle="modal"
                                                data-bs-target="#editStudentModal{{ $student->id }}">
                                            Edit
                                        </button>

                                        <a href="{{ route('students.delete', $student->id) }}"
                                           class="btn btn-sm btn-danger"
                                           onclick="return confirm('Are you sure you want to delete this student?')">
                                            Delete
                                        </a>
                                    </td>
                                </tr>

                                <!-- Edit Student Modal -->
                                <div class="modal fade" id="editStudentModal{{ $student->id }}" tabindex="-1">
                                    <div class="modal-dialog">
                                        <div class="modal-content">

                                            <form method="POST" action="{{ route('students.update', $student->id) }}">
                                                @csrf

                                                <div class="modal-header">
                                                    <h5 class="modal-title">Edit Student</h5>
                                                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                                </div>

                                                <div class="modal-body">
                                                    <input type="text" name="student_id" class="form-control mb-2" value="{{ $student->student_id }}" required>
                                                    <input type="text" name="name" class="form-control mb-2" value="{{ $student->name }}" required>
                                                    <input type="text" name="institute" class="form-control mb-2" value="{{ $student->institute }}" required>
                                                    <input type="text" name="class" class="form-control mb-2" value="{{ $student->class }}" required>
                                                    <input type="text" name="section" class="form-control mb-2" value="{{ $student->section }}" required>
                                                    <input type="text" name="contact" class="form-control mb-2" value="{{ $student->contact }}" required>

                                                    <select name="status" class="form-control mb-2">
                                                        <option value="1" {{ $student->status == 1 ? 'selected' : '' }}>Active</option>
                                                        <option value="0" {{ $student->status == 0 ? 'selected' : '' }}>Inactive</option>
                                                    </select>
                                                </div>

                                                <div class="modal-footer">
                                                    <button type="submit" class="btn btn-success">
                                                        Update Student
                                                    </button>
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

<!-- Add Student Modal -->
<div class="modal fade" id="addStudentModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">

            <form method="POST" action="{{ route('students.store') }}">
                @csrf

                <div class="modal-header">
                    <h5 class="modal-title">Add Student</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>

                <div class="modal-body">
                    <input type="text" name="student_id" class="form-control mb-2" placeholder="Student ID" required>
                    <input type="text" name="name" class="form-control mb-2" placeholder="Name" required>
                    <input type="text" name="institute" class="form-control mb-2" placeholder="Institute" required>
                    <input type="text" name="class" class="form-control mb-2" placeholder="Class" required>
                    <input type="text" name="section" class="form-control mb-2" placeholder="Section" required>
                    <input type="text" name="contact" class="form-control mb-2" placeholder="Contact" required>
                </div>

                <div class="modal-footer">
                    <button type="submit" class="btn btn-success">
                        Save Student
                    </button>
                </div>
            </form>

        </div>
    </div>
</div>

@endsection