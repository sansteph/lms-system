@extends('layouts.app')

@section('content')

<div class="container-fluid">
    <div class="row">

        @include('layouts.sidebar')

        <div class="col-md-10 col-lg-10 p-4">

            <div class="page-header d-flex justify-content-between align-items-center flex-wrap gap-3 mb-4">
                <div>
                    <h2 class="mb-1">STEM Engineer Management</h2>
                    <p class="text-muted mb-0">
                        Manage STEM Engineer accounts and account status.
                    </p>
                </div>

                <button class="btn btn-primary btn-sm"
                        data-bs-toggle="modal"
                        data-bs-target="#addUserModal">
                    Add STEM Engineer
                </button>
            </div>

            @if(session('success'))
                <div class="alert alert-success">{{ session('success') }}</div>
            @endif

            @if($errors->any())
                <div class="alert alert-danger">{{ $errors->first() }}</div>
            @endif

            <div class="card border-0 shadow-sm mb-3">
                <div class="card-body">
                    <div class="d-flex align-items-center gap-2 mb-3">
                        <span class="badge bg-primary-subtle text-primary">Filter STEM Engineers</span>
                        <span class="text-muted small">Use search to narrow the list without switching sections.</span>
                    </div>
                    <form method="GET" action="{{ route('users') }}" class="row g-3 align-items-end">
                        @if(session('user_role') === 'Admin')
                            <div class="col-md-4">
                                <label class="form-label">Institute</label>
                                <select name="institute" class="form-select">
                                    <option value="">All Institutes</option>
                                    @foreach($instituteOptions as $instituteOption)
                                        <option value="{{ $instituteOption }}" {{ request('institute') === $instituteOption ? 'selected' : '' }}>{{ $instituteOption }}</option>
                                    @endforeach
                                </select>
                            </div>
                        @endif
                        <div class="col-md-4">
                            <label class="form-label">Search</label>
                            <input type="text"
                                   name="search"
                                   class="form-control"
                                   placeholder="Search by name or email"
                                   value="{{ request('search') }}">
                        </div>

                        <div class="col-md-2">
                            <button type="submit" class="btn btn-primary w-100">Apply Filters</button>
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
                        <thead class="table-light">
                            <tr>
                                <th>Sl. No</th>
                                <th>STEM Engineer ID</th>
                                <th>Name</th>
                                <th>Email</th>
                                <th>Institute</th>
                                <th>Status</th>
                                <th width="180">Actions</th>
                            </tr>
                        </thead>

                        <tbody>
                            @php
                                $userRows = method_exists($users, 'getCollection') ? $users->getCollection() : collect($users);
                                $groupedUsers = $userRows->groupBy(fn ($user) => $user->institute ?: 'Unassigned Institute');
                                $rowNumber = 1;
                            @endphp

                            @forelse($groupedUsers as $instituteName => $instituteUsers)
                                <tr class="table-primary">
                                    <td colspan="7" class="fw-semibold">
                                        {{ $instituteName }} · {{ $instituteUsers->count() }} STEM Engineer{{ $instituteUsers->count() == 1 ? '' : 's' }}
                                    </td>
                                </tr>

                                @foreach($instituteUsers as $user)
                                    <tr>
                                        <td>{{ $rowNumber++ }}</td>
                                        <td>{{ $user->user_id }}</td>
                                        <td>{{ $user->name }}</td>
                                        <td>{{ $user->email }}</td>
                                        <td>{{ $user->institute ?? 'N/A' }}</td>

                                        <td>
                                            @if($user->status == 1)
                                                <span class="badge bg-success">Active</span>
                                            @else
                                                <span class="badge bg-danger">Inactive</span>
                                            @endif
                                        </td>

                                        <td>
                                            <button class="btn btn-sm btn-outline-primary"
                                                    data-bs-toggle="modal"
                                                    data-bs-target="#editUserModal{{ $user->id }}">
                                                Edit
                                            </button>

                                            <a href="{{ route('users.delete', $user->id) }}"
                                               class="btn btn-sm btn-outline-danger"
                                               onclick="return confirm('Are you sure you want to delete this STEM Engineer?')">
                                                Delete
                                            </a>
                                        </td>
                                    </tr>
                                @endforeach
                            @empty
                                <tr>
                                    <td colspan="7" class="text-center text-muted">
                                        No STEM Engineers found
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                    </div>

                </div>

                @if(method_exists($users, 'links'))
                    <div class="px-3 pb-3">
                        {{ $users->links('pagination::bootstrap-5') }}
                    </div>
                @endif
            </div>
            @endif

        </div>
    </div>
</div>

<div class="modal fade" id="addUserModal" tabindex="-1">
    <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content">

            <form method="POST" action="{{ route('users.store') }}">
                @csrf

                <div class="modal-header">
                    <h5 class="modal-title">Add STEM Engineer</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>

                <div class="modal-body">
                    <div class="row g-3">

                        <div class="col-md-6">
                            <label class="form-label">STEM Engineer ID</label>
                            <input type="text" name="user_id" class="form-control" placeholder="Example: STE001" required>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">Full Name</label>
                            <input type="text" name="name" class="form-control" placeholder="Enter full name" required>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">Email Address</label>
                            <input type="email" name="email" class="form-control" placeholder="Enter email address" required>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">Qualification</label>
                            <input type="text" name="qualification" class="form-control" placeholder="Example: B.Tech, M.Sc, B.Ed" required>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">Institute</label>

                            @if(session('user_role') == 'InstituteAdmin')
                                <input type="hidden" name="institute" value="{{ session('user_institute') }}">
                                <input type="text" class="form-control" value="{{ session('user_institute') }}" readonly>
                            @else
                                <select name="institute" class="form-select" required>
                                    <option value="">Select institute</option>
                                    @foreach($instituteOptions as $instituteOption)
                                        <option value="{{ $instituteOption }}">{{ $instituteOption }}</option>
                                    @endforeach
                                </select>
                            @endif
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">Password</label>
                            <input type="password" name="password" class="form-control" placeholder="Enter password" required>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">Status</label>
                            <select name="status" class="form-control" required>
                                <option value="1">Active</option>
                                <option value="0">Inactive</option>
                            </select>
                        </div>

                    </div>
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Save STEM Engineer</button>
                </div>

            </form>

        </div>
    </div>
</div>

@foreach($users as $user)
    <div class="modal fade" id="editUserModal{{ $user->id }}" tabindex="-1">
        <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
            <div class="modal-content">

                <form method="POST" action="{{ route('users.update', $user->id) }}">
                    @csrf

                    <div class="modal-header">
                        <h5 class="modal-title">Edit STEM Engineer</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>

                    <div class="modal-body">
                        <div class="row g-3">

                            <div class="col-md-6">
                                <label class="form-label">STEM Engineer ID</label>
                                <input type="text" name="user_id" class="form-control" value="{{ $user->user_id }}" required>
                            </div>

                            <div class="col-md-6">
                                <label class="form-label">Full Name</label>
                                <input type="text" name="name" class="form-control" value="{{ $user->name }}" required>
                            </div>

                            <div class="col-md-6">
                                <label class="form-label">Email Address</label>
                                <input type="email" name="email" class="form-control" value="{{ $user->email }}" required>
                            </div>

                            <div class="col-md-6">
                                <label class="form-label">Qualification</label>
                                <input type="text" name="qualification" class="form-control" value="{{ $user->qualification }}" required>
                            </div>

                            <div class="col-md-6">
                                <label class="form-label">Institute</label>

                                @if(session('user_role') == 'InstituteAdmin')
                                    <input type="hidden" name="institute" value="{{ session('user_institute') }}">
                                    <input type="text" class="form-control" value="{{ session('user_institute') }}" readonly>
                                @else
                                    <select name="institute" class="form-select" required>
                                        <option value="">Select institute</option>
                                        @foreach($instituteOptions as $instituteOption)
                                            <option value="{{ $instituteOption }}" {{ $user->institute === $instituteOption ? 'selected' : '' }}>
                                                {{ $instituteOption }}
                                            </option>
                                        @endforeach
                                        @if($user->institute && !collect($instituteOptions ?? [])->contains($user->institute))
                                            <option value="{{ $user->institute }}" selected>{{ $user->institute }}</option>
                                        @endif
                                    </select>
                                @endif
                            </div>

                            <div class="col-md-6">
                                <label class="form-label">Status</label>
                                <select name="status" class="form-control" required>
                                    <option value="1" {{ $user->status == 1 ? 'selected' : '' }}>Active</option>
                                    <option value="0" {{ $user->status == 0 ? 'selected' : '' }}>Inactive</option>
                                </select>
                            </div>

                        </div>
                    </div>

                    <div class="modal-footer">
                        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">Update STEM Engineer</button>
                    </div>

                </form>

            </div>
        </div>
    </div>
@endforeach

@endsection
