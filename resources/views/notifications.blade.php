@extends('layouts.app')

@section('content')

<div class="container-fluid">
    <div class="row">

        @include('layouts.sidebar')

        <div class="col-md-10 col-lg-10 p-4">

            <div class="page-header d-flex justify-content-between align-items-center flex-wrap gap-3 mb-4">
                <div>
                    <h2 class="mb-1">Notifications</h2>
                    <p class="text-muted mb-0">
                        Send announcements and notifications to students and teachers.
                    </p>
                </div>

                <button class="btn btn-primary btn-sm"
                        data-bs-toggle="modal"
                        data-bs-target="#createNotificationModal">
                    Create Notification
                </button>
            </div>

            <div class="row g-4 mb-4">
                <div class="col-md-3">
                    <div class="dashboard-card">
                        <h6>Total Notifications</h6>
                        <h2>32</h2>
                    </div>
                </div>

                <div class="col-md-3">
                    <div class="dashboard-card">
                        <h6>Sent Today</h6>
                        <h2>5</h2>
                    </div>
                </div>

                <div class="col-md-3">
                    <div class="dashboard-card">
                        <h6>Teachers</h6>
                        <h2>18</h2>
                    </div>
                </div>

                <div class="col-md-3">
                    <div class="dashboard-card">
                        <h6>Students</h6>
                        <h2>120</h2>
                    </div>
                </div>
            </div>

            <div class="card shadow border-0">
                <div class="card-body">

                    <div class="row mb-3">
                        <div class="col-md-4">
                            <input type="text" class="form-control" placeholder="Search notifications">
                        </div>
                    </div>
                    <table class="table table-bordered table-hover align-middle">
                        <thead class="table-dark">
                            <tr>
                                <th>Sl. No</th>
                                <th>Title</th>
                                <th>Message</th>
                                <th>Target</th>
                                <th>Date</th>
                                <th width="180">Actions</th>
                            </tr>
                        </thead>

                        <tbody>
                            <tr>
                                <td>1</td>
                                <td>Exam Notice</td>
                                <td>Mid-term exams start next week</td>
                                <td><span class="badge bg-primary">Students</span></td>
                                <td>05-05-2026</td>
                                <td>
                                    <button class="btn btn-sm btn-info">View</button>
                                    <button class="btn btn-sm btn-danger" onclick="confirmDelete()">Delete</button>
                                </td>
                            </tr>

                            <tr>
                                <td>2</td>
                                <td>Staff Meeting</td>
                                <td>Meeting scheduled on Friday</td>
                                <td><span class="badge bg-warning text-dark">Teachers</span></td>
                                <td>05-05-2026</td>
                                <td>
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

<!-- Create Notification Modal -->
<div class="modal fade" id="createNotificationModal" tabindex="-1">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content">

            <div class="modal-header">
                <h5 class="modal-title">Create Notification</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>

            <div class="modal-body">
                <form>
                    <div class="row g-3">

                        <div class="col-md-6">
                            <label class="form-label">Notification Title</label>
                            <input type="text" class="form-control" placeholder="Enter title">
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">Target Audience</label>
                            <select class="form-control">
                                <option>Select Target</option>
                                <option>All Students</option>
                                <option>All Teachers</option>
                                <option>Specific Class</option>
                            </select>
                        </div>

                        <div class="col-12">
                            <label class="form-label">Message</label>
                            <textarea class="form-control" rows="4" placeholder="Enter notification message"></textarea>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">Schedule Date</label>
                            <input type="date" class="form-control">
                        </div>

                    </div>
                </form>
            </div>

            <div class="modal-footer">
                <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                <button class="btn btn-success">Send Notification</button>
            </div>

        </div>
    </div>
</div>

<script>
function confirmDelete() {
    if(confirm("Are you sure you want to delete this notification?")) {
        alert("Deleted (UI only)");
    }
}
</script>

@endsection