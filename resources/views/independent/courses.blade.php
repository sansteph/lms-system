@extends('layouts.app')

@section('content')

<div class="container py-5">

    <div class="page-header mb-4">
        <h2>Explore Courses</h2>
        <p class="text-muted mb-0">
            Browse premium hybrid learning courses.
        </p>
    </div>

    <div class="row g-4">

        @forelse($courses as $course)

            <div class="col-md-4">

                <div class="card shadow border-0 h-100">

                    <div class="card-body p-4">

                        <h4>{{ $course->course_title }}</h4>

                        <p class="text-muted">
                            {{ Str::limit($course->description, 100) }}
                        </p>

                        <h5 class="text-primary">
                            ₹{{ number_format($course->price, 2) }}
                        </h5>

                        <a href="{{ route('independent.courses.show', $course->id) }}"
                           class="btn btn-primary mt-3">
                            View Course
                        </a>

                    </div>

                </div>

            </div>

        @empty

            <div class="col-12">
                <div class="alert alert-info">
                    No independent courses available yet.
                </div>
            </div>

        @endforelse

    </div>

</div>

@endsection