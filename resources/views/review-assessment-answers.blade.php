@extends('layouts.app')

@section('content')

<div class="container-fluid">
    <div class="row">

        @include('layouts.teacher-sidebar')

        <div class="col-md-10 col-lg-10 p-4">

            <h2 class="mb-4">Assessment Evaluation</h2>

            @if(session('success'))
                <div class="alert alert-success">
                    {{ session('success') }}
                </div>
            @endif

            <div class="card shadow border-0">
                <div class="card-body">

                    <table class="table table-bordered align-middle">
                        <thead>
                            <tr>
                                <th>Student</th>
                                <th>Assessment</th>
                                <th>Question</th>
                                <th>Submitted Answer</th>
                                <th>Max Marks</th>
                                <th>Award Marks</th>
                            </tr>
                        </thead>

                        <tbody>
                            @forelse($answers as $answer)
                                <tr>
                                    <td>{{ $answer->student->name ?? 'Student Deleted' }}</td>
                                    <td>{{ $answer->assessment->assessment_title ?? 'Assessment Deleted' }}</td>
                                    <td>{{ $answer->question->question ?? 'Question Deleted' }}</td>
                                    <td>{{ $answer->submitted_answer }}</td>
                                    <td>{{ $answer->question->marks ?? 0 }}</td>
                                    <td>
                                        <form method="POST"
                                              action="{{ route('assessment.review.submit', $answer->id) }}">
                                            @csrf

                                            <input type="number"
                                                   name="marks_awarded"
                                                   class="form-control mb-2"
                                                   min="0"
                                                   max="{{ $answer->question->marks ?? 0 }}"
                                                   required>

                                            <button type="submit"
                                                    class="btn btn-sm btn-success">
                                                Submit
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="text-center text-muted">
                                        No pending answers for review.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>

                </div>
            </div>

        </div>

    </div>
</div>

@endsection