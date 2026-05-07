@extends('layouts.app')

@section('content')

<div class="container-fluid">
    <div class="row">

        @include('layouts.teacher-sidebar')

        <div class="col-md-10 col-lg-10 p-4">

            <div class="page-header mb-4">
                <h2 class="mb-1">Notifications</h2>
                <p class="text-muted mb-0">
                    View announcements and important LMS updates.
                </p>
            </div>

            <div class="row g-4 mb-4">
                <div class="col-md-3">
                    <div class="dashboard-card">
                        <h6>Total Notifications</h6>
                        <h2>18</h2>
                    </div>
                </div>

                <div class="col-md-3">
                    <div class="dashboard-card">
                        <h6>Unread</h6>
                        <h2>4</h2>
                    </div>
                </div>

                <div class="col-md-3">
                    <div class="dashboard-card">
                        <h6>Assessments</h6>
                        <h2>6</h2>
                    </div>
                </div>

                <div class="col-md-3">
                    <div class="dashboard-card">
                        <h6>General</h6>
                        <h2>12</h2>
                    </div>
                </div>
            </div>

            <div class="card shadow border-0">
                <div class="card-body">

                    <div class="row mb-3">
                        <div class="col-md-4">
                            <input type="text"
                                   class="form-control"
                                   placeholder="Search notifications">
                        </div>

                        <div class="col-md-3">
                            <select class="form-control">
                                <option>Filter by Type</option>
                                <option>General</option>
                                <option>Assessment</option>
                                <option>Content</option>
                            </select>
                        </div>
                    </div>

                    <table class="table table-bordered table-hover align-middle">
                        <thead class="table-dark">
                            <tr>
                                <th>Sl. No</th>
                                <th>Title</th>
                                <th>Message</th>
                                <th>Type</th>
                                <th>Date</th>
                                <th>Status</th>
                            </tr>
                        </thead>

                        <tbody>
                            <tr>
                                <td>1</td>
                                <td>Assessment Reminder</td>
                                <td>Please complete assessment review by Friday.</td>
                                <td><span class="badge bg-warning text-dark">Assessment</span></td>
                                <td>05-05-2026</td>
                                <td><span class="badge bg-danger">Unread</span></td>
                            </tr>

                            <tr>
                                <td>2</td>
                                <td>New Content Assigned</td>
                                <td>New AI content has been assigned to Class VIII.</td>
                                <td><span class="badge bg-primary">Content</span></td>
                                <td>05-05-2026</td>
                                <td><span class="badge bg-success">Read</span></td>
                            </tr>

                            <tr>
                                <td>3</td>
                                <td>Staff Meeting</td>
                                <td>Teacher meeting scheduled for Monday morning.</td>
                                <td><span class="badge bg-info">General</span></td>
                                <td>04-05-2026</td>
                                <td><span class="badge bg-success">Read</span></td>
                            </tr>
                        </tbody>
                    </table>

                </div>
            </div>

        </div>

    </div>
</div>

@endsection