@extends('layouts.app')

@section('content')

<div class="container-fluid">
    <div class="row">

        @include('layouts.sidebar')

        <div class="col-md-9 col-lg-10 p-4">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h2>Class Management</h2>

                <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addClassModal">
                    Add Class
                </button>
            </div>

            <div class="row g-4 mb-4">
                <div class="col-md-3">
                    <div class="card shadow border-0 p-3">
                        <h6>Total Classes</h6>
                        <h2>10</h2>
                    </div>
                </div>

                <div class="col-md-3">
                    <div class="card shadow border-0 p-3">
                        <h6>Total Sections</h6>
                        <h2>24</h2>
                    </div>
                </div>

                <div class="col-md-3">
                    <div class="card shadow border-0 p-3">
                        <h6>Assigned Teachers</h6>
                        <h2>18</h2>
                    </div>
                </div>

                <div class="col-md-3">
                    <div class="card shadow border-0 p-3">
                        <h6>Academic Year</h6>
                        <h2>2026</h2>
                    </div>
                </div>
            </div>

            <div class="card shadow border-0">
                <div class="card-body">

                    <input type="text" class="form-control mb-3" placeholder="Search class...">

                    <table class="table table-bordered table-hover align-middle">
                        <thead class="table-dark">
                            <tr>
                                <th>Sl. No</th>
                                <th>Class</th>
                                <th>Section</th>
                                <th>Class Teacher</th>
                                <th>Total Students</th>
                                <th>Status</th>
                                <th width="180">Actions</th>
                            </tr>
                        </thead>

                        <tbody>
                            <tr>
                                <td>1</td>
                                <td>VIII</td>
                                <td>A</td>
                                <td>Priya Nair</td>
                                <td>32</td>
                                <td><button class="btn btn-sm btn-success">Active</button></td>
                                <td>
                                    <button class="btn btn-sm btn-warning" data-bs-toggle="modal" data-bs-target="#editClassModal">Edit</button>
                                    <button class="btn btn-sm btn-info">View</button>
                                    <button class="btn btn-sm btn-danger" onclick="confirmDelete()">Delete</button>
                                </td>
                            </tr>

                            <tr>
                                <td>2</td>
                                <td>IX</td>
                                <td>B</td>
                                <td>Arun Kumar</td>
                                <td>29</td>
                                <td><button class="btn btn-sm btn-success">Active</button></td>
                                <td>
                                    <button class="btn btn-sm btn-warning" data-bs-toggle="modal" data-bs-target="#editClassModal">Edit</button>
                                    <button class="btn btn-sm btn-info">View</button>
                                    <button class="btn btn-sm btn-danger" onclick="confirmDelete()">Delete</button>
                                </td>
                            </tr>
                        </tbody>
                    </table>

                    <div class="mt-4">
                        <button class="btn btn-outline-primary">Promote Classes</button>
                        <button class="btn btn-outline-danger">Archive Class X</button>
                    </div>

                </div>
            </div>
        </div>
    </div>
</div>

<!-- Add Class Modal -->
<div class="modal fade" id="addClassModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">

            <div class="modal-header">
                <h5 class="modal-title">Add Class</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>

            <div class="modal-body">
                <form>
                    <input type="text" class="form-control mb-2" placeholder="Class Name">
                    <input type="text" class="form-control mb-2" placeholder="Section">
                    <input type="text" class="form-control mb-2" placeholder="Class Teacher">
                    <input type="text" class="form-control mb-2" placeholder="Academic Year">
                    <select class="form-control mb-2">
                        <option>Status</option>
                        <option>Active</option>
                        <option>Inactive</option>
                    </select>
                </form>
            </div>

            <div class="modal-footer">
                <button class="btn btn-success">Save Class</button>
            </div>

        </div>
    </div>
</div>

<!-- Edit Class Modal -->
<div class="modal fade" id="editClassModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">

            <div class="modal-header">
                <h5 class="modal-title">Edit Class</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>

            <div class="modal-body">
                <form>
                    <input type="text" class="form-control mb-2" value="VIII">
                    <input type="text" class="form-control mb-2" value="A">
                    <input type="text" class="form-control mb-2" value="Priya Nair">
                    <input type="text" class="form-control mb-2" value="2026">
                    <select class="form-control mb-2">
                        <option>Active</option>
                        <option>Inactive</option>
                    </select>
                </form>
            </div>

            <div class="modal-footer">
                <button class="btn btn-success">Update Class</button>
            </div>

        </div>
    </div>
</div>

<script>
function confirmDelete() {
    if(confirm("Are you sure you want to delete this class?")) {
        alert("Deleted (UI only)");
    }
}
</script>

@endsection