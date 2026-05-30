@extends('layouts.app')

@section('content')

<div class="container py-5">

    <div class="card shadow border-0">

        <div class="card-body p-5">

            <a href="{{ route('independent.courses') }}"
               class="btn btn-sm btn-outline-secondary mb-4">
                <i class="fa fa-arrow-left me-2"></i>
                Back to Courses
            </a>

            <h2>
                {{ $course->course_title }}
            </h2>

            <p class="text-muted mt-3">
                {{ $course->description }}
            </p>

            <hr>

            <div class="row g-4">

                <div class="col-md-4">
                    <div class="profile-info-item">
                        <span class="profile-label">Price</span>
                        <h6>₹{{ number_format($course->price, 2) }}</h6>
                    </div>
                </div>

                <div class="col-md-4">
                    <div class="profile-info-item">
                        <span class="profile-label">Availability</span>
                        <h6>{{ $course->availability_type }}</h6>
                    </div>
                </div>

                <div class="col-md-4">
                    <div class="profile-info-item">
                        <span class="profile-label">Status</span>
                        <h6>{{ $course->is_active ? 'Active' : 'Inactive' }}</h6>
                    </div>
                </div>

            </div>

            <div class="mt-4">

                @if(session('independent_learner_id'))

                    <form method="POST"
                        action="{{ route('independent.courses.enroll', $course->id) }}">

                        @csrf

                        <button type="submit"
                                class="btn btn-primary">

                            Enroll Now

                        </button>

                    </form>

                @else

                    <a href="{{ route('independent.login') }}"
                    class="btn btn-primary">

                        Login to Enroll

                    </a>

                @endif

            </div>

        </div>

    </div>

</div>

@endsection