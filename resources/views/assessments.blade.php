@extends('layouts.app')

@section('content')

<div class="container-fluid">
    <div class="row">

        @include('layouts.sidebar')

        <div class="col-md-10 col-lg-10 p-4">

            <div class="page-header d-flex justify-content-between align-items-center flex-wrap gap-3 mb-4">
                <div>
                    <h2 class="mb-1">Assessment Management</h2>
                    <p class="text-muted mb-0">
                        Create student and teacher assessments, generate links, and manage status.
                    </p>
                </div>

                <button class="btn btn-primary btn-sm"
                        data-bs-toggle="modal"
                        data-bs-target="#createAssessmentModal">
                    Create Assessment
                </button>
            </div>

            <div class="row g-4 mb-4">
                <div class="col-md-3">
                    <div class="dashboard-card">
                        <h6>Total Assessments</h6>
                        <h2>18</h2>
                    </div>
                </div>

                <div class="col-md-3">
                    <div class="dashboard-card">
                        <h6>Student Assessments</h6>
                        <h2>12</h2>
                    </div>
                </div>

                <div class="col-md-3">
                    <div class="dashboard-card">
                        <h6>Teacher Assessments</h6>
                        <h2>4</h2>
                    </div>
                </div>

                <div class="col-md-3">
                    <div class="dashboard-card">
                        <h6>Active Links</h6>
                        <h2>9</h2>
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
                                <th>Assessment Title</th>
                                <th>Type</th>
                                <th>Class</th>
                                <th>Total Marks</th>
                                <th>Duration</th>
                                <th>Status</th>
                                <th width="230">Actions</th>
                            </tr>
                        </thead>

                        <tbody>
                            <tr>
                                <td>1</td>
                                <td>AI Basics Test</td>
                                <td><span class="badge bg-primary">Student</span></td>
                                <td>VIII - A</td>
                                <td>50</td>
                                <td>45 mins</td>
                                <td><span class="badge bg-success">Active</span></td>
                                <td>
                                    <button class="btn btn-sm btn-info" onclick="copyAssessmentLink()">Generate Link</button>
                                    <button class="btn btn-sm btn-warning" data-bs-toggle="modal" data-bs-target="#editAssessmentModal">Edit</button>
                                    <button class="btn btn-sm btn-danger" onclick="confirmDelete()">Delete</button>
                                </td>
                            </tr>

                            <tr>
                                <td>2</td>
                                <td>Teacher Monthly Review</td>
                                <td><span class="badge bg-warning text-dark">Teacher</span></td>
                                <td>All Teachers</td>
                                <td>100</td>
                                <td>60 mins</td>
                                <td><span class="badge bg-success">Active</span></td>
                                <td>
                                    <button class="btn btn-sm btn-info" onclick="copyAssessmentLink()">Generate Link</button>
                                    <button class="btn btn-sm btn-warning" data-bs-toggle="modal" data-bs-target="#editAssessmentModal">Edit</button>
                                    <button class="btn btn-sm btn-danger" onclick="confirmDelete()">Delete</button>
                                </td>
                            </tr>
                        </tbody>
                    </table>

                    <div class="alert alert-info mt-3 mb-0">
                        Assessment link format example:
                        <strong>/student-assessment?institute_id=INS001&student_id=STU001&assessment_id=ASM001</strong>
                    </div>

                </div>
            </div>

        </div>
    </div>
</div>

<!-- Create Assessment Modal -->
<div class="modal fade" id="createAssessmentModal" tabindex="-1">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content">

            <div class="modal-header">
                <h5 class="modal-title">Create Assessment</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>

            <div class="modal-body">
                <form>
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">Assessment Title</label>
                            <input type="text" class="form-control" placeholder="Enter assessment title">
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">Assessment Type</label>
                            <select class="form-control">
                                <option>Select Assessment Type</option>
                                <option>Student Assessment</option>
                                <option>Teacher Assessment</option>
                            </select>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">Class / Group</label>
                            <select class="form-control">
                                <option>Select Class</option>
                                <option>VIII - A</option>
                                <option>IX - B</option>
                                <option>X - A</option>
                                <option>All Teachers</option>
                            </select>
                        </div>

                        <div class="col-md-3">
                            <label class="form-label">Total Marks</label>
                            <input type="number" class="form-control" placeholder="50">
                        </div>

                        <div class="col-md-3">
                            <label class="form-label">Duration</label>
                            <input type="text" class="form-control" placeholder="45 mins">
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">Question Paper Type</label>
                            <select class="form-control">
                                <option>Upload Question Paper</option>
                                <option>Create Questions Later</option>
                            </select>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">Question Paper File</label>
                            <input type="file" class="form-control">
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
                <button class="btn btn-success">Save Assessment</button>
            </div>

        </div>
    </div>
</div>

<!-- Edit Assessment Modal -->
<div class="modal fade" id="editAssessmentModal" tabindex="-1">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content">

            <div class="modal-header">
                <h5 class="modal-title">Edit Assessment</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>

            <div class="modal-body">
                <form>
                    <input type="text" class="form-control mb-2" value="AI Basics Test">

                    <select class="form-control mb-2">
                        <option>Student Assessment</option>
                        <option>Teacher Assessment</option>
                    </select>

                    <select class="form-control mb-2">
                        <option>VIII - A</option>
                        <option>IX - B</option>
                        <option>X - A</option>
                        <option>All Teachers</option>
                    </select>

                    <input type="number" class="form-control mb-2" value="50">
                    <input type="text" class="form-control mb-2" value="45 mins">

                    <select class="form-control mb-2">
                        <option>Active</option>
                        <option>Inactive</option>
                    </select>
                </form>
            </div>

            <div class="modal-footer">
                <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                <button class="btn btn-success">Update Assessment</button>
            </div>

        </div>
    </div>
</div>

<script>
function confirmDelete() {
    if(confirm("Are you sure you want to delete this assessment?")) {
        alert("Deleted (UI only)");
    }
}

function copyAssessmentLink() {
    alert("Assessment link generated and copied (UI only)");
}
</script>

@endsection