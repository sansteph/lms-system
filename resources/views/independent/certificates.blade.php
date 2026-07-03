@extends('layouts.app')

@section('content')

<div class="container py-5">

    <div class="card shadow border-0">

        <div class="card-body">

            <h2 class="mb-4">
                My Certificates
            </h2>

            <table class="table table-bordered">

                <thead>

                    <tr>

                        <th>Certificate Code</th>

                        <th>Issued Date</th>

                        <th>Course</th>

                        <th>Status</th>

                    </tr>

                </thead>

                <tbody>

                    @forelse($certificates as $certificate)

                        <tr>

                            <td>
                                {{ $certificate->certificate_code }}
                            </td>

                            <td>
                                {{ $certificate->issued_date }}
                            </td>

                            <td>
                                {{ $certificate->course->course_title ?? 'Course Deleted' }}
                            </td>

                            <td>

                                <span class="badge bg-success">

                                    {{ $certificate->status }}

                                </span>

                            </td>

                        </tr>

                    @empty

                        <tr>

                            <td colspan="3" class="text-center">

                                No certificates available.

                            </td>

                        </tr>

                    @endforelse

                </tbody>

            </table>

        </div>

    </div>

</div>

@endsection
