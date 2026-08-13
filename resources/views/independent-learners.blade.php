@extends('layouts.app')

@section('content')

<div class="container-fluid">
    <div class="row align-items-stretch">

        @include('layouts.sidebar')

        <div class="col-md-10 col-lg-10 p-4 d-flex flex-column">
            <div class="page-header d-flex justify-content-between align-items-center flex-wrap gap-3 mb-4">
                <div>
                    <h2 class="mb-1">Hybrid Learners</h2>
                    <p class="text-muted mb-0">View and manage self-registered learners.</p>
                </div>
            </div>

            @if(session('success'))
                <div class="alert alert-success">
                    {{ session('success') }}
                </div>
            @endif

            <div class="card border-0 shadow-sm mb-4">
                <div class="card-body">
                    <div class="d-flex align-items-center gap-2 mb-3">
                        <span class="badge bg-primary-subtle text-primary">Filter Hybrid Learners</span>
                        <span class="text-muted small">Search by learner name or email to narrow down the list.</span>
                    </div>
                    <form method="GET" action="{{ route('admin.independent.learners') }}" class="row g-3 align-items-end">
                        <div class="col-md-6 col-lg-5">
                            <label class="form-label">Search</label>
                            <input type="text"
                                   name="search"
                                   class="form-control"
                                   placeholder="Search name or email"
                                   value="{{ request('search') }}">
                        </div>
                        <div class="col-md-3 col-lg-2">
                            <button type="submit" class="btn btn-primary w-100">Apply Filters</button>
                        </div>
                        <div class="col-md-3 col-lg-2">
                            <a href="{{ route('admin.independent.learners') }}" class="btn btn-outline-secondary w-100">Clear</a>
                        </div>
                    </form>
                </div>
            </div>

            <div class="card shadow border-0 flex-grow-1">
                <div class="card-body p-4">
                    <div class="table-responsive lms-table-shell">
                        <table class="table table-bordered table-hover align-middle lms-table-fit">

                            <thead>
                                <tr>
                                    <th>Name</th>
                                    <th>Email</th>
                                    <th>Phone</th>
                                    <th>Registered On</th>
                                    <th>Enrollments</th>
                                    <th>Certificates</th>
                                    <th>Status</th>
                                    <th width="160">Action</th>
                                </tr>
                            </thead>

                            <tbody>

                                @forelse($learners as $learner)

                                    <tr>
                                        <td>{{ $learner->name }}</td>
                                        <td>{{ $learner->email }}</td>
                                        <td>{{ $learner->phone ?? 'N/A' }}</td>
                                        <td>{{ $learner->created_at->format('d M Y') }}</td>
                                        <td>
                                            {{ $learner->enrollments_count }}
                                        </td>

                                        <td>
                                            {{ $learner->certificates_count }}
                                        </td>

                                        <td>
                                            @if($learner->status)
                                                <span class="badge bg-success">Active</span>
                                            @else
                                                <span class="badge bg-danger">Inactive</span>
                                            @endif
                                        </td>

                                        <td>
                                            <form method="POST"
                                                  action="{{ route('admin.independent.learners.toggle-status', $learner->id) }}">
                                                @csrf
                                                <a href="{{ route('admin.independent.learners.show', $learner->id) }}" class="btn btn-sm btn-outline-primary mb-2">
                                                    View
                                                </a>
                                                <button type="submit"
                                                        class="btn btn-sm {{ $learner->status ? 'btn-outline-danger' : 'btn-success' }}">
                                                    {{ $learner->status ? 'Deactivate' : 'Activate' }}
                                                </button>
                                            </form>
                                        </td>
                                    </tr>

                                @empty

                                    <tr>
                                        <td colspan="8" class="text-center text-muted">
                                            No hybrid learners found.
                                        </td>
                                    </tr>

                                @endforelse

                            </tbody>

                        </table>
                    </div>

                    @if($learners->hasPages())
                        <div class="mt-3">
                            {{ $learners->links('pagination::bootstrap-5') }}
                        </div>
                    @endif

                </div>
            </div>

        </div>

    </div>
</div>

@endsection
