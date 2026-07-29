@extends('layouts.app')

@section('title', 'Verify Certificate | InnovatEdge')
@section('meta_description', 'Verify InnovatEdge LMS certificates securely using a valid certificate ID or certificate code.')

@section('content')

<div class="container py-5">

    <div class="row justify-content-center">

        <div class="col-md-7">

            <div class="card shadow border-0">

                <div class="card-body p-5">

                    <div class="mb-4">
                        <a href="{{ url()->previous() != url()->current() ? url()->previous() : route('home') }}"
                           class="btn btn-outline-secondary">
                            <i class="fa fa-arrow-left me-2"></i>
                            Back
                        </a>
                    </div>

                    <div class="text-center mb-4">

                        <i class="fa fa-certificate text-primary"
                           style="font-size: 60px;"></i>

                        <h2 class="mt-3">
                            Certificate Verification
                        </h2>

                        <p class="text-muted">
                            Verify LMS certificates using certificate code.
                        </p>

                    </div>

                    <form method="POST"
                          action="{{ route('certificate.verify.submit') }}">

                        @csrf
                        
                        <div class="mb-3">
                            <label class="form-label">Your Name</label>
                            <input type="text"
                                   name="verifier_name"
                                   class="form-control"
                                   placeholder="Enter your full name"
                                   value="{{ old('verifier_name') }}"
                                   required>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Email Address</label>
                            <input type="email"
                                   name="verifier_email"
                                   class="form-control"
                                   placeholder="Enter your email address"
                                   value="{{ old('verifier_email') }}"
                                   required>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Reason for Verification</label>
                            <textarea name="verification_reason"
                                      class="form-control"
                                      rows="3"
                                      placeholder="Example: Employment verification, admission verification, document validation"
                                      required>{{ old('verification_reason') }}</textarea>
                        </div>

                        <div class="mb-3">

                            <label class="form-label">
                                Certificate Code
                            </label>

                            <input type="text"
                                   name="certificate_code"
                                   class="form-control"
                                   placeholder="Enter certificate code"
                                   required>

                        </div>

                        <button type="submit"
                                class="btn btn-primary w-100">

                            Verify Certificate

                        </button>

                    </form>

                    @if(isset($revoked) && $revoked)

                        <div class="alert alert-danger mt-4">

                            This certificate has been revoked and is no longer valid.

                        </div>

                    @elseif(isset($inactive) && $inactive)

                        <div class="alert alert-warning mt-4">

                            This certificate has been generated but is waiting for admin approval.

                        </div>

                    @elseif(isset($certificate) && $certificate)

                        <div class="alert alert-success mt-4">

                            <h5 class="mb-3">
                                Certificate Verified
                            </h5>

                            <p class="mb-2">
                                <strong>Certificate Code:</strong>
                                {{ $certificate->certificate_code }}
                            </p>

                            @if($certificate->certificate_type == 'Independent')

                                <p class="mb-2">
                                    <strong>Learner Name:</strong>
                                    {{ $certificate->independentLearner->name ?? 'N/A' }}
                                </p>

                                <p class="mb-2">
                                    <strong>Course:</strong>
                                    {{ $certificate->course->course_title ?? 'Program Completion' }}
                                </p>

                            @else

                                <p class="mb-2">
                                    <strong>Student Name:</strong>
                                    {{ $certificate->student->name ?? 'N/A' }}
                                </p>

                                <p class="mb-2">
                                    <strong>Student ID:</strong>
                                    {{ $certificate->student->student_id ?? 'N/A' }}
                                </p>

                                <p class="mb-2">
                                    <strong>Final Percentage:</strong>
                                    {{ $certificate->final_score ?? $certificate->badge_count }}%
                                    @if($certificate->final_grade)
                                        / Grade {{ $certificate->final_grade }}
                                    @endif
                                    @if($certificate->final_classification)
                                        / {{ $certificate->final_classification }}
                                    @endif
                                </p>

                            @endif

                            <p class="mb-2">
                                <strong>Issued Date:</strong>
                                {{ \Carbon\Carbon::parse($certificate->issued_date)->format('d M Y') }}
                            </p>

                            <p class="mb-0">
                                <strong>Status:</strong>
                                {{ $certificate->status }}
                            </p>

                        </div>

                    @elseif(request()->isMethod('post'))

                        <div class="alert alert-danger mt-4">

                            No certificate found for this code.

                        </div>

                    @endif

                </div>

            </div>

        </div>

    </div>

</div>

@endsection

