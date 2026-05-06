@extends('layouts.app')

@section('content')

<div class="container-fluid">
    <div class="row">

        @include('layouts.sidebar')

        <div class="col-md-9 col-lg-10 p-4">

            <div class="d-flex justify-content-between align-items-center mb-4">
                <h2>Assessment Management</h2>

                <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#createAssessmentModal">
                    Create Assessment
                </button>
            </div>

            <div class="row g-4 mb-4">
                <div class="col-md-3">
                    <div class="card shadow border-0 p-3">
                        <h6>Total Assessments</h6>
                        <h2>18</h2>
                    </div>
                </div>

                <div class="col-md-3">
                    <div class="card shadow border-0 p-3">
                        <h6>Student Assessments</h6>
                        <h2>12</h2>
                    </div>
                </div>

                <div class="col-md-3">
                    <div class="card shadow border-0 p-3">
                        <h6>Teacher Assessments</h6>
                        <h2>4</h2>
                    </div>
                </div>

                <div class="col-md-3">
                    <div class="card shadow border-0 p-3">
                        <h6>Active Links</h6>
                        <h2>9</h2>
                    </div>
                </div>
            </div>

            <div class="card shadow border-0">
                <div class="card-body">

                    <input type="text" class="form-control mb-3" placeholder="Search assessment...">

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
                                <td><button class="btn btn-sm btn-success">Active</button></td>
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
                                <td><button class="btn btn-sm btn-success">Active</button></td>
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
    <div class="modal-dialog">
        <div class="modal-content">

            <div class="modal-header">
                <h5 class="modal-title">Create Assessment</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>

            <div class="modal-body">
                <form>
                    <input type="text" class="form-control mb-2" placeholder="Assessment Title">

                    <select class="form-control mb-2">
                        <option>Select Assessment Type</option>
                        <option>Student Assessment</option>
                        <option>Teacher Assessment</option>
                    </select>

                    <select class="form-control mb-2">
                        <option>Select Class</option>
                        <option>VIII - A</option>
                        <option>IX - B</option>
                        <option>X - A</option>
                        <option>All Teachers</option>
                    </select>

                    <input type="number" class="form-control mb-2" placeholder="Total Marks">
                    <input type="text" class="form-control mb-2" placeholder="Duration e.g. 45 mins">

                    <select class="form-control mb-2">
                        <option>Question Paper Type</option>
                        <option>Upload Question Paper</option>
                        <option>Create Questions Later</option>
                    </select>

                    <input type="file" class="form-control mb-2">

                    <select class="form-control mb-2">
                        <option>Status</option>
                        <option>Active</option>
                        <option>Inactive</option>
                    </select>
                </form>
            </div>

            <div class="modal-footer">
                <button class="btn btn-success">Save Assessment</button>
            </div>

        </div>
    </div>
</div>

<!-- Edit Assessment Modal -->
<div class="modal fade" id="editAssessmentModal" tabindex="-1">
    <div class="modal-dialog">
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