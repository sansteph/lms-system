@extends('layouts.app')

@section('content')

<div class="container-fluid">

    <div class="row">

        @include('layouts.sidebar')

        <div class="col-md-10 col-lg-10 p-4">

            <div class="page-header mb-4">

                <h2>
                    Learner Details
                </h2>

                <p class="text-muted mb-0">

                    Complete learner profile and activity.

                </p>

            </div>

            <div class="row g-4">

                <div class="col-lg-4">

                    <div class="card shadow border-0">

                        <div class="card-body">

                            <h4>
                                {{ $learner->name }}
                            </h4>

                            <p class="mb-1">
                                <strong>Email:</strong>
                                {{ $learner->email }}
                            </p>

                            <p class="mb-1">
                                <strong>Phone:</strong>
                                {{ $learner->phone ?? 'N/A' }}
                            </p>

                            <p class="mb-1">
                                <strong>Joined:</strong>
                                {{ $learner->created_at->format('d M Y') }}
                            </p>

                            <p class="mb-0">
                                <strong>Status:</strong>

                                @if($learner->status)

                                    <span class="badge bg-success">
                                        Active
                                    </span>

                                @else

                                    <span class="badge bg-danger">
                                        Inactive
                                    </span>

                                @endif

                            </p>

                        </div>

                    </div>

                </div>

                <div class="col-lg-8">

                    <div class="card shadow border-0">

                        <div class="card-body">

                            <h5 class="mb-3">
                                Course Enrollments
                            </h5>

                            <table class="table table-bordered">

                                <thead>

                                    <tr>

                                        <th>Course</th>

                                        <th>Payment Status</th>

                                        <th>Completion</th>

                                    </tr>

                                </thead>

                                <tbody>

                                    @forelse($learner->enrollments as $enrollment)

                                        <tr>

                                            <td>
                                                {{ $enrollment->course->course_title ?? 'Course Deleted' }}
                                            </td>

                                            <td>
                                                {{ $enrollment->payment_status }}
                                            </td>

                                            <td>

                                                @if($enrollment->is_completed)

                                                    <span class="badge bg-success">
                                                        Completed
                                                    </span>

                                                @else

                                                    <span class="badge bg-warning text-dark">
                                                        In Progress
                                                    </span>

                                                @endif

                                            </td>

                                        </tr>

                                    @empty

                                        <tr>

                                            <td colspan="3" class="text-center">

                                                No enrollments found.

                                            </td>

                                        </tr>

                                    @endforelse

                                </tbody>

                            </table>

                        </div>

                    </div>

                    <div class="card shadow border-0 mt-4">

                        <div class="card-body">

                            <h5 class="mb-3">
                                Certificates
                            </h5>

                            <table class="table table-bordered">

                                <thead>

                                    <tr>

                                        <th>Certificate Code</th>

                                        <th>Course</th>

                                        <th>Status</th>

                                    </tr>

                                </thead>

                                <tbody>

                                    @forelse($learner->certificates as $certificate)

                                        <tr>

                                            <td>
                                                {{ $certificate->certificate_code }}
                                            </td>

                                            <td>
                                                {{ $certificate->course->course_title ?? 'Course Deleted' }}
                                            </td>

                                            <td>
                                                {{ $certificate->status }}
                                            </td>

                                        </tr>

                                    @empty

                                        <tr>

                                            <td colspan="3" class="text-center">

                                                No certificates issued.

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

</div>

@endsection