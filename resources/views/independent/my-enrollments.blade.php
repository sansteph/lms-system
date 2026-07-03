@extends('layouts.app')

@section('content')

<div class="container py-5">

    <div class="card shadow border-0">

        <div class="card-body">

            <h2 class="mb-4">
                My Enrollments
            </h2>

            <table class="table table-bordered">

                <thead>
                    <tr>
                        <th>Course</th>
                        <th>Enrolled On</th>
                        <th>Payment Status</th>
                        <th>Completion</th>
                        <th>Actions</th>
                    </tr>
                </thead>

                <tbody>

                    @forelse($enrollments as $enrollment)

                        <tr>

                            <td>
                                {{ $enrollment->course->course_title ?? 'Course Deleted' }}
                            </td>

                            <td>
                                {{ $enrollment->enrolled_at }}
                            </td>

                            <td>
                                <span class="badge bg-warning text-dark">
                                    @if($enrollment->is_completed)

                                        <span class="badge bg-success">
                                            Completed
                                        </span>

                                    @else

                                        <span class="badge bg-warning text-dark">
                                            {{ $enrollment->payment_status }}
                                        </span>

                                    @endif
                                </span>
                            </td>

                            <td>
                                @if($enrollment->is_completed)

                                    <span class="badge bg-success">
                                        Completed
                                    </span>

                                @else

                                    <span class="badge bg-secondary">
                                        In Progress
                                    </span>

                                @endif
                            </td>

                            <td>

                                <a href="{{ route('independent.courses.learn', $enrollment->course_id) }}"
                                class="btn btn-sm btn-primary">

                                    Learn

                                </a>

                            </td>

                        </tr>

                    @empty

                        <tr>
                            <td colspan="5" class="text-center">
                                No enrollments found.
                            </td>
                        </tr>

                    @endforelse

                </tbody>

            </table>

        </div>

    </div>

</div>

@endsection
