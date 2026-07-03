@extends('layouts.app')

@section('content')

<div class="container-fluid">

    <div class="row">

        @include('layouts.sidebar')

        <div class="col-md-10 col-lg-10 p-4">

            <div class="page-header mb-4">

                <h2>
                    Institute Registration Requests
                </h2>

                <p class="text-muted mb-0">
                    Review and approve institute onboarding requests.
                </p>

            </div>

            @if(session('success'))

                <div class="alert alert-success">
                    {{ session('success') }}
                </div>

            @endif

            @if(session('error'))

                <div class="alert alert-danger">
                    {{ session('error') }}
                </div>

            @endif

            <div class="card shadow border-0">

                <div class="card-body">

                    <table class="table table-bordered table-hover align-middle">

                        <thead>

                            <tr>

                                <th>Request ID</th>

                                <th>Institute</th>

                                <th>Admin Name</th>

                                <th>Email</th>

                                <th>Phone</th>

                                <th>Location</th>

                                <th>Status</th>

                                <th width="220">Actions</th>

                            </tr>

                        </thead>

                        <tbody>

                            @forelse($requests as $request)

                                <tr>

                                    <td>
                                        {{ $request->request_id }}
                                    </td>

                                    <td>
                                        {{ $request->institute_name }}
                                    </td>

                                    <td>
                                        {{ $request->admin_name }}
                                    </td>

                                    <td>
                                        {{ $request->admin_email }}
                                    </td>

                                    <td>
                                        {{ $request->phone }}
                                    </td>

                                    <td>
                                        {{ $request->location }}
                                    </td>

                                    <td>

                                        @if($request->status == 'Pending')

                                            <span class="badge bg-warning text-dark">
                                                Pending
                                            </span>

                                        @elseif($request->status == 'Approved')

                                            <span class="badge bg-success">
                                                Approved
                                            </span>

                                        @else

                                            <span class="badge bg-danger">
                                                Rejected
                                            </span>

                                        @endif

                                    </td>

                                    <td>

                                        @if($request->status == 'Pending')

                                            <div class="d-flex gap-2">

                                                <form method="POST"
                                                      action="{{ route('admin.institute.requests.approve', $request->id) }}">

                                                    @csrf

                                                    <button type="submit"
                                                            class="btn btn-success btn-sm">

                                                        Approve

                                                    </button>

                                                </form>

                                                <form method="POST"
                                                      action="{{ route('admin.institute.requests.reject', $request->id) }}">

                                                    @csrf

                                                    <button type="submit"
                                                            class="btn btn-sm btn-outline-danger">

                                                        Reject

                                                    </button>

                                                </form>

                                            </div>

                                        @else

                                            <span class="text-muted">
                                                Processed
                                            </span>

                                        @endif

                                    </td>

                                </tr>

                            @empty

                                <tr>

                                    <td colspan="8"
                                        class="text-center text-muted">

                                        No registration requests found.

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
