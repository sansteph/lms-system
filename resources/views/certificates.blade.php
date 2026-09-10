@extends('layouts.app')

@section('content')

<div class="container-fluid">
    <div class="row">

        @include('layouts.sidebar')

        <div class="col-md-10 col-lg-10 p-4">

            <div class="page-header mb-4">
                <h2 class="mb-1">Certificate Management</h2>
                <p class="text-muted mb-0">Review program completion certificate requests before students can access them.</p>
            </div>

            @if(session('success'))
                <div class="alert alert-success">{{ session('success') }}</div>
            @endif

            @if(session('error'))
                <div class="alert alert-danger">{{ session('error') }}</div>
            @endif

            <div class="card shadow border-0 mb-4">
                <div class="card-body lms-report-filter-card">
                    <div class="d-flex justify-content-between align-items-start flex-wrap gap-3 mb-3">
                        <div>
                            <h5 class="mb-1">Apply filters for easy navigation</h5>
                            <p class="text-muted mb-0">Choose institute, class, section, and certificate status to load relevant approval requests.</p>
                        </div>
                        <span class="badge bg-light text-dark border">Approval Filters</span>
                    </div>

                    <form method="GET" action="{{ route('admin.certificates') }}">
                        <div class="lms-report-filter-grid">
                            @if(session('user_role') == 'Admin')
                                <div class="lms-report-action-group">
                                    <label class="form-label">Institute</label>
                                    <select name="institute" class="form-select">
                                        <option value="">Select institute</option>
                                        @foreach($certificateInstituteOptions as $instituteOption)
                                            <option value="{{ $instituteOption }}" @selected($currentInstitute == $instituteOption)>{{ $instituteOption }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            @endif

                            <div class="lms-report-action-group">
                                <label class="form-label">Class</label>
                                <select name="student_class" class="form-select">
                                    <option value="">Select class</option>
                                    @foreach($certificateClassOptions as $classOption)
                                        <option value="{{ $classOption }}" @selected($selectedStudentClass == $classOption)>{{ $classOption }}</option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="lms-report-action-group">
                                <label class="form-label">Section</label>
                                <select name="student_section" class="form-select">
                                    <option value="">Select section</option>
                                    @foreach($certificateSectionOptions as $sectionOption)
                                        <option value="{{ $sectionOption }}" @selected($selectedStudentSection == $sectionOption)>{{ $sectionOption }}</option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="lms-report-action-group">
                                <label class="form-label">Status</label>
                                <select name="certificate_status" class="form-select">
                                    <option value="">Any status</option>
                                    <option value="Pending Approval" @selected($selectedCertificateStatus == 'Pending Approval')>Pending Approval</option>
                                    <option value="pending_admin_approval" @selected($selectedCertificateStatus == 'pending_admin_approval')>Pending Approval (legacy)</option>
                                    <option value="approved" @selected($selectedCertificateStatus == 'approved')>Approved</option>
                                    <option value="Revoked" @selected($selectedCertificateStatus == 'Revoked')>Revoked</option>
                                </select>
                            </div>
                        </div>

                        <div class="lms-report-button-band">
                            <button type="submit" class="btn btn-primary lms-report-action-button">Apply Filters</button>
                            <a href="{{ route('admin.certificates') }}" class="btn btn-outline-secondary lms-report-action-clear">Clear</a>
                        </div>
                    </form>
                </div>
            </div>

            @if(!$hasFilters)
                @include('partials.filter-placeholder')
            @else
                <div class="card shadow border-0">
                    <div class="card-body">
                        <div class="table-responsive lms-table-shell">
                            <table class="table table-bordered table-hover align-middle lms-table-fit">
                                <thead>
                                    <tr>
                                        <th>Sl. No</th>
                                        <th>Certificate Code</th>
                                        <th>Student Name</th>
                                        <th>Program / Course</th>
                                        <th>Institute</th>
                                        <th>Class</th>
                                        <th>Section</th>
                                        <th>Final Percentage</th>
                                        <th>Grade</th>
                                        <th>Classification</th>
                                        <th>Issued Date</th>
                                        <th>Status</th>
                                        <th width="220">Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @php
                                        $certificateRows = method_exists($certificates, 'getCollection') ? $certificates->getCollection() : collect($certificates);
                                        $sortedCertificates = $certificateRows
                                            ->sortBy([
                                                fn ($certificate) => $certificate->student->institute ?? '',
                                                fn ($certificate) => $certificate->student->class ?? '',
                                                fn ($certificate) => $certificate->student->section ?? '',
                                                fn ($certificate) => $certificate->student->name ?? '',
                                            ])
                                            ->values();
                                        $rowNumber = 1;
                                    @endphp

                                    @forelse($sortedCertificates as $certificate)
                                        <tr>
                                            <td>{{ $rowNumber++ }}</td>
                                            <td>{{ $certificate->certificate_code }}</td>
                                            <td>{{ $certificate->student->name ?? 'Student Deleted' }}</td>
                                            <td>{{ $certificate->course->course_title ?? 'Program Completion' }}</td>
                                            <td>{{ $certificate->student->institute ?? 'N/A' }}</td>
                                            <td>{{ $certificate->student->class ?? 'N/A' }}</td>
                                            <td>{{ $certificate->student->section ?? 'N/A' }}</td>
                                            <td>{{ $certificate->final_score ?? $certificate->badge_count }}%</td>
                                            <td>{{ $certificate->final_grade ?? 'N/A' }}</td>
                                            <td>{{ $certificate->final_classification ?? 'N/A' }}</td>
                                            <td>{{ $certificate->issued_date ? \Carbon\Carbon::parse($certificate->issued_date)->format('d M Y') : 'Awaiting approval' }}</td>
                                            <td>
                                                @if(in_array($certificate->status, ['Pending', 'Pending Approval', 'pending_admin_approval']))
                                                    <span class="badge bg-warning text-dark">Pending Approval</span>
                                                @elseif($certificate->status == 'Revoked')
                                                    <span class="badge bg-danger">Revoked</span>
                                                @else
                                                    <span class="badge bg-success">Issued</span>
                                                @endif
                                            </td>
                                            <td>
                                                <div class="d-flex flex-wrap gap-2">
                                                    @if(in_array($certificate->status, ['Pending', 'Pending Approval', 'pending_admin_approval']))
                                                        <form method="POST" action="{{ route('admin.certificates.approve', $certificate->id) }}">
                                                            @csrf
                                                            <button type="submit" class="btn btn-sm btn-success">Approve</button>
                                                        </form>
                                                    @endif

                                                    @if($certificate->status != 'Revoked')
                                                        <form method="POST" action="{{ route('admin.certificates.revoke', $certificate->id) }}">
                                                            @csrf
                                                            <button type="submit" class="btn btn-sm btn-outline-danger" onclick="return confirm('Revoke this certificate?')">Revoke</button>
                                                        </form>
                                                    @else
                                                        <form method="POST" action="{{ route('admin.certificates.reissue', $certificate->id) }}">
                                                            @csrf
                                                            <button type="submit" class="btn btn-sm btn-success">Reissue</button>
                                                        </form>
                                                    @endif
                                                </div>
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="13" class="text-center text-muted py-4">No certificate requests found yet.</td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>

                    @if(method_exists($certificates, 'links'))
                        <div class="px-3 pb-3">
                            {{ $certificates->links('pagination::bootstrap-5') }}
                        </div>
                    @endif
                </div>
            @endif
        </div>
    </div>
</div>

@endsection
