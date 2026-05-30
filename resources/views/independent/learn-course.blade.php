@extends('layouts.app')

@section('content')

<div class="container py-5">

    @if(session('error'))

        <div class="alert alert-danger">

            {{ session('error') }}

        </div>

    @endif

    <div class="card shadow border-0 mb-4">

        <div class="card-body">

            <h2>
                {{ $course->course_title }}
            </h2>

            <p class="text-muted mb-0">

                {{ $course->description }}

            </p>

        </div>

    </div>

    <div class="card shadow border-0">

        <div class="card-body">

            <h4 class="mb-4">
                Course Lessons
            </h4>

            <div class="card shadow border-0 mb-4">
                <div class="card-body">

                    <div class="d-flex justify-content-between align-items-center mb-2">

                        <h5 class="mb-0">
                            Course Progress
                        </h5>

                        <strong>
                            {{ $progressPercentage }}%
                        </strong>

                    </div>

                    <div class="progress" style="height: 12px;">
                        <div class="progress-bar"
                            style="width: {{ $progressPercentage }}%;">
                        </div>
                    </div>

                    <p class="text-muted mt-2 mb-0">
                            {{ $completedLessons }} of {{ $totalLessons }} lessons completed.
                    </p>

                </div>

            </div>

            @if($enrollment->is_completed)

                <div class="alert alert-success">

                    <i class="fa fa-check-circle me-2"></i>

                    Course Completed Successfully

                    @if($enrollment->completed_at)

                        <br>

                        <small>
                            Completed on:
                            {{ \Carbon\Carbon::parse($enrollment->completed_at)->format('d M Y') }}
                        </small>

                    @endif

                </div>

            @endif
 

            @forelse($contents as $content)
                @php
                    $completed = \App\Models\LessonProgress::where('independent_learner_id', session('independent_learner_id'))
                        ->where('content_id', $content->id)
                        ->where('is_completed', true)
                        ->exists();
                @endphp

                <div class="border rounded p-3 mb-3">

                    <div class="d-flex justify-content-between align-items-center">

                        <div>

                            <h5 class="mb-1">

                                Lesson {{ $content->lesson_order }}

                            </h5>

                            <p class="mb-0">

                                {{ $content->content_title }}

                            </p>

                        </div>

                        <div>

                            <span class="badge bg-primary">

                                {{ $content->content_type }}

                            </span>

                        </div>

                    </div>

                    <hr>

                    @if($content->file_path)

                        <a href="{{ asset('storage/' . $content->file_path) }}"
                           target="_blank"
                           class="btn btn-sm btn-success">

                            View Lesson

                        </a>

                    @endif

                    @if($completed)

                        <span class="badge bg-success">
                            Completed
                        </span>

                    @else

                        <form method="POST"
                            action="{{ route('independent.lesson.complete', $content->id) }}"
                            class="d-inline">

                            @csrf

                            <button type="submit"
                                    class="btn btn-sm btn-primary">

                                Mark Complete

                            </button>

                        </form>

                    @endif

                </div>

                

            @empty

                <div class="alert alert-info">

                    No lessons available for this course yet.

                </div>

            @endforelse

        </div>

    </div>

</div>

@endsection