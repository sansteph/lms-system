@extends('layouts.app')

@section('content')

<div class="container-fluid">
    <div class="row">

        @include('layouts.sidebar')

        <div class="col-md-10 col-lg-10 p-4">

            <div class="page-header mb-4">
                <h2 class="mb-1">Certificate Management</h2>
                <p class="text-muted mb-0">
                    View issued student certificates and verification details.
                </p>
            </div>

            <div class="card shadow border-0">
                <div class="card-body">

                    <table class="table table-bordered table-hover align-middle">
                        <thead>
                            <tr>
                                <th>Sl. No</th>
                                <th>Certificate Code</th>
                                <th>Student Name</th>
                                <th>Student ID</th>
                                <th>Badges</th>
                                <th>Issued Date</th>
                                <th>Status</th>
                                <th width="160">Actions</th>
                            </tr>
                        </thead>

                        <tbody>
                            @forelse($certificates as $index => $certificate)
                                <tr>
                                    <td>{{ $index + 1 }}</td>
                                    <td>{{ $certificate->certificate_code }}</td>
                                    <td>{{ $certificate->student->name ?? 'Student Deleted' }}</td>
                                    <td>{{ $certificate->student_id }}</td>
                                    <td>{{ $certificate->badge_count }}</td>
                                    <td>{{ $certificate->issued_date }}</td>
                                    <td>
                                        <span class="badge
                                            {{ $certificate->status == 'Revoked'
                                                ? 'bg-danger'
                                                : 'bg-success' }}">

                                            {{ $certificate->status }}

                                        </span>
                                    </td>
                                    <td>

                                        @if($certificate->status != 'Revoked')

                                            <form method="POST"
                                                action="{{ route('admin.certificates.revoke', $certificate->id) }}">

                                                @csrf

                                                <button type="submit"
                                                        class="btn btn-sm btn-danger"
                                                        onclick="return confirm('Revoke this certificate?')">

                                                    Revoke

                                                </button>

                                            </form>

                                        @else

                                            <form method="POST"
                                                action="{{ route('admin.certificates.reissue', $certificate->id) }}">

                                                @csrf

                                                <button type="submit"
                                                        class="btn btn-sm btn-success">

                                                    Reissue

                                                </button>

                                            </form>

                                        @endif

                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="7" class="text-center text-muted">
                                        No certificates issued yet.
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