@extends('layouts.app')

@section('content')

<div class="container py-5">

    <div class="certificate-container">

        <div class="certificate-border">

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

                    <small class="text-muted d-block mt-2">
                        Verify at: {{ route('certificate.verify') }}
                    </small>

                    <div>
                        <small class="text-muted d-block">
                            Issued Date
                        </small>

                        <strong>
                            {{ \Carbon\Carbon::parse($certificate->issued_date)->format('d M Y') }}
                        </strong>
                    </div>

                </div>

                <div class="mt-5">

                    <button onclick="window.print()"
                            class="btn btn-primary">

                        <i class="fa fa-download"></i>
                        Download Certificate

                    </button>

                </div>

            </div>

        </div>

    </div>

</div>

@endsection