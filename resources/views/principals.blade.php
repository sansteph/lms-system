@extends('layouts.app')

@section('content')

<div class="container-fluid">
    <div class="row">
        @include('layouts.sidebar')

        <main class="col-md-10 col-lg-10 p-4">
            <div class="page-header d-flex justify-content-between align-items-center flex-wrap gap-3 mb-4">
                <div>
                    <h2 class="mb-1">Principal Management</h2>
                    <p class="text-muted mb-0">Assign one principal account to each institute.</p>
                </div>
                <button class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#addPrincipalModal">
                    <i class="fa-solid fa-plus me-1"></i> Add Principal
                </button>
            </div>

            @if(session('success'))
                <div class="alert alert-success">{{ session('success') }}</div>
            @endif

            @if(session('error'))
                <div class="alert alert-danger">{{ session('error') }}</div>
            @endif

            @if($errors->any())
                <div class="alert alert-danger">Please check the principal details and try again.</div>
            @endif

            <div class="card shadow border-0 mb-4">
                <div class="card-body">
                    <form method="GET" action="{{ route('principals') }}" class="row g-3 align-items-end">
                        <div class="col-lg-5">
                            <label class="form-label">Search</label>
                            <input type="search" name="search" value="{{ $search }}" class="form-control" placeholder="Name, email, contact, institute">
                        </div>
                        <div class="col-lg-4">
                            <label class="form-label">Institute</label>
                            <select name="institute" class="form-select">
                                <option value="">All institutes</option>
                                @foreach($institutes as $institute)
                                    <option value="{{ $institute }}" @selected($selectedInstitute == $institute)>{{ $institute }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-lg-3 d-flex gap-2">
                            <button class="btn btn-primary flex-fill">Filter</button>
                            <a href="{{ route('principals') }}" class="btn btn-outline-secondary">Clear</a>
                        </div>
                    </form>
                </div>
            </div>

            <div class="card shadow border-0">
                <div class="card-body">
                    <div class="table-responsive lms-table-shell">
                        <table class="table table-bordered table-hover align-middle lms-table-fit">
                            <thead class="table-light">
                                <tr>
                                    <th>Full Name</th>
                                    <th>Institute</th>
                                    <th>Contact</th>
                                    <th>Login Email</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($principals as $principal)
                                    <tr>
                                        <td>{{ $principal->name }}</td>
                                        <td>{{ $principal->institute }}</td>
                                        <td>{{ $principal->phone }}</td>
                                        <td>{{ $principal->email }}</td>
                                        <td>
                                            <span class="badge {{ $principal->status ? 'bg-success' : 'bg-secondary' }}">
                                                {{ $principal->status ? 'Active' : 'Inactive' }}
                                            </span>
                                        </td>
                                        <td>
                                            <button class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#principalEdit{{ $principal->id }}">Edit</button>
                                            <a href="{{ route('principals.delete', $principal->id) }}" class="btn btn-sm btn-outline-danger" onclick="return confirm('Delete this principal?')">Delete</a>
                                        </td>
                                    </tr>

                                    <div class="modal fade" id="principalEdit{{ $principal->id }}" tabindex="-1" aria-hidden="true">
                                        <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
                                            <div class="modal-content">
                                                <form method="POST" action="{{ route('principals.update', $principal->id) }}">
                                                    @csrf
                                                    <div class="modal-header">
                                                        <h5 class="modal-title">Edit Principal</h5>
                                                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                                    </div>
                                                    <div class="modal-body">
                                                        <div class="row g-3">
                                                            <div class="col-md-6">
                                                                <label class="form-label">Full name</label>
                                                                <input type="text" name="name" value="{{ $principal->name }}" class="form-control" required>
                                                            </div>
                                                            <div class="col-md-6">
                                                                <label class="form-label">Institute</label>
                                                                <select name="institute" class="form-select" required>
                                                                    @foreach($institutes as $institute)
                                                                        <option value="{{ $institute }}" @selected($principal->institute == $institute)>{{ $institute }}</option>
                                                                    @endforeach
                                                                </select>
                                                            </div>
                                                            <div class="col-md-6">
                                                                <label class="form-label">Contact number</label>
                                                                <input type="text" name="phone" value="{{ $principal->phone }}" class="form-control" required>
                                                            </div>
                                                            <div class="col-md-6">
                                                                <label class="form-label">Login email</label>
                                                                <input type="email" name="email" value="{{ $principal->email }}" class="form-control" required>
                                                            </div>
                                                            <div class="col-md-6">
                                                                <label class="form-label">New password</label>
                                                                <input type="password" name="password" class="form-control" minlength="8" placeholder="Leave blank to keep current password">
                                                            </div>
                                                            <div class="col-md-6">
                                                                <label class="form-label">Status</label>
                                                                <select name="status" class="form-select" required>
                                                                    <option value="1" @selected($principal->status)>Active</option>
                                                                    <option value="0" @selected(!$principal->status)>Inactive</option>
                                                                </select>
                                                            </div>
                                                        </div>
                                                    </div>
                                                    <div class="modal-footer">
                                                        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                                                        <button type="submit" class="btn btn-primary">Update Principal</button>
                                                    </div>
                                                </form>
                                            </div>
                                        </div>
                                    </div>
                                @empty
                                    <tr>
                                        <td colspan="6" class="text-center text-muted">No principals found.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    <div class="mt-3">
                        {{ $principals->links() }}
                    </div>
                </div>
            </div>
        </main>
    </div>
</div>

<div class="modal fade" id="addPrincipalModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content">
            <form method="POST" action="{{ route('principals.store') }}">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title">Add Principal</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">Full name</label>
                            <input type="text" name="name" value="{{ old('name') }}" class="form-control" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Institute</label>
                            <select name="institute" class="form-select" required>
                                <option value="">Select institute</option>
                                @foreach($institutes as $institute)
                                    <option value="{{ $institute }}" @selected(old('institute') == $institute)>{{ $institute }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Contact number</label>
                            <input type="text" name="phone" value="{{ old('phone') }}" class="form-control" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Login email</label>
                            <input type="email" name="email" value="{{ old('email') }}" class="form-control" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Password</label>
                            <input type="password" name="password" class="form-control" minlength="8" required>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Add Principal</button>
                </div>
            </form>
        </div>
    </div>
</div>

@endsection
