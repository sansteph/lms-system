@extends('layouts.app')

@section('content')

<div class="container-fluid">
    <div class="row">

        @include('layouts.sidebar')

        <div class="col-md-10 col-lg-10 p-4">

            <div class="page-header mb-4">
                <h2>My Space Review</h2>
                <p class="text-muted mb-0">
                    Review, approve, reject, and feature submitted ideas and projects.
                </p>
            </div>

            @if(session('success'))
                <div class="alert alert-success">
                    {{ session('success') }}
                </div>
            @endif

            <div class="card shadow border-0">
                <div class="card-body">

                    <div class="table-responsive">

                        <table class="table table-hover align-middle">

                            <thead>
                                <tr>
                                    <th>Title</th>
                                    <th>Type</th>
                                    <th>Submitter</th>
                                    <th>Role</th>
                                    <th>Status</th>
                                    <th width="220">Actions</th>
                                </tr>
                            </thead>

                            <tbody>

                                @forelse($items as $item)

                                    <tr>
                                        <td>{{ $item->title }}</td>

                                        <td>
                                            <span class="badge bg-info">
                                                {{ $item->type }}
                                            </span>
                                        </td>

                                        <td>

                                            @php
                                                $submitter = $item->submitter();
                                            @endphp

                                            @if($submitter)

                                                <strong>{{ $submitter->name }}</strong>

                                                <br>

                                                <small class="text-muted">

                                                    {{ $item->created_by_type == 'Student'
                                                        ? $submitter->student_id
                                                        : $submitter->user_id }}

                                                </small>

                                            @else

                                                Deleted User

                                            @endif

                                        </td>

                                        <td>
                                            {{ $item->created_by_type }}
                                        </td>

                                        <td>
                                            @if($item->status == 'Approved')
                                                <span class="badge bg-success">Approved</span>
                                            @elseif($item->status == 'Rejected')
                                                <span class="badge bg-danger">Rejected</span>
                                            @elseif($item->status == 'Featured')
                                                <span class="badge bg-warning text-dark">Featured</span>
                                            @else
                                                <span class="badge bg-secondary">{{ $item->status }}</span>
                                            @endif
                                        </td>

                                        <td>
                                            <div class="d-flex flex-wrap gap-2">

                                                <a href="{{ route('admin.my-space.show', $item->id) }}"class="btn btn-sm btn-primary">
                                                    View
                                                </a>

                                                <form method="POST"
                                                      action="{{ route('admin.my-space.approve', $item->id) }}">
                                                    @csrf
                                                    <button type="submit"
                                                            class="btn btn-sm btn-success">
                                                        Approve
                                                    </button>
                                                </form>

                                                <form method="POST"
                                                      action="{{ route('admin.my-space.reject', $item->id) }}">
                                                    @csrf
                                                    <button type="submit"
                                                            class="btn btn-sm btn-danger">
                                                        Reject
                                                    </button>
                                                </form>

                                                <form method="POST"
                                                      action="{{ route('admin.my-space.feature', $item->id) }}">
                                                    @csrf
                                                    <button type="submit"
                                                            class="btn btn-sm btn-warning">
                                                        Feature
                                                    </button>
                                                </form>

                                            </div>
                                        </td>
                                    </tr>

                                @empty

                                    <tr>
                                        <td colspan="6" class="text-center text-muted py-4">
                                            No My Space submissions found.
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
</div>

@endsection