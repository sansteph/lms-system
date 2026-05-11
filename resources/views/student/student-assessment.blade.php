@extends('layouts.app')

@section('content')

<div class="d-flex justify-content-center align-items-center" style="height: 80vh;">

    <div class="card p-4 shadow" style="width: 400px;">

        <h3 class="text-center mb-3">
            Student Assessment Verification
        </h3>

        <p class="text-muted text-center mb-4">
            Enter your details to access the assessment.
        </p>

        <form method="GET" action="{{ route('student.assessment') }}">

            <div class="mb-3">
                <label class="form-label">Institute ID</label>

                <input type="text"
                       class="form-control"
                       name="institute_id"
                       placeholder="Enter Institute ID"
                       required>
            </div>

            <div class="mb-3">
                <label class="form-label">Student ID</label>

                <input type="text"
                       class="form-control"
                       name="student_id"
                       placeholder="Enter Student ID"
                       required>
            </div>

            <div class="mb-4">
                <label class="form-label">Assessment ID</label>

                <input type="text"
                       class="form-control"
                       name="assessment_id"
                       placeholder="Enter Assessment ID"
                       required>
            </div>

            <button type="submit"
                    class="btn btn-success w-100">

                Start Assessment

            </button>

        </form>

    </div>

</div>

@endsection