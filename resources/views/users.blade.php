@extends('layouts.app')

@section('content')

<div class="container-fluid">
    <div class="row">

        @include('layouts.sidebar')

        <div class="col-md-9 col-lg-10 p-4">

            <div class="d-flex justify-content-between align-items-center mb-4">
                <h2>User Management</h2>

                <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addUserModal">
                    Add User
                </button>
            </div>

            <div class="row g-4 mb-4">
                <div class="col-md-3">
                    <div class="card shadow border-0 p-3">
                        <h6>Total Users</h6>
                        <h2>45</h2>
                    </div>
                </div>

                <div class="col-md-3">
                    <div class="card shadow border-0 p-3">
                        <h6>Admins</h6>
                        <h2>3</h2>
                    </div>
                </div>

                <div class="col-md-3">
                    <div class="card shadow border-0 p-3">
                        <h6>Teachers</h6>
                        <h2>25</h2>
                    </div>
                </div>

                <div class="col-md-3">
                    <div class="card shadow border-0 p-3">
                        <h6>Active Users</h6>
                        <h2>42</h2>
                    </div>
                </div>
            </div>

            <div class="card shadow border-0">
                <div class="card-body">

                    <input type="text" class="form-control mb-3" placeholder="Search user...">

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
                                <td><button class="btn btn-sm btn-success">Active</button></td>
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
                                <td><button class="btn btn-sm btn-success">Active</button></td>
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
    <div class="modal-dialog">
        <div class="modal-content">

            <div class="modal-header">
                <h5 class="modal-title">Add User</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>

            <div class="modal-body">
                <form>
                    <input type="text" class="form-control mb-2" placeholder="Full Name">
                    <input type="email" class="form-control mb-2" placeholder="Email Address">

                    <select class="form-control mb-2">
                        <option>Select Role</option>
                        <option>Admin</option>
                        <option>Teacher</option>
                    </select>

                    <input type="password" class="form-control mb-2" placeholder="Password">

                    <select class="form-control mb-2">
                        <option>Status</option>
                        <option>Active</option>
                        <option>Inactive</option>
                    </select>
                </form>
            </div>

            <div class="modal-footer">
                <button class="btn btn-success">Save User</button>
            </div>

        </div>
    </div>
</div>

<!-- Edit User Modal -->
<div class="modal fade" id="editUserModal" tabindex="-1">
    <div class="modal-dialog">
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