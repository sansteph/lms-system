@extends('layouts.app')

@section('content')

<div class="container-fluid">
    <div class="row">

        @include('layouts.sidebar')

        <div class="col-md-10 col-lg-10 p-4">

            <div class="page-header mb-4">
                <h2>Hybrid Learners</h2>
                <p class="text-muted mb-0">
                    View and manage self-registered learners.
                </p>
            </div>

            @if(session('success'))
                <div class="alert alert-success">
                    {{ session('success') }}
                </div>
            @endif

            <div class="card shadow border-0">
                <div class="card-body">

                    <form method="GET"
                          action="{{ route('admin.independent.learners') }}"
                          class="row mb-3">

                        <div class="col-md-4">
                            <input type="text"
                                   name="search"
                                   class="form-control"
                                   placeholder="Search name or email"
                                   value="{{ request('search') }}">
                        </div>

                        <div class="col-md-2">
                            <button type="submit"
                                    class="btn btn-primary w-100">
                                Search
                            </button>
                        </div>

                    </form>

                    <table class="table table-bordered table-hover align-middle">

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
                                    <td colspan="6" class="text-center text-muted">
                                        No hybrid learners found.
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
