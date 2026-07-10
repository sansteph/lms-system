@extends('layouts.app')

@section('content')

<div class="container-fluid">
    <div class="row">

        @include('layouts.sidebar')

        <div class="col-md-10 col-lg-10 p-4">

            <div class="page-header mb-4">
                <h2 class="mb-1">Certificate Management</h2>
                <p class="text-muted mb-0">
                    Review program completion certificate requests before students can access them.
                </p>
            </div>

            @if(session('success'))
                <div class="alert alert-success">{{ session('success') }}</div>
            @endif

            @if(session('error'))
                <div class="alert alert-danger">{{ session('error') }}</div>
            @endif

            <div class="card shadow border-0">
                <div class="card-body">

                    <table class="table table-bordered table-hover align-middle">
                        <thead>
                            <tr>
                                <th>Sl. No</th>
                                <th>Certificate Code</th>
                                <th>Student Name</th>
                                <th>Program / Course</th>
                                <th>Final Percentage</th>
                                <th>Grade</th>
                                <th>Classification</th>
                                <th>Issued Date</th>
                                <th>Status</th>
                                <th width="220">Actions</th>
                            </tr>
                        </thead>

                        <tbody>
                            @forelse($certificates as $index => $certificate)
                                <tr>
                                    <td>{{ $index + 1 }}</td>
                                    <td>{{ $certificate->certificate_code }}</td>
                                    <td>{{ $certificate->student->name ?? 'Student Deleted' }}</td>
                                    <td>{{ $certificate->course->course_title ?? 'Program Completion' }}</td>
                                    <td>{{ $certificate->final_score ?? $certificate->badge_count }}%</td>
                                    <td>{{ $certificate->final_grade ?? 'N/A' }}</td>
                                    <td>{{ $certificate->final_classification ?? 'N/A' }}</td>
                                    <td>
                                        {{ $certificate->issued_date
                                            ? \Carbon\Carbon::parse($certificate->issued_date)->format('d M Y')
                                            : 'Awaiting approval' }}
                                    </td>
                                    <td>
                                        @if(in_array($certificate->status, ['Pending Approval', 'pending_admin_approval']))
                                            <span class="badge bg-warning text-dark">Pending Approval</span>
                                        @elseif($certificate->status == 'Revoked')
                                            <span class="badge bg-danger">Revoked</span>
                                        @else
                                            <span class="badge bg-success">Issued</span>
                                        @endif
                                    </td>
                                    <td>
                                        <div class="d-flex flex-wrap gap-2">
                                            @if(in_array($certificate->status, ['Pending Approval', 'pending_admin_approval']))
                                                <form method="POST" action="{{ route('admin.certificates.approve', $certificate->id) }}">
                                                    @csrf
                                                    <button type="submit" class="btn btn-sm btn-success">
                                                        Approve
                                                    </button>
                                                </form>
                                            @endif

                                            @if($certificate->status != 'Revoked')
                                                <form method="POST" action="{{ route('admin.certificates.revoke', $certificate->id) }}">
                                                    @csrf
                                                    <button type="submit"
                                                            class="btn btn-sm btn-outline-danger"
                                                            onclick="return confirm('Revoke this certificate?')">
                                                        Revoke
                                                    </button>
                                                </form>
                                            @else
                                                <form method="POST" action="{{ route('admin.certificates.reissue', $certificate->id) }}">
                                                    @csrf
                                                    <button type="submit" class="btn btn-sm btn-success">
                                                        Reissue
                                                    </button>
                                                </form>
                                            @endif
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="10" class="text-center text-muted">
                                        No certificate requests found yet.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>

                </div>
            </div>

        </div>

    </div>
</div>

@endsection

