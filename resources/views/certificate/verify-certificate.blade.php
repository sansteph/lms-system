@extends('layouts.app')

@section('content')

<div class="container py-5">

    <div class="row justify-content-center">

        <div class="col-md-7">

            <div class="card shadow border-0">

                <div class="card-body p-5">

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

                    @elseif(isset($certificate) && $certificate)

                        <div class="alert alert-success mt-4">

                            <h5 class="mb-3">
                                Certificate Verified
                            </h5>

                            <p class="mb-2">
                                <strong>Certificate Code:</strong>
                                {{ $certificate->certificate_code }}
                            </p>

                            <p class="mb-2">
                                <strong>Student Name:</strong>
                                {{ $certificate->student->student_name ?? 'N/A' }}
                            </p>

                            <p class="mb-2">
                                <strong>Student ID:</strong>
                                {{ $certificate->student->student_id ?? 'N/A' }}
                            </p>

                            <p class="mb-2">
                                <strong>Badge Count:</strong>
                                {{ $certificate->badge_count }}
                            </p>

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