@extends('layouts.app')

@section('content')

<div class="container-fluid">
    <div class="row">

        @include('layouts.sidebar')

        <div class="col-md-9 col-lg-10 p-4">

            <div class="d-flex justify-content-between align-items-center mb-4">
                <h2>Notifications</h2>

                <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#createNotificationModal">
                    Create Notification
                </button>
            </div>

            <div class="row g-4 mb-4">
                <div class="col-md-3">
                    <div class="card shadow border-0 p-3">
                        <h6>Total Notifications</h6>
                        <h2>32</h2>
                    </div>
                </div>

                <div class="col-md-3">
                    <div class="card shadow border-0 p-3">
                        <h6>Sent Today</h6>
                        <h2>5</h2>
                    </div>
                </div>

                <div class="col-md-3">
                    <div class="card shadow border-0 p-3">
                        <h6>Teachers</h6>
                        <h2>18</h2>
                    </div>
                </div>

                <div class="col-md-3">
                    <div class="card shadow border-0 p-3">
                        <h6>Students</h6>
                        <h2>120</h2>
                    </div>
                </div>
            </div>

            <div class="card shadow border-0">
                <div class="card-body">

                    <input type="text" class="form-control mb-3" placeholder="Search notifications...">

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
    <div class="modal-dialog">
        <div class="modal-content">

            <div class="modal-header">
                <h5 class="modal-title">Create Notification</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>

            <div class="modal-body">
                <form>
                    <input type="text" class="form-control mb-2" placeholder="Title">
                    <textarea class="form-control mb-2" rows="3" placeholder="Message"></textarea>

                    <select class="form-control mb-2">
                        <option>Select Target</option>
                        <option>All Students</option>
                        <option>All Teachers</option>
                        <option>Specific Class</option>
                    </select>

                    <input type="date" class="form-control mb-2">
                </form>
            </div>

            <div class="modal-footer">
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