@extends('layouts.app')

@section('content')

<div class="container py-4">

    <h2 class="mb-4">
        Courses
    </h2>

    @if(session('success'))

        <div class="alert alert-success">

            {{ session('success') }}

        </div>

    @endif

    <div class="card mb-4">

        <div class="card-body">

            <form action="{{ route('courses.store') }}"
                  method="POST">

                @csrf

                <div class="mb-3">

                    <label>
                        Course Title
                    </label>

                    <input type="text"
                           name="course_title"
                           class="form-control"
                           required>

                </div>

                <div class="mb-3">

                    <label>
                        Description
                    </label>

                    <textarea name="description"
                              class="form-control"></textarea>

                </div>

                <div class="mb-3">

                    <label>
                        Target
                    </label>

                    <select name="target"
                            class="form-select">

                        <option value="Student">
                            Student
                        </option>

                        <option value="Teacher">
                            Teacher
                        </option>

                        <option value="Both">
                            Both
                        </option>

                    </select>

                </div>

                <div class="mb-3">

                    <label>
                        Assigned Class
                    </label>

                    <input type="text"
                           name="assigned_class"
                           class="form-control">

                </div>

                <button type="submit"
                        class="btn btn-primary">

                    Create Course

                </button>

            </form>

        </div>

    </div>

    <div class="card">

        <div class="card-body">

            <table class="table table-bordered">

                <thead>

                    <tr>

                        <th>Course</th>

                        <th>Target</th>

                        <th>Class</th>

                        <th>Status</th>

                    </tr>

                </thead>

                <tbody>

                    @forelse($courses as $course)

                        <tr>

                            <td>

                                {{ $course->course_title }}

                            </td>

                            <td>

                                {{ $course->target }}

                            </td>

                            <td>

                                {{ $course->assigned_class }}

                            </td>

                            <td>

                                @if($course->status)

                                    Active

                                @else

                                    Inactive

                                @endif

                            </td>

                        </tr>

                    @empty

                        <tr>

                            <td colspan="4"
                                class="text-center">

                                No courses created

                            </td>

                        </tr>

                    @endforelse

                </tbody>

            </table>

        </div>

    </div>

</div>

@endsection