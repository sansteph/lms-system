@extends('layouts.app')

@section('content')

<div class="container-fluid">
    <div class="row">

        @include('layouts.teacher-sidebar')

        <main class="col-md-10 col-lg-10 p-4">

            <div class="page-header mb-4 d-flex justify-content-between align-items-center flex-wrap gap-3">

                <div>

                    <h2>Student Details</h2>

                    <p>
                        View student details collected during first login.
                    </p>

                </div>

                <a href="{{ route('teacher.student.profiles.export', request()->query()) }}"
                    class="btn btn-success">

                    <i class="fa fa-file-excel me-2"></i>

                    Export Details

                </a>

            </div>

            <div class="card shadow-sm border-0 p-4">
                <form method="GET" action="{{ route('teacher.student.profiles') }}" class="row g-3 align-items-end mb-4">
                    <div class="col-md-4">
                        <label class="form-label">Class</label>
                        <select name="class" class="form-control">
                            <option value="">All Classes</option>
                            @foreach($classOptions as $classOption)
                                <option value="{{ $classOption }}" {{ $selectedClass == $classOption ? 'selected' : '' }}>
                                    {{ $classOption }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-2">
                        <button type="submit" class="btn btn-primary w-100">Apply</button>
                    </div>
                    <div class="col-md-2">
                        <a href="{{ route('teacher.student.profiles') }}" class="btn btn-outline-secondary w-100">Clear</a>
                    </div>
                </form>

                <div class="table-responsive">

                    <table class="table align-middle">

                        <thead>

                            <tr>
                                <th>Name</th>
                                <th>Email</th>
                                <th>Class</th>
                                <th>Institute</th>
                                <th>Guardian</th>
                                <th>Guardian Contact</th>
                                <th>Robotics Club</th>
                            </tr>

                        </thead>

                        <tbody>

                            @forelse($students->groupBy(fn ($student) => trim($student->class . ' ' . $student->section) ?: 'Unassigned Class') as $classLabel => $classStudents)
                                <tr class="table-primary">
                                    <td colspan="7" class="fw-semibold">{{ $classLabel }}</td>
                                </tr>
                                @foreach($classStudents as $student)

                                <tr>

                                    <td>
                                        {{ $student->name }}
                                    </td>

                                    <td>
                                        {{ $student->email }}
                                    </td>

                                    <td>
                                        {{ $student->class }}
                                    </td>

                                    <td>
                                        {{ $student->institute }}
                                    </td>

                                    <td>
                                        {{ $student->guardian_name }}
                                    </td>

                                    <td>
                                        {{ $student->guardian_contact }}
                                    </td>

                                    <td>

                                        @if($student->is_robotics_club_member)

                                            <span class="badge bg-success">
                                                Yes
                                            </span>

                                        @else

                                            <span class="badge bg-secondary">
                                                No
                                            </span>

                                        @endif

                                    </td>

                                </tr>
                                @endforeach

                            @empty

                                <tr>

                                    <td colspan="7" class="text-center text-muted py-4">

                                        No student profiles found.

                                    </td>

                                </tr>

                            @endforelse

                        </tbody>

                    </table>

                </div>

            </div>

        </main>

    </div>
</div>

@endsection
