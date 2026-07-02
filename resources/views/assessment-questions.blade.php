@extends('layouts.app')

@section('content')

<div class="container-fluid">
    <div class="row">
        @include('layouts.teacher-sidebar')

        <div class="col-md-10 col-lg-10 p-4">
            <div class="card shadow border-0">
                <div class="card-body text-center py-5">
                    <h3 class="mb-3">Manual Question Builder Removed</h3>
                    <p class="text-muted mb-4">
                        Assessments now use uploaded question papers that require admin approval before students can access them.
                    </p>
                    <a href="{{ route('teacher.assessments') }}" class="btn btn-primary">
                        Go to Assessment Management
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

@endsection