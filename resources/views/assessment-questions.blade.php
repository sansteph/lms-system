@extends('layouts.app')

@section('content')

<div class="container-fluid">
    <div class="row">

        @include('layouts.sidebar')

        <div class="col-md-10 col-lg-10 p-4">

            <div class="page-header mb-4">
                <h2 class="mb-1">Create Question Paper</h2>
                <p class="text-muted mb-0">
                    Add topics and create MCQ, short answer, or long answer questions.
                </p>
            </div>

            @if(session('success'))
                <div class="alert alert-success">{{ session('success') }}</div>
            @endif

            @if($errors->any())
                <div class="alert alert-danger">
                    {{ $errors->first() }}
                </div>
            @endif

            <div class="card shadow border-0 mb-4">
                <div class="card-body">

                    <h5 class="mb-4">Add Question</h5>

                    <form method="POST" action="{{ route('assessment-questions.store') }}">
                        @csrf

                        <div class="row g-3">

                            <div class="col-md-6">
                                <label class="form-label">Assessment</label>
                                <select name="assessment_id" class="form-control" required>
                                    <option value="">Select Assessment</option>
                                    @foreach($assessments as $assessment)
                                        <option value="{{ $assessment->id }}">
                                            {{ $assessment->assessment_title }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="col-md-6">
                                <label class="form-label">Topic</label>
                                <input type="text"
                                       name="topic"
                                       class="form-control"
                                       placeholder="Example: Arduino, Sensors, Robotics"
                                       required>
                            </div>

                            <div class="col-md-6">
                                <label class="form-label">Question Type</label>
                                <select name="question_type"
                                        class="form-control question-type-select"
                                        data-target="create"
                                        required>
                                    <option value="MCQ">MCQ</option>
                                    <option value="Short Answer">Short Answer</option>
                                    <option value="Long Answer">Long Answer</option>
                                </select>
                            </div>

                            <div class="col-md-6">
                                <label class="form-label">Marks</label>
                                <input type="number"
                                       name="marks"
                                       class="form-control"
                                       value="1"
                                       min="1"
                                       required>
                            </div>

                            <div class="col-12">
                                <label class="form-label">Question</label>
                                <textarea name="question"
                                          class="form-control"
                                          rows="3"
                                          required></textarea>
                            </div>

                            <div class="question-fields-create col-12">

                                <div class="mcq-fields-create row g-3">

                                    <div class="col-md-6">
                                        <label class="form-label">Option A</label>
                                        <input type="text" name="option_a" class="form-control">
                                    </div>

                                    <div class="col-md-6">
                                        <label class="form-label">Option B</label>
                                        <input type="text" name="option_b" class="form-control">
                                    </div>

                                    <div class="col-md-6">
                                        <label class="form-label">Option C</label>
                                        <input type="text" name="option_c" class="form-control">
                                    </div>

                                    <div class="col-md-6">
                                        <label class="form-label">Option D</label>
                                        <input type="text" name="option_d" class="form-control">
                                    </div>

                                    <div class="col-md-6">
                                        <label class="form-label">Correct Answer</label>
                                        <select name="correct_answer" class="form-control">
                                            <option value="">Select Correct Answer</option>
                                            <option value="A">Option A</option>
                                            <option value="B">Option B</option>
                                            <option value="C">Option C</option>
                                            <option value="D">Option D</option>
                                        </select>
                                    </div>

                                </div>

                                <div class="short-answer-fields-create d-none">
                                    <label class="form-label">Expected Short Answer</label>
                                    <textarea name="short_answer"
                                              class="form-control"
                                              rows="3"></textarea>
                                </div>

                                <div class="long-answer-fields-create d-none">
                                    <label class="form-label">Model Long Answer</label>
                                    <textarea name="long_answer"
                                              class="form-control"
                                              rows="5"></textarea>
                                </div>

                            </div>

                            <div class="col-12">
                                <label class="form-label">Explanation</label>
                                <textarea name="explanation"
                                          class="form-control"
                                          rows="3"
                                          placeholder="Optional explanation shown after assessment review"></textarea>
                            </div>

                        </div>

                        <div class="mt-4">
                            <button type="submit" class="btn btn-primary">
                                Save Question
                            </button>
                        </div>

                    </form>

                </div>
            </div>

            <div class="card shadow border-0">
                <div class="card-body">

                    <h5 class="mb-4">Question List</h5>

                    <table class="table table-bordered table-hover align-middle">
                        <thead class="table-light">
                            <tr>
                                <th>Sl. No</th>
                                <th>Assessment</th>
                                <th>Topic</th>
                                <th>Type</th>
                                <th>Question</th>
                                <th>Answer</th>
                                <th>Marks</th>
                                <th width="180">Actions</th>
                            </tr>
                        </thead>

                        <tbody>
                            @forelse($questions as $index => $question)

                                <tr>
                                    <td>{{ $index + 1 }}</td>
                                    <td>{{ $question->assessment->assessment_title ?? $question->assessment_id }}</td>
                                    <td>{{ $question->topic ?? 'N/A' }}</td>
                                    <td>
                                        <span class="badge bg-info">
                                            {{ $question->question_type ?? 'MCQ' }}
                                        </span>
                                    </td>
                                    <td>{{ $question->question }}</td>
                                    <td>
                                        @if(($question->question_type ?? 'MCQ') == 'MCQ')
                                            <span class="badge bg-success">
                                                {{ $question->correct_answer }}
                                            </span>
                                        @elseif($question->question_type == 'Short Answer')
                                            {{ Str::limit($question->short_answer, 50) }}
                                        @else
                                            {{ Str::limit($question->long_answer, 50) }}
                                        @endif
                                    </td>
                                    <td>{{ $question->marks }}</td>
                                    <td>
                                        <button class="btn btn-sm btn-warning"
                                                data-bs-toggle="modal"
                                                data-bs-target="#editQuestionModal{{ $question->id }}">
                                            Edit
                                        </button>

                                        <form method="POST"
                                              action="{{ route('assessment-questions.delete', $question->id) }}"
                                              class="d-inline"
                                              onsubmit="return confirm('Are you sure you want to delete this question?')">
                                            @csrf
                                            @method('DELETE')

                                            <button type="submit" class="btn btn-sm btn-danger">
                                                Delete
                                            </button>
                                        </form>
                                    </td>
                                </tr>

                            @empty
                                <tr>
                                    <td colspan="8" class="text-center text-muted">
                                        No questions added yet.
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

@foreach($questions as $question)

<div class="modal fade" id="editQuestionModal{{ $question->id }}" tabindex="-1">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content">

            <form method="POST" action="{{ route('assessment-questions.update', $question->id) }}">
                @csrf
                @method('PUT')

                <div class="modal-header">
                    <h5 class="modal-title">Edit Question</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>

                <div class="modal-body">

                    <div class="row g-3">

                        <div class="col-md-6">
                            <label class="form-label">Assessment</label>
                            <select name="assessment_id" class="form-control" required>
                                @foreach($assessments as $assessment)
                                    <option value="{{ $assessment->id }}"
                                        {{ $question->assessment_id == $assessment->id ? 'selected' : '' }}>
                                        {{ $assessment->assessment_title }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">Topic</label>
                            <input type="text"
                                   name="topic"
                                   class="form-control"
                                   value="{{ $question->topic }}"
                                   required>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">Question Type</label>
                            <select name="question_type"
                                    class="form-control question-type-select"
                                    data-target="{{ $question->id }}"
                                    required>
                                <option value="MCQ" {{ ($question->question_type ?? 'MCQ') == 'MCQ' ? 'selected' : '' }}>
                                    MCQ
                                </option>
                                <option value="Short Answer" {{ $question->question_type == 'Short Answer' ? 'selected' : '' }}>
                                    Short Answer
                                </option>
                                <option value="Long Answer" {{ $question->question_type == 'Long Answer' ? 'selected' : '' }}>
                                    Long Answer
                                </option>
                            </select>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">Marks</label>
                            <input type="number"
                                   name="marks"
                                   class="form-control"
                                   value="{{ $question->marks }}"
                                   min="1"
                                   required>
                        </div>

                        <div class="col-12">
                            <label class="form-label">Question</label>
                            <textarea name="question"
                                      class="form-control"
                                      rows="3"
                                      required>{{ $question->question }}</textarea>
                        </div>

                        <div class="col-12">

                            <div class="mcq-fields-{{ $question->id }} row g-3">

                                <div class="col-md-6">
                                    <label class="form-label">Option A</label>
                                    <input type="text"
                                           name="option_a"
                                           class="form-control"
                                           value="{{ $question->option_a }}">
                                </div>

                                <div class="col-md-6">
                                    <label class="form-label">Option B</label>
                                    <input type="text"
                                           name="option_b"
                                           class="form-control"
                                           value="{{ $question->option_b }}">
                                </div>

                                <div class="col-md-6">
                                    <label class="form-label">Option C</label>
                                    <input type="text"
                                           name="option_c"
                                           class="form-control"
                                           value="{{ $question->option_c }}">
                                </div>

                                <div class="col-md-6">
                                    <label class="form-label">Option D</label>
                                    <input type="text"
                                           name="option_d"
                                           class="form-control"
                                           value="{{ $question->option_d }}">
                                </div>

                                <div class="col-md-6">
                                    <label class="form-label">Correct Answer</label>
                                    <select name="correct_answer" class="form-control">
                                        <option value="">Select Correct Answer</option>
                                        <option value="A" {{ $question->correct_answer == 'A' ? 'selected' : '' }}>Option A</option>
                                        <option value="B" {{ $question->correct_answer == 'B' ? 'selected' : '' }}>Option B</option>
                                        <option value="C" {{ $question->correct_answer == 'C' ? 'selected' : '' }}>Option C</option>
                                        <option value="D" {{ $question->correct_answer == 'D' ? 'selected' : '' }}>Option D</option>
                                    </select>
                                </div>

                            </div>

                            <div class="short-answer-fields-{{ $question->id }} d-none">
                                <label class="form-label">Expected Short Answer</label>
                                <textarea name="short_answer"
                                          class="form-control"
                                          rows="3">{{ $question->short_answer }}</textarea>
                            </div>

                            <div class="long-answer-fields-{{ $question->id }} d-none">
                                <label class="form-label">Model Long Answer</label>
                                <textarea name="long_answer"
                                          class="form-control"
                                          rows="5">{{ $question->long_answer }}</textarea>
                            </div>

                        </div>

                        <div class="col-12">
                            <label class="form-label">Explanation</label>
                            <textarea name="explanation"
                                      class="form-control"
                                      rows="3">{{ $question->explanation }}</textarea>
                        </div>

                    </div>

                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">
                        Cancel
                    </button>

                    <button type="submit" class="btn btn-success">
                        Update Question
                    </button>
                </div>

            </form>

        </div>
    </div>
</div>

@endforeach

<script>
document.addEventListener('DOMContentLoaded', function () {

    function toggleQuestionFields(target, type) {

        const mcqFields = document.querySelector('.mcq-fields-' + target);
        const shortFields = document.querySelector('.short-answer-fields-' + target);
        const longFields = document.querySelector('.long-answer-fields-' + target);

        if (!mcqFields || !shortFields || !longFields) {
            return;
        }

        mcqFields.classList.add('d-none');
        shortFields.classList.add('d-none');
        longFields.classList.add('d-none');

        if (type === 'MCQ') {
            mcqFields.classList.remove('d-none');
        }

        if (type === 'Short Answer') {
            shortFields.classList.remove('d-none');
        }

        if (type === 'Long Answer') {
            longFields.classList.remove('d-none');
        }
    }

    document.querySelectorAll('.question-type-select').forEach(function (select) {

        toggleQuestionFields(select.dataset.target, select.value);

        select.addEventListener('change', function () {
            toggleQuestionFields(select.dataset.target, select.value);
        });

    });

});
</script>

@endsection