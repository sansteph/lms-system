@extends('layouts.app')

@section('content')

<div class="container py-5">

    <div class="row g-4">

        <div class="col-12">

            <div class="card shadow border-0">

                <div class="card-body p-5">

                    <h2>
                        Welcome,
                        {{ session('independent_learner_name') }}
                    </h2>

                    <p class="text-muted mb-0">
                        Start learning by exploring our premium courses.
                    </p>

                </div>

            </div>

        </div>

        <div class="col-md-4">

            <div class="card shadow border-0">

                <div class="card-body text-center p-4">

                    <i class="fa fa-book-open fa-3x text-primary mb-3"></i>

                    <h5>
                        Explore Courses
                    </h5>

                    <p class="text-muted">
                        Browse hybrid learning programs.
                    </p>

                    <a href="{{ route('independent.courses') }}"
                       class="btn btn-primary">

                        View Courses

                    </a>

                </div>

            </div>

        </div>

        <div class="col-md-4">

            <div class="card shadow border-0">

                <div class="card-body text-center p-4">

                    <i class="fa fa-graduation-cap fa-3x text-success mb-3"></i>

                    <h5>
                        My Enrollments
                    </h5>

                    <p class="text-muted">
                        Track courses you've enrolled in.
                    </p>

                    @if($enrollments->count() > 0)

                        <ul class="list-group mt-3 text-start">

                            @foreach($enrollments as $enrollment)

                                <li class="list-group-item d-flex justify-content-between align-items-center">

                                    <a href="{{ route('independent.courses.learn', $enrollment->course_id) }}"
                                    class="text-decoration-none fw-semibold">

                                        {{ $enrollment->course->course_title ?? 'Course Deleted' }}

                                    </a>

                                    <span class="badge bg-warning text-dark">
                                        {{ $enrollment->payment_status }}
                                    </span>

                                </li>

                            @endforeach

                        </ul>

                    @else

                        <p class="text-muted">
                            You have not enrolled in any courses yet.
                        </p>

                    @endif

                    

                </div>

            </div>

        </div>

        <div class="col-md-4">

            <div class="card shadow border-0">

                <div class="card-body text-center p-4">

                    <i class="fa fa-certificate fa-3x text-warning mb-3"></i>

                    <h5>
                        My Certificates
                    </h5>

                    <p class="text-muted">
                        View certificates earned through learning.
                    </p>

                    <a href="{{ route('independent.certificates') }}"
                    class="btn btn-warning text-white">

                        View Certificates

                    </a>

                </div>

            </div>

        </div>

    </div>

</div>

@endsection