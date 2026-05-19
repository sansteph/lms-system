@extends('layouts.app')

@section('content')

<div class="container-fluid">

    <div class="row">

        @include('layouts.student-sidebar')

        <div class="col-md-10 col-lg-10 p-4">

            <div class="d-flex justify-content-between align-items-center flex-wrap gap-3 mb-4">

                <div>

                    <h3 class="fw-bold mb-1">
                        Upload Achievement
                    </h3>

                    <p class="text-muted mb-0">
                        Add competitions, workshops, certifications, and extracurricular achievements.
                    </p>

                </div>

                <a href="{{ route('student.achievements') }}"
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

                    <form action="{{ route('student.achievements.store') }}"
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

                                    <option value="Competition">
                                        Competition
                                    </option>

                                    <option value="Exhibition">
                                        Exhibition
                                    </option>

                                    <option value="Workshop">
                                        Workshop
                                    </option>

                                    <option value="Certification">
                                        Certification
                                    </option>

                                    <option value="Course">
                                        Extra Course
                                    </option>

                                </select>

                            </div>

                            <div class="col-md-6">

                                <label class="form-label fw-semibold">

                                    Achievement Date

                                </label>

                                <input type="date"
                                       name="achievement_date"
                                       class="form-control">

                            </div>

                            <div class="col-md-12">

                                <label class="form-label fw-semibold">

                                    Title

                                </label>

                                <input type="text"
                                       name="title"
                                       class="form-control"
                                       placeholder="Enter achievement title"
                                       required>

                            </div>

                            <div class="col-md-6">

                                <label class="form-label fw-semibold">

                                    Organizer

                                </label>

                                <input type="text"
                                       name="organizer"
                                       class="form-control"
                                       placeholder="Organization / Institution">

                            </div>

                            <div class="col-md-6">

                                <label class="form-label fw-semibold">

                                    Position / Result

                                </label>

                                <input type="text"
                                       name="position"
                                       class="form-control"
                                       placeholder="Winner / Participant / Rank">

                            </div>

                            <div class="col-md-12">

                                <label class="form-label fw-semibold">

                                    Description

                                </label>

                                <textarea name="description"
                                          class="form-control"
                                          rows="5"
                                          placeholder="Describe the achievement"></textarea>

                            </div>

                            <div class="col-md-12">

                                <label class="form-label fw-semibold">

                                    Upload Certificate

                                </label>

                                <input type="file"
                                       name="certificate_file"
                                       class="form-control"
                                       accept=".pdf,.jpg,.jpeg,.png"
                                       required>

                                <small class="text-muted">

                                    Accepted formats:
                                    PDF, JPG, JPEG, PNG (Max: 5MB)

                                </small>

                            </div>

                        </div>

                        <div class="d-flex justify-content-end gap-3 mt-4">

                            <a href="{{ route('student.achievements') }}"
                               class="btn btn-light border">

                                Cancel

                            </a>

                            <button type="submit"
                                    class="btn btn-primary">

                                <i class="fa fa-upload me-2"></i>

                                Submit Achievement

                            </button>

                        </div>

                    </form>

                </div>

            </div>

        </div>

    </div>

</div>

@endsection