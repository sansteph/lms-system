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
                        View student details by class and section.
                    </p>

                </div>

                <a href="{{ route('teacher.student.profiles.export', request()->query()) }}"
                    class="btn btn-success">

                    <i class="fa fa-file-excel me-2"></i>

                    Export Details

                </a>

            </div>

            <div class="card shadow-sm border-0 p-4">
                @include('partials.section-navigator', [
                    'sectionPager' => $classSectionPager ?? null,
                    'sectionDescription' => 'Browse student details one class at a time.',
                ])

                @include('partials.section-navigator', [
                    'sectionPager' => $studentSectionPager ?? null,
                    'sectionDescription' => 'Showing student details for this section only.',
                ])

                @if(!empty($selectedStudentClass))
                    <div class="alert alert-light border d-flex justify-content-between align-items-center flex-wrap gap-2">
                        <div>
                            <span class="fw-semibold">Current student scope:</span>
                            Class {{ $selectedStudentClass }}
                            @if(!empty($selectedStudentSection))
                                &middot; Section {{ $selectedStudentSection }}
                            @endif
                        </div>
                        <span class="badge bg-primary">
                            {{ $students->count() }} student{{ $students->count() == 1 ? '' : 's' }}
                        </span>
                    </div>
                @endif

                <div class="table-responsive lms-table-shell">

                    <table class="table align-middle lms-table-fit">

                        <thead>

                            <tr>
                                <th>Name</th>
                                <th>Email</th>
                                <th>Class</th>
                                <th>Institute</th>
                                <th>Guardian</th>
                                <th>Contact</th>
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
                                        {{ $student->contact }}
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
