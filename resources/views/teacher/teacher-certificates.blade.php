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

            <div class="card shadow-sm border-0 mb-4">
                <div class="card-body">
                    <form method="GET" action="{{ route('teacher.certificates') }}" class="row g-3 align-items-end">
                        <div class="col-md-4">
                            <label class="form-label">Class</label>
                            <select name="student_class" class="form-select">
                                <option value="">All Classes</option>
                                @foreach($classOptions as $classOption)
                                    <option value="{{ $classOption }}" @selected(($selectedStudentClass ?? '') === $classOption)>{{ $classOption }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div class="col-md-4">
                            <label class="form-label">Section</label>
                            <select name="student_section" class="form-select">
                                <option value="">All Sections</option>
                                @foreach($sectionOptions as $sectionOption)
                                    <option value="{{ $sectionOption }}" @selected(($selectedStudentSection ?? '') === $sectionOption)>{{ $sectionOption }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div class="col-md-4 d-flex gap-2 flex-wrap">
                            <button type="submit" class="btn btn-primary">Apply Filters</button>
                            <a href="{{ route('teacher.certificates') }}" class="btn btn-outline-secondary">Clear</a>
                        </div>
                    </form>
                </div>
            </div>

            @if($showFilterPlaceholder)
                @include('partials.filter-placeholder')
            @else
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
                    @if(!empty($selectedStudentClass))
                        <div class="alert alert-light border d-flex justify-content-between align-items-center flex-wrap gap-2">
                            <div>
                                <span class="fw-semibold">Current certificate scope:</span>
                                Class {{ $selectedStudentClass }}
                                @if(!empty($selectedStudentSection))
                                    &middot; Section {{ $selectedStudentSection }}
                                @endif
                            </div>
                        </div>
                    @endif

                    <div class="table-responsive lms-table-shell">
                    <table class="table table-bordered table-hover align-middle lms-table-fit">
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
                                <th>Actions</th>
                            </tr>
                        </thead>

                        <tbody>
                            @php $rowNumber = 1; @endphp
                            @forelse($certificates->groupBy(fn ($certificate) => $certificate->student ? trim($certificate->student->class . ' ' . $certificate->student->section) : 'Unassigned Class') as $classLabel => $classCertificates)
                                <tr class="table-primary">
                                    <td colspan="10" class="fw-semibold">{{ $classLabel }}</td>
                                </tr>
                                @foreach($classCertificates as $certificate)
                                <tr>
                                    <td>{{ $rowNumber++ }}</td>

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
                                        @if(in_array($certificate->status, ['Issued', 'approved']))
                                            <span class="badge bg-success">Issued</span>
                                        @elseif($certificate->status == 'Revoked')
                                            <span class="badge bg-danger">Revoked</span>
                                        @elseif(in_array($certificate->status, ['Pending', 'Pending Approval', 'pending_admin_approval']))
                                            <span class="badge bg-warning text-dark">Pending Approval</span>
                                        @else
                                            <span class="badge bg-secondary">{{ $certificate->status }}</span>
                                        @endif
                                    </td>
                                    <td>
                                        @if(in_array($certificate->status, ['Pending', 'Pending Approval', 'pending_admin_approval']))
                                            <form method="POST" action="{{ route('teacher.certificates.approve', $certificate->id) }}">
                                                @csrf
                                                <button type="submit" class="btn btn-sm btn-success">
                                                    Approve
                                                </button>
                                            </form>
                                        @else
                                            <span class="text-muted small">No action</span>
                                        @endif
                                    </td>
                                </tr>
                                @endforeach
                            @empty
                                <tr>
                                    <td colspan="10" class="text-center text-muted">
                                        No certificates found.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>

                    </table>
                    </div>

                    <div class="alert alert-info mt-3 mb-0">
                        Certificate requests are prepared after Annual Assessment evaluation and issued only after approval.
                    </div>

                </div>
            </div>
            @endif
        </div>
    </div>
</div>

@endsection

