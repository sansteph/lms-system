@extends('layouts.app')

@section('content')

<div class="container-fluid">
    <div class="row">
        @include('layouts.sidebar')

        <div class="col-md-10 col-lg-10 p-4">

            <div class="page-header d-flex justify-content-between align-items-center flex-wrap gap-3 mb-4">
                <div>
                    <h2 class="mb-1">Institute Management</h2>
                    <p class="text-muted mb-0">Manage institutes, branches, contacts, and status.</p>
                </div>

                <button class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#addInstituteModal">
                    Add Institute
                </button>
            </div>

            @if(session('success'))
                <div class="alert alert-success">{{ session('success') }}</div>
            @endif

            @if($errors->any())
                <div class="alert alert-danger">Please fill all required fields correctly.</div>
            @endif

            <div class="row g-4 mb-4">
                <div class="col-md-3"><div class="dashboard-card"><h6>Total Institutes</h6><h2>{{ $institutes->count() }}</h2></div></div>
                <div class="col-md-3"><div class="dashboard-card"><h6>Active Institutes</h6><h2>{{ $institutes->where('status', 1)->count() }}</h2></div></div>
                <div class="col-md-3"><div class="dashboard-card"><h6>Total Branches</h6><h2>{{ $institutes->count() }}</h2></div></div>
                <div class="col-md-3"><div class="dashboard-card"><h6>Total Students</h6><h2>{{ $studentCount }}</h2></div></div>
            </div>

            <div class="card shadow border-0">
                <div class="card-body">

                    <form method="GET" action="{{ route('institutes') }}" class="row mb-3">
                        <div class="col-md-4">
                            <input type="text"
                                name="search"
                                class="form-control"
                                placeholder="Search by institute or location"
                                value="{{ request('search') }}">
                        </div>

                        <div class="col-md-2">
                            <button type="submit" class="btn btn-primary w-100">
                                Search
                            </button>
                        </div>
                    </form>

                    <table class="table table-bordered table-hover align-middle">
                        <thead class="table-light">
                            <tr>
                                <th>Sl. No</th>
                                <th>Institute ID</th>
                                <th>Institute Name</th>
                                <th>Location</th>
                                <th>Contact Person</th>
                                <th>Email</th>
                                <th>Status</th>
                                <th width="180">Actions</th>
                            </tr>
                        </thead>

                        <tbody>
                            @forelse($institutes as $index => $institute)
                                <tr>
                                    <td>{{ $index + 1 }}</td>
                                    <td>{{ $institute->institute_id }}</td>
                                    <td>{{ $institute->institute_name }}</td>
                                    <td>{{ $institute->location }}</td>
                                    <td>{{ $institute->contact_person }}</td>
                                    <td>{{ $institute->email }}</td>
                                    <td>
                                        @if($institute->status == 1)
                                            <span class="badge bg-success">Active</span>
                                        @else
                                            <span class="badge bg-danger">Inactive</span>
                                        @endif
                                    </td>
                                    <td>
                                        <button class="btn btn-sm btn-outline-primary"
                                                data-bs-toggle="modal"
                                                data-bs-target="#editInstituteModal{{ $institute->id }}">
                                            Edit
                                        </button>
                                        <a href="{{ route('institutes.delete', $institute->id) }}"
                                            class="btn btn-sm btn-outline-danger"
                                            onclick="return confirm('Are you sure you want to delete this institute?')">
                                            Delete
                                        </a>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="8" class="text-center text-muted">No institutes found</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>

                </div>
            </div>

        </div>
    </div>
</div>

<div class="modal fade" id="addInstituteModal" tabindex="-1">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content">

            <form method="POST" action="{{ route('institutes.store') }}">
                @csrf

                <div class="modal-header">
                    <h5 class="modal-title">Add Institute</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>

                <div class="modal-body">
                    <div class="row g-3">

                        <div class="col-md-6">
                            <label class="form-label">Institute ID</label>
                            <input type="text" name="institute_id" class="form-control" placeholder="Example: INS001" required>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">Institute Name</label>
                            <input type="text" name="institute_name" class="form-control" placeholder="Institute name" required>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">Location</label>
                            <input type="text" name="location" class="form-control" placeholder="City / Area" required>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">Contact Person</label>
                            <input type="text" name="contact_person" class="form-control" placeholder="Contact person name" required>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">Email Address</label>
                            <input type="email" name="email" class="form-control" placeholder="Email address" required>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">Phone Number</label>
                            <input type="text" name="phone" class="form-control" placeholder="Phone number" required>
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
                    <button type="submit" class="btn btn-primary">Save Institute</button>
                </div>

            </form>

        </div>
    </div>
</div>

@foreach($institutes as $institute)
<div class="modal fade" id="editInstituteModal{{ $institute->id }}" tabindex="-1">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content">

            <form method="POST" action="{{ route('institutes.update', $institute->id) }}">
                @csrf

                <div class="modal-header">
                    <h5 class="modal-title">Edit Institute</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>

                <div class="modal-body">
                    <div class="row g-3">

                        <div class="col-md-6">
                            <label class="form-label">Institute ID</label>
                            <input type="text" name="institute_id" class="form-control" value="{{ $institute->institute_id }}" required>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">Institute Name</label>
                            <input type="text" name="institute_name" class="form-control" value="{{ $institute->institute_name }}" required>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">Location</label>
                            <input type="text" name="location" class="form-control" value="{{ $institute->location }}" required>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">Contact Person</label>
                            <input type="text" name="contact_person" class="form-control" value="{{ $institute->contact_person }}" required>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">Email Address</label>
                            <input type="email" name="email" class="form-control" value="{{ $institute->email }}" required>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">Phone Number</label>
                            <input type="text" name="phone" class="form-control" value="{{ $institute->phone }}" required>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">Status</label>
                            <select name="status" class="form-control" required>
                                <option value="1" {{ $institute->status == 1 ? 'selected' : '' }}>Active</option>
                                <option value="0" {{ $institute->status == 0 ? 'selected' : '' }}>Inactive</option>
                            </select>
                        </div>

                    </div>
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Update Institute</button>
                </div>

            </form>

        </div>
    </div>
</div>
@endforeach

@endsection

