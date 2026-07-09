@extends('layouts.app')

@section('content')

<div class="container-fluid">

    <div class="row">

        @include('layouts.student-sidebar')

        <div class="col-md-10 col-lg-10 p-4">

            <div class="d-flex justify-content-between align-items-center flex-wrap gap-3 mb-4">

                <div>

                    <h3 class="fw-bold mb-1">
                        Edit Achievement
                    </h3>

                    <p class="text-muted mb-0">
                        Update your achievement details and resubmit it for review.
                    </p>

                </div>

                <a href="{{ route('student.badges') }}"
                   class="btn btn-outline-secondary">

                    <i class="fa fa-arrow-left me-2"></i>

                    Back

                </a>

            </div>

            <div class="card shadow border-0">

                <div class="card-body p-4">

                    @if ($errors->any())

                        <div class="alert alert-danger">

                            <ul class="mb-0">

                                @foreach ($errors->all() as $error)

                                    <li>{{ $error }}</li>

                                @endforeach

                            </ul>

                        </div>

                    @endif

                    <form action="{{ route('student.achievements.update', $achievement->id) }}"
                          method="POST"
                          enctype="multipart/form-data">

                        @csrf

                        <div class="row g-4">

                            <div class="col-md-6">

                                <label class="form-label fw-semibold">
                                    Achievement Type
                                </label>

                                <select name="achievement_type"
                                        class="form-select"
                                        required>

                                    <option value="">
                                        Select Type
                                    </option>

                                    @foreach(['Competition', 'Exhibition', 'Workshop', 'Certification', 'Course'] as $type)
                                        <option value="{{ $type }}"
                                            {{ old('achievement_type', $achievement->achievement_type) == $type ? 'selected' : '' }}>
                                            {{ $type == 'Course' ? 'Extra Course' : $type }}
                                        </option>
                                    @endforeach

                                </select>

                            </div>

                            <div class="col-md-6">

                                <label class="form-label fw-semibold">
                                    Achievement Date
                                </label>

                                <input type="date"
                                       name="achievement_date"
                                       class="form-control"
                                       value="{{ old('achievement_date', $achievement->achievement_date) }}">

                            </div>

                            <div class="col-md-12">

                                <label class="form-label fw-semibold">
                                    Title
                                </label>

                                <input type="text"
                                       name="title"
                                       class="form-control"
                                       value="{{ old('title', $achievement->title) }}"
                                       required>

                            </div>

                            <div class="col-md-6">

                                <label class="form-label fw-semibold">
                                    Organizer
                                </label>

                                <input type="text"
                                       name="organizer"
                                       class="form-control"
                                       value="{{ old('organizer', $achievement->organizer) }}">

                            </div>

                            <div class="col-md-6">

                                <label class="form-label fw-semibold">
                                    Position / Result
                                </label>

                                <input type="text"
                                       name="position"
                                       class="form-control"
                                       value="{{ old('position', $achievement->position) }}">

                            </div>

                            <div class="col-md-12">

                                <label class="form-label fw-semibold">
                                    Description
                                </label>

                                <textarea name="description"
                                          class="form-control"
                                          rows="5">{{ old('description', $achievement->description) }}</textarea>

                            </div>

                            <div class="col-md-12">

                                <label class="form-label fw-semibold">
                                    Replace Certificate
                                </label>

                                <input type="file"
                                       name="certificate_file"
                                       class="form-control"
                                       accept=".pdf,.jpg,.jpeg,.png">

                                <small class="text-muted">
                                    Leave empty to keep the current file. Accepted formats: PDF, JPG, JPEG, PNG (Max: 5MB)
                                </small>

                            </div>

                        </div>

                        <div class="d-flex justify-content-end gap-3 mt-4">

                            <a href="{{ route('student.badges') }}"
                               class="btn btn-outline-secondary">
                                Cancel
                            </a>

                            <button type="submit"
                                    class="btn btn-primary">

                                Update Achievement

                            </button>

                        </div>

                    </form>

                </div>

            </div>

        </div>

    </div>

</div>

@endsection
