@extends('layouts.app')

@section('content')

<div class="container-fluid">
    <div class="row">

        @include('layouts.teacher-sidebar')

        <div class="col-md-10 col-lg-10 p-4">

            <div class="page-header d-flex justify-content-between align-items-center flex-wrap gap-3 mb-4">
                <div>
                    <h2 class="mb-1">Certificates</h2>
                    <p class="text-muted mb-0">
                        Track program completion certificate requests for eligible students.
                    </p>
                </div>

            </div>

            <div class="row g-4 mb-4">
                <div class="col-md-4">
                    <div class="dashboard-card">
                        <h6>Total Certificates</h6>
                        <h2>{{ $totalCertificates }}</h2>
                    </div>
                </div>

                <div class="col-md-4">
                    <div class="dashboard-card">
                        <h6>Issued</h6>
                        <h2>{{ $issuedCertificates }}</h2>
                    </div>
                </div>

                <div class="col-md-4">
                    <div class="dashboard-card">
                        <h6>Revoked</h6>
                        <h2>{{ $revokedCertificates }}</h2>
                    </div>
                </div>
            </div>
            <div class="card shadow border-0">
                <div class="card-body">

                    <table class="table table-bordered table-hover align-middle">
                        <thead class="table-dark">
                            <tr>
                                <th>Sl. No</th>
                                <th>Student Name</th>
                                <th>Class</th>
                                <th>Certificate Code</th>
                                <th>Final Percentage</th>
                                <th>Grade</th>
                                <th>Classification</th>
                                <th>Issued Date</th>
                                <th>Status</th>
                            </tr>
                        </thead>

                        <tbody>
                            @forelse($certificates as $index => $certificate)
                                <tr>
                                    <td>{{ $index + 1 }}</td>

                                    <td>{{ $certificate->student->name ?? 'Student Deleted' }}</td>

                                    <td>{{ $certificate->student->class ?? 'N/A' }}</td>

                                    <td>{{ $certificate->certificate_code }}</td>

                                    <td>{{ $certificate->final_score ?? $certificate->badge_count }}%</td>
                                    <td>{{ $certificate->final_grade ?? 'N/A' }}</td>
                                    <td>{{ $certificate->final_classification ?? 'N/A' }}</td>

                                    <td>
                                        {{ $certificate->issued_date ? \Carbon\Carbon::parse($certificate->issued_date)->format('d-m-Y') : 'Awaiting Approval' }}
                                    </td>

                                    <td>
                                        @if($certificate->status == 'Issued')
                                            <span class="badge bg-success">Issued</span>
                                        @elseif($certificate->status == 'Revoked')
                                            <span class="badge bg-danger">Revoked</span>
                                        @else
                                            <span class="badge bg-secondary">{{ $certificate->status }}</span>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="9" class="text-center text-muted">
                                        No certificates found.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>

                    </table>

                    <div class="alert alert-info mt-3 mb-0">
                        Certificate requests are prepared after Annual Assessment evaluation and issued only after Admin approval.
                    </div>

                </div>
            </div>
        </div>
    </div>
</div>

@endsection
