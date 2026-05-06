@extends('layouts.app')

@section('content')

<div class="container-fluid">
    <div class="row">

        @include('layouts.sidebar')

        <div class="col-md-10 col-lg-10 p-4">

            <div class="page-header d-flex justify-content-between align-items-center flex-wrap gap-3 mb-4">
                <div>
                    <h2 class="mb-1">Content Management</h2>
                    <p class="text-muted mb-0">
                        Upload PPT, PDF, videos and assign content with priority access.
                    </p>
                </div>

                <button class="btn btn-primary btn-sm"
                        data-bs-toggle="modal"
                        data-bs-target="#uploadContentModal">
                    Upload Content
                </button>
            </div>

            <div class="row g-4 mb-4">
                <div class="col-md-3">
                    <div class="dashboard-card">
                        <h6>Total Files</h6>
                        <h2>42</h2>
                    </div>
                </div>

                <div class="col-md-3">
                    <div class="dashboard-card">
                        <h6>PPT Files</h6>
                        <h2>15</h2>
                    </div>
                </div>

                <div class="col-md-3">
                    <div class="dashboard-card">
                        <h6>PDF Files</h6>
                        <h2>19</h2>
                    </div>
                </div>

                <div class="col-md-3">
                    <div class="dashboard-card">
                        <h6>Video Files</h6>
                        <h2>8</h2>
                    </div>
                </div>
            </div>

            <div class="card shadow border-0">
                <div class="card-body">

                    <div class="row mb-3">
                        <div class="col-md-4">
                            <input type="text" class="form-control" placeholder="Search by title or class">
                        </div>
                    </div>

                    <table class="table table-bordered table-hover align-middle">
                        <thead class="table-dark">
                            <tr>
                                <th>Sl. No</th>
                                <th>Title</th>
                                <th>Type</th>
                                <th>Assigned Class</th>
                                <th>Priority</th>
                                <th>Access Rule</th>
                                <th>Status</th>
                                <th width="180">Actions</th>
                            </tr>
                        </thead>

                        <tbody>
                            <tr>
                                <td>1</td>
                                <td>Introduction to AI</td>
                                <td><span class="badge bg-info">PDF</span></td>
                                <td>VIII - A</td>
                                <td>1</td>
                                <td>Sequential</td>
                                <td><span class="badge bg-success">Active</span></td>
                                <td>
                                    <button class="btn btn-sm btn-warning" data-bs-toggle="modal" data-bs-target="#editContentModal">Edit</button>
                                    <button class="btn btn-sm btn-info">View</button>
                                    <button class="btn btn-sm btn-danger" onclick="confirmDelete()">Delete</button>
                                </td>
                            </tr>

                            <tr>
                                <td>2</td>
                                <td>Robotics Basics</td>
                                <td><span class="badge bg-primary">PPT</span></td>
                                <td>IX - B</td>
                                <td>2</td>
                                <td>Sequential</td>
                                <td><span class="badge bg-success">Active</span></td>
                                <td>
                                    <button class="btn btn-sm btn-warning" data-bs-toggle="modal" data-bs-target="#editContentModal">Edit</button>
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

<!-- Upload Content Modal -->
<div class="modal fade" id="uploadContentModal" tabindex="-1">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content">

            <div class="modal-header">
                <h5 class="modal-title">Upload Content</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>

            <div class="modal-body">
                <form>
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">Content Title</label>
                            <input type="text" class="form-control" placeholder="Enter content title">
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">Content Type</label>
                            <select class="form-control">
                                <option>Select Content Type</option>
                                <option>PPT</option>
                                <option>PDF</option>
                                <option>Video</option>
                            </select>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">Assign Class</label>
                            <select class="form-control">
                                <option>Assign Class</option>
                                <option>VIII - A</option>
                                <option>IX - B</option>
                                <option>X - A</option>
                            </select>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">Priority Order</label>
                            <input type="number" class="form-control" placeholder="Example: 1">
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">Access Rule</label>
                            <select class="form-control">
                                <option>Sequential</option>
                                <option>Free Access</option>
                            </select>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">Upload File</label>
                            <input type="file" class="form-control">
                        </div>
                    </div>
                </form>
            </div>

            <div class="modal-footer">
                <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                <button class="btn btn-success">Upload</button>
            </div>

        </div>
    </div>
</div>

<!-- Edit Content Modal -->
<div class="modal fade" id="editContentModal" tabindex="-1">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content">

            <div class="modal-header">
                <h5 class="modal-title">Edit Content</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>

            <div class="modal-body">
                <form>
                    <input type="text" class="form-control mb-2" value="Introduction to AI">

                    <select class="form-control mb-2">
                        <option>PDF</option>
                        <option>PPT</option>
                        <option>Video</option>
                    </select>

                    <select class="form-control mb-2">
                        <option>VIII - A</option>
                        <option>IX - B</option>
                        <option>X - A</option>
                    </select>

                    <input type="number" class="form-control mb-2" value="1">

                    <select class="form-control mb-2">
                        <option>Sequential</option>
                        <option>Free Access</option>
                    </select>
                </form>
            </div>

            <div class="modal-footer">
                <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                <button class="btn btn-success">Update</button>
            </div>

        </div>
    </div>
</div>

<script>
function confirmDelete() {
    if(confirm("Are you sure you want to delete this content?")) {
        alert("Deleted (UI only)");
    }
}
</script>

@endsection