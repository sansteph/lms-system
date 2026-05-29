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

            <div class="mb-3">

                <label>
                    Course Price (₹)
                </label>

                <input type="number"
                       step="0.01"
                       name="price"
                       class="form-control"
                       value="0"
                       required>

            </div>

            <div class="mb-3">

                <label>
                    Availability
                </label>

                <select name="availability_type"
                        class="form-select"
                        required>

                    <option value="Institute">
                        Institute Only
                    </option>

                    <option value="Independent">
                        Independent Learners Only
                    </option>

                    <option value="Both">
                        Both
                    </option>

                </select>

            </div>

            <div class="mb-3">

                <label>
                    Active Status
                </label>

                <select name="is_active"
                        class="form-select"
                        required>

                    <option value="1">
                        Active
                    </option>

                    <option value="0">
                        Inactive
                    </option>

                </select>

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

                    <th>Price</th>

                    <th>Availability</th>

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

                            ₹{{ number_format($course->price ?? 0, 2) }}

                        </td>

                        <td>

                            {{ $course->availability_type ?? 'Institute' }}

                        </td>

                        <td>

                            @if($course->is_active)

                                Active

                            @else

                                Inactive

                            @endif

                        </td>

                    </tr>

                @empty

                    <tr>

                        <td colspan="6"
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
