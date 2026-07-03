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

                <a href="{{ route('teacher.student.profiles.export') }}"
                    class="btn btn-success">

                    <i class="fa fa-file-excel me-2"></i>

                    Export Details

                </a>

            </div>

            <div class="card shadow-sm border-0 p-4">

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

                            @forelse($students as $student)

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
