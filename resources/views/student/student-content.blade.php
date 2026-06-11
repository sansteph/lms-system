@php
    use App\Models\LessonProgress;
    use Illuminate\Support\Str;
@endphp

@extends('layouts.app')

@section('content')

<div class="container-fluid">

    <div class="row">

        @include('layouts.student-sidebar')

        <div class="col-md-10 col-lg-10 p-4">

            {{-- PAGE HEADER --}}

            <div class="d-flex justify-content-between align-items-center flex-wrap gap-3 mb-4">

                <div>

                    <h2 class="fw-bold mb-1">
                        Learning Content
                    </h2>

                    <p class="text-muted mb-0">
                        Access lessons, study materials, assignments, and uploaded resources.
                    </p>

                </div>

                <div class="text-end">

                    <h6 class="mb-1 text-muted">
                        Course Completion
                    </h6>

                    <h4 class="fw-bold text-primary mb-0">

                        {{ $progressPercentage }}%

                    </h4>

                </div>

            </div>


            {{-- PROGRESS CARD --}}

            <div class="card shadow border-0 mb-5">

                <div class="card-body p-4">

                    <div class="d-flex justify-content-between align-items-center mb-3">

                        <div>

                            <h5 class="mb-1">
                                Learning Progress
                            </h5>

                            <p class="text-muted mb-0">
                                Complete lessons in sequence to unlock the next module.
                            </p>

                        </div>

                        <div class="text-end">

                            <h5 class="fw-bold mb-0">

                                {{ $completedLessons }}/{{ $totalLessons }}

                            </h5>

                            <small class="text-muted">
                                Lessons Completed
                            </small>

                        </div>

                    </div>

                    <div class="progress"
                         style="height: 14px; border-radius: 10px;">

                        <div class="progress-bar"
                             role="progressbar"
                             style="width: {{ $progressPercentage }}%">

                        </div>

                    </div>

                </div>

            </div>


            {{-- CONTENT GRID --}}

            <div class="row g-4">

                @forelse($contents as $content)

                    @php

                        $previousLesson = $contents
                            ->where('lesson_order', $content->lesson_order - 1)
                            ->first();

                        $isLocked = false;

                        if ($previousLesson) {

                            $completedPrevious = LessonProgress::where(
                                'student_id',
                                session('student_id')
                            )
                            ->where('content_id', $previousLesson->id)
                            ->where('is_completed', true)
                            ->exists();

                            if (!$completedPrevious) {

                                $isLocked = true;
                            }
                        }

                        $completed = LessonProgress::where(
                            'student_id',
                            session('student_id')
                        )
                        ->where('content_id', $content->id)
                        ->where('is_completed', true)
                        ->exists();

                    @endphp

                    <div class="col-md-6 col-lg-4">

                        <div class="card shadow border-0 h-100">

                            <div class="card-body d-flex flex-column">

                                {{-- TOP SECTION --}}

                                <div class="d-flex justify-content-between align-items-start mb-3">

                                    <div>

                                        <small class="text-muted d-block mb-1">

                                            Lesson {{ $content->lesson_order }}

                                        </small>

                                        <h5 class="fw-bold mb-0">

                                            {{ $content->content_title }}

                                        </h5>

                                    </div>

                                    <div>

                                        @if($completed)

                                            <span class="badge bg-success">

                                                Completed

                                            </span>

                                        @elseif($isLocked)

                                            <span class="badge bg-danger">

                                                Locked

                                            </span>

                                        @else

                                            <span class="badge bg-primary">

                                                Available

                                            </span>

                                        @endif

                                    </div>

                                </div>


                                {{-- DESCRIPTION --}}

                                <p class="text-muted small mb-4">

                                    {{ Str::limit($content->description, 140) }}

                                </p>


                                {{-- FILE SECTION --}}

                                @if($content->file_path)

                                    <div class="mb-4">

                                        <small class="text-muted d-block mb-2">

                                            Attached Resource

                                        </small>

                                        <div class="border rounded p-3 bg-light">

                                            <div class="d-flex justify-content-between align-items-center">

                                                <div>

                                                    <i class="fa fa-file-alt text-primary me-2"></i>

                                                    <span class="small fw-semibold">

                                                        Learning Material

                                                    </span>

                                                </div>

                                                @if(!$isLocked)

                                                    <a href="{{ asset('storage/' . $content->file_path) }}"
                                                       target="_blank"
                                                       class="btn btn-sm btn-outline-primary">

                                                        View

                                                    </a>

                                                @endif

                                            </div>

                                        </div>

                                    </div>

                                @endif


                                {{-- ACTION BUTTONS --}}

                                <div class="mt-auto">

                                    @if($isLocked)

                                        <button class="btn btn-secondary w-100"
                                                disabled>

                                            <i class="fa fa-lock me-2"></i>

                                            Complete Previous Lesson First

                                        </button>

                                    @elseif($completed)

                                        <button class="btn btn-success w-100"
                                                disabled>

                                            <i class="fa fa-check-circle me-2"></i>

                                            Lesson Completed

                                        </button>

                                    @else

                                        <form action="{{ route('student.lesson.complete', $content->id) }}"
                                              method="POST">

                                            @csrf

                                            <button type="submit"
                                                    class="btn btn-primary w-100">

                                                <i class="fa fa-check me-2"></i>

                                                Mark as Complete

                                            </button>

                                        </form>

                                    @endif

                                </div>

                            </div>

                        </div>

                    </div>

                @empty

                    <div class="col-12">

                        <div class="card shadow border-0">

                            <div class="card-body text-center py-5">

                                <div class="mb-4">

                                    <i class="fa fa-book-open text-muted"
                                       style="font-size: 80px;"></i>

                                </div>

                                <h4 class="fw-bold">

                                    No Learning Content Available

                                </h4>

                                <p class="text-muted mb-0">

                                    Learning materials and lessons will appear here once uploaded by your STEM Engineer.

                                </p>

                            </div>

                        </div>

                    </div>

                @endforelse

            </div>

        </div>

    </div>

</div>

@endsection