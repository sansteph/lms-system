@extends('layouts.app')

@section('content')

<div class="container py-5">

    <div class="certificate-container certificate-print-area">

        <div class="certificate-border p-5">

            <div class="text-center">

                <h5 class="certificate-top">
                    LMS ACHIEVEMENT CERTIFICATE
                </h5>

                <h1 class="certificate-title">
                    Certificate of Achievement
                </h1>

                <p class="certificate-subtitle">
                    This certificate is proudly presented to
                </p>

                <h2 class="student-name">
                    {{ $student->name }}
                </h2>

                <p class="certificate-description">
                    For successfully earning achievement badges
                    and demonstrating outstanding learning performance
                    in the LMS platform.
                </p>

                <div class="certificate-details mt-5">

                    <div>
                        <small class="text-muted d-block">
                            Certificate Code
                        </small>

                        <strong>
                            {{ $certificate->certificate_code }}
                        </strong>
                    </div>

                    <div class="mt-3">
                        <small class="text-muted d-block">
                            Verify at
                        </small>

                        <strong>
                            {{ route('certificate.verify') }}
                        </strong>
                    </div>

                    <div class="mt-3">
                        <small class="text-muted d-block">
                            Issued Date
                        </small>

                        <strong>
                            {{ \Carbon\Carbon::parse($certificate->issued_date)->format('d M Y') }}
                        </strong>
                    </div>

                </div>

            </div>

        </div>

    </div>

    <div class="certificate-actions no-print text-center mb-4">

        <div class="mb-3">
            <a href="{{ route('student.certificate.download') }}"
            class="btn btn-primary">
                <i class="fa fa-download me-2"></i>
                Download Certificate
            </a>
        </div>

        <div class="mb-3">
            <a href="{{ route('student.badges') }}"
            class="btn btn-outline-secondary ms-2">
                <i class="fa fa-arrow-left me-2"></i>
                Back
            </a>
        </div>
    </div>

</div>

@endsection