@extends('layouts.app')

@section('content')

<div class="container-fluid">
    <div class="row">

        @include('layouts.student-sidebar')

        <div class="col-md-10 col-lg-10 p-4">

            <div class="page-header d-flex justify-content-between align-items-center flex-wrap gap-3 mb-4">
                <div>
                    <h2 class="mb-1">Take Assessment</h2>
                    <p class="text-muted mb-0">
                        Complete your assigned assessment and submit your answers.
                    </p>
                </div>

                <span class="badge bg-warning text-dark p-2">
                    Time Left: 45 mins
                </span>
            </div>

            <div class="card shadow border-0 mb-4">
                <div class="card-body">
                    <h5 class="mb-2">AI Fundamentals Test</h5>
                    <p class="text-muted mb-0">
                        Total Marks: 50 | Duration: 45 mins | Questions: 5
                    </p>
                </div>
            </div>

            <form>
                <div class="card shadow border-0 mb-3">
                    <div class="card-body">
                        <h6>1. What does AI stand for?</h6>

                        <div class="form-check mt-3">
                            <input class="form-check-input" type="radio" name="q1">
                            <label class="form-check-label">Artificial Intelligence</label>
                        </div>

                        <div class="form-check">
                            <input class="form-check-input" type="radio" name="q1">
                            <label class="form-check-label">Automatic Internet</label>
                        </div>

                        <div class="form-check">
                            <input class="form-check-input" type="radio" name="q1">
                            <label class="form-check-label">Advanced Input</label>
                        </div>
                    </div>
                </div>

                <div class="card shadow border-0 mb-3">
                    <div class="card-body">
                        <h6>2. Which of the following is an example of AI?</h6>

                        <div class="form-check mt-3">
                            <input class="form-check-input" type="radio" name="q2">
                            <label class="form-check-label">Voice Assistant</label>
                        </div>

                        <div class="form-check">
                            <input class="form-check-input" type="radio" name="q2">
                            <label class="form-check-label">Wooden Chair</label>
                        </div>

                        <div class="form-check">
                            <input class="form-check-input" type="radio" name="q2">
                            <label class="form-check-label">Plain Notebook</label>
                        </div>
                    </div>
                </div>

                <div class="card shadow border-0 mb-3">
                    <div class="card-body">
                        <h6>3. Write one use of AI in daily life.</h6>

                        <textarea class="form-control mt-3" rows="4" placeholder="Write your answer here"></textarea>
                    </div>
                </div>

                <div class="d-flex justify-content-end gap-2">
                    <button type="button" class="btn btn-light">
                        Save Draft
                    </button>

                    <button type="submit" class="btn btn-success">
                        Submit Assessment
                    </button>
                </div>
            </form>

        </div>

    </div>
</div>

@endsection