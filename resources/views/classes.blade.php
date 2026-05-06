@extends('layouts.app')

@section('content')

<div class="container-fluid">
    <div class="row">

        @include('layouts.sidebar')

        <div class="col-md-10 col-lg-10 p-4">
            <div class="page-header d-flex justify-content-between align-items-center flex-wrap gap-3 mb-4">
                <div>
                    <h2 class="mb-1">Class Management</h2>
                    <p class="text-muted mb-0">
                        Manage classes, sections, teachers, and academic year details.
                    </p>
                </div>

                <button class="btn btn-primary btn-sm"
                        data-bs-toggle="modal"
                        data-bs-target="#addClassModal">
                    Add Class
                </button>
            </div>

            <div class="row g-4 mb-4">
                <div class="col-md-3">
                    <div class="dashboard-card">
                        <h6>Total Classes</h6>
                        <h2>10</h2>
                    </div>
                </div>

                <div class="col-md-3">
                    <div class="dashboard-card">
                        <h6>Total Sections</h6>
                        <h2>24</h2>
                    </div>
                </div>

                <div class="col-md-3">
                    <div class="dashboard-card">
                        <h6>Assigned Teachers</h6>
                        <h2>18</h2>
                    </div>
                </div>

                <div class="col-md-3">
                    <div class="dashboard-card">
                        <h6>Academic Year</h6>
                        <h2>2026</h2>
                    </div>
                </div>
            </div>

            <div class="card shadow border-0">
                <div class="card-body">

                    <div class="row mb-3">
                        <div class="col-md-4">
                            <input type="text" class="form-control" placeholder="Search by class or teacher">
                        </div>
                    </div>

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
                                <td><span class="badge bg-success">Active</span></td>
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
                                <td><span class="badge bg-success">Active</span></td>
                                <td>
                                    <button class="btn btn-sm btn-warning" data-bs-toggle="modal" data-bs-target="#editClassModal">Edit</button>
                                    <button class="btn btn-sm btn-info">View</button>
                                    <button class="btn btn-sm btn-danger" onclick="confirmDelete()">Delete</button>
                                </td>
                            </tr>
                        </tbody>
                    </table>

                    <div class="mt-4 d-flex gap-2 flex-wrap">
                        <button class="btn btn-primary">Promote Classes</button>
                        <button class="btn btn-danger">Archive Class X</button>
                    </div>

                </div>
            </div>
        </div>
    </div>
</div>

<!-- Add Class Modal -->
<div class="modal fade" id="addClassModal" tabindex="-1">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content">

            <div class="modal-header">
                <h5 class="modal-title">Add Class</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>

            <div class="modal-body">
                <form>
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">Class Name</label>
                            <input type="text" class="form-control" placeholder="Example: VIII">
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">Section</label>
                            <input type="text" class="form-control" placeholder="Example: A">
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">Class Teacher</label>
                            <input type="text" class="form-control" placeholder="Teacher name">
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">Academic Year</label>
                            <input type="text" class="form-control" placeholder="2026">
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
                <button class="btn btn-success">Save Class</button>
            </div>
        </div>
    </div>
</div>

<!-- Edit Class Modal -->
<div class="modal fade" id="editClassModal" tabindex="-1">
    <div class="modal-dialog modal-lg modal-dialog-centered">
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
                <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
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