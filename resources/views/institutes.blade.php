@extends('layouts.app')

@section('content')

<div class="container-fluid">
    <div class="row">

        @include('layouts.sidebar')

        <div class="col-md-9 col-lg-10 p-4">

            <div class="d-flex justify-content-between align-items-center mb-4">
                <h2>Institute Management</h2>

                <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addInstituteModal">
                    Add Institute
                </button>
            </div>

            <div class="row g-4 mb-4">
                <div class="col-md-3">
                    <div class="card shadow border-0 p-3">
                        <h6>Total Institutes</h6>
                        <h2>8</h2>
                    </div>
                </div>

                <div class="col-md-3">
                    <div class="card shadow border-0 p-3">
                        <h6>Active Institutes</h6>
                        <h2>7</h2>
                    </div>
                </div>

                <div class="col-md-3">
                    <div class="card shadow border-0 p-3">
                        <h6>Total Branches</h6>
                        <h2>12</h2>
                    </div>
                </div>

                <div class="col-md-3">
                    <div class="card shadow border-0 p-3">
                        <h6>Total Students</h6>
                        <h2>250</h2>
                    </div>
                </div>
            </div>

            <div class="card shadow border-0">
                <div class="card-body">

                    <input type="text" class="form-control mb-3" placeholder="Search institute...">

                    <table class="table table-bordered table-hover align-middle">
                        <thead class="table-dark">
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
                            <tr>
                                <td>1</td>
                                <td>INS001</td>
                                <td>ABC School</td>
                                <td>Bangalore</td>
                                <td>Ramesh Kumar</td>
                                <td>abcschool@example.com</td>
                                <td><button class="btn btn-sm btn-success">Active</button></td>
                                <td>
                                    <button class="btn btn-sm btn-warning" data-bs-toggle="modal" data-bs-target="#editInstituteModal">Edit</button>
                                    <button class="btn btn-sm btn-info">View</button>
                                    <button class="btn btn-sm btn-danger" onclick="confirmDelete()">Delete</button>
                                </td>
                            </tr>

                            <tr>
                                <td>2</td>
                                <td>INS002</td>
                                <td>Bright Future Academy</td>
                                <td>Mysore</td>
                                <td>Anitha Rao</td>
                                <td>brightfuture@example.com</td>
                                <td><button class="btn btn-sm btn-success">Active</button></td>
                                <td>
                                    <button class="btn btn-sm btn-warning" data-bs-toggle="modal" data-bs-target="#editInstituteModal">Edit</button>
                                    <button class="btn btn-sm btn-info">View</button>
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

<!-- Add Institute Modal -->
<div class="modal fade" id="addInstituteModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">

            <div class="modal-header">
                <h5 class="modal-title">Add Institute</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>

            <div class="modal-body">
                <form>
                    <input type="text" class="form-control mb-2" placeholder="Institute ID">
                    <input type="text" class="form-control mb-2" placeholder="Institute Name">
                    <input type="text" class="form-control mb-2" placeholder="Location">
                    <input type="text" class="form-control mb-2" placeholder="Contact Person">
                    <input type="email" class="form-control mb-2" placeholder="Email Address">
                    <input type="text" class="form-control mb-2" placeholder="Phone Number">

                    <select class="form-control mb-2">
                        <option>Status</option>
                        <option>Active</option>
                        <option>Inactive</option>
                    </select>
                </form>
            </div>

            <div class="modal-footer">
                <button class="btn btn-success">Save Institute</button>
            </div>

        </div>
    </div>
</div>

<!-- Edit Institute Modal -->
<div class="modal fade" id="editInstituteModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">

            <div class="modal-header">
                <h5 class="modal-title">Edit Institute</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>

            <div class="modal-body">
                <form>
                    <input type="text" class="form-control mb-2" value="INS001">
                    <input type="text" class="form-control mb-2" value="ABC School">
                    <input type="text" class="form-control mb-2" value="Bangalore">
                    <input type="text" class="form-control mb-2" value="Ramesh Kumar">
                    <input type="email" class="form-control mb-2" value="abcschool@example.com">
                    <input type="text" class="form-control mb-2" value="9876543210">

                    <select class="form-control mb-2">
                        <option>Active</option>
                        <option>Inactive</option>
                    </select>
                </form>
            </div>

            <div class="modal-footer">
                <button class="btn btn-success">Update Institute</button>
            </div>

        </div>
    </div>
</div>

<script>
function confirmDelete() {
    if(confirm("Are you sure you want to delete this institute?")) {
        alert("Deleted (UI only)");
    }
}
</script>

@endsection