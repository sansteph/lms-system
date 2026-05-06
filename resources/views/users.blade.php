@extends('layouts.app')

@section('content')

<div class="container-fluid">
    <div class="row">

        @include('layouts.sidebar')

        <div class="col-md-10 col-lg-10 p-4">

            <div class="page-header d-flex justify-content-between align-items-center flex-wrap gap-3 mb-4">
                <div>
                    <h2 class="mb-1">User Management</h2>
                    <p class="text-muted mb-0">
                        Manage admins, teachers, roles, and account status.
                    </p>
                </div>

                <button class="btn btn-primary btn-sm"
                        data-bs-toggle="modal"
                        data-bs-target="#addUserModal">
                    Add User
                </button>
            </div>

            <div class="row g-4 mb-4">
                <div class="col-md-3">
                    <div class="dashboard-card">
                        <h6>Total Users</h6>
                        <h2>45</h2>
                    </div>
                </div>

                <div class="col-md-3">
                    <div class="dashboard-card">
                        <h6>Admins</h6>
                        <h2>3</h2>
                    </div>
                </div>

                <div class="col-md-3">
                    <div class="dashboard-card">
                        <h6>Teachers</h6>
                        <h2>25</h2>
                    </div>
                </div>

                <div class="col-md-3">
                    <div class="dashboard-card">
                        <h6>Active Users</h6>
                        <h2>42</h2>
                    </div>
                </div>
            </div>

            <div class="card shadow border-0">
                <div class="card-body">

                    <div class="row mb-3">
                        <div class="col-md-4">
                            <input type="text" class="form-control" placeholder="Search by name or email">
                        </div>
                    </div>

                    <table class="table table-bordered table-hover align-middle">
                        <thead class="table-dark">
                            <tr>
                                <th>Sl. No</th>
                                <th>User ID</th>
                                <th>Name</th>
                                <th>Email</th>
                                <th>Role</th>
                                <th>Status</th>
                                <th width="180">Actions</th>
                            </tr>
                        </thead>

                        <tbody>
                            <tr>
                                <td>1</td>
                                <td>USR001</td>
                                <td>Admin User</td>
                                <td>admin@example.com</td>
                                <td><span class="badge bg-danger">Admin</span></td>
                                <td><span class="badge bg-success">Active</span></td>
                                <td>
                                    <button class="btn btn-sm btn-warning" data-bs-toggle="modal" data-bs-target="#editUserModal">Edit</button>
                                    <button class="btn btn-sm btn-danger" onclick="confirmDelete()">Delete</button>
                                </td>
                            </tr>

                            <tr>
                                <td>2</td>
                                <td>USR002</td>
                                <td>Priya Nair</td>
                                <td>priya@example.com</td>
                                <td><span class="badge bg-primary">Teacher</span></td>
                                <td><span class="badge bg-success">Active</span></td>
                                <td>
                                    <button class="btn btn-sm btn-warning" data-bs-toggle="modal" data-bs-target="#editUserModal">Edit</button>
                                    <button class="btn btn-sm btn-danger" onclick="confirmDelete()">Delete</button>
                                </td>
                            </tr>
                        </tbody>
                    </table>

                </div>
            </div>

        </div>
    </div>
</div>

<!-- Add User Modal -->
<div class="modal fade" id="addUserModal" tabindex="-1">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content">

            <div class="modal-header">
                <h5 class="modal-title">Add User</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>

            <div class="modal-body">
                <form>
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">Full Name</label>
                            <input type="text" class="form-control" placeholder="Enter full name">
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">Email Address</label>
                            <input type="email" class="form-control" placeholder="Enter email address">
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">Role</label>
                            <select class="form-control">
                                <option>Select Role</option>
                                <option>Admin</option>
                                <option>Teacher</option>
                            </select>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">Password</label>
                            <input type="password" class="form-control" placeholder="Enter password">
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">Status</label>
                            <select class="form-control">
                                <option>Active</option>
                                <option>Inactive</option>
                            </select>
                        </div>
                    </div>
                </form>
            </div>

            <div class="modal-footer">
                <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                <button class="btn btn-success">Save User</button>
            </div>

        </div>
    </div>
</div>

<!-- Edit User Modal -->
<div class="modal fade" id="editUserModal" tabindex="-1">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content">

            <div class="modal-header">
                <h5 class="modal-title">Edit User</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>

            <div class="modal-body">
                <form>
                    <input type="text" class="form-control mb-2" value="Priya Nair">
                    <input type="email" class="form-control mb-2" value="priya@example.com">

                    <select class="form-control mb-2">
                        <option>Teacher</option>
                        <option>Admin</option>
                    </select>

                    <select class="form-control mb-2">
                        <option>Active</option>
                        <option>Inactive</option>
                    </select>
                </form>
            </div>

            <div class="modal-footer">
                <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                <button class="btn btn-success">Update User</button>
            </div>

        </div>
    </div>
</div>

<script>
function confirmDelete() {
    if(confirm("Are you sure you want to delete this user?")) {
        alert("Deleted (UI only)");
    }
}
</script>

@endsection