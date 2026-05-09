@extends('layouts.app')

@section('content')

<div class="container-fluid">
    <div class="row">

        @include('layouts.student-sidebar')

        <div class="col-md-10 col-lg-10 p-4">

            <div class="page-header mb-4">
                <h2 class="mb-1">Notifications</h2>
                <p class="text-muted mb-0">
                    View assessment reminders, badge updates, and certificate announcements.
                </p>
            </div>

            <div class="row g-4 mb-4">
                <div class="col-md-3">
                    <div class="dashboard-card">
                        <h6>Total Notifications</h6>
                        <h2>8</h2>
                    </div>
                </div>

                <div class="col-md-3">
                    <div class="dashboard-card">
                        <h6>Unread</h6>
                        <h2>3</h2>
                    </div>
                </div>

                <div class="col-md-3">
                    <div class="dashboard-card">
                        <h6>Assessments</h6>
                        <h2>4</h2>
                    </div>
                </div>

                <div class="col-md-3">
                    <div class="dashboard-card">
                        <h6>Achievements</h6>
                        <h2>2</h2>
                    </div>
                </div>
            </div>

            <div class="card shadow border-0">
                <div class="card-body">

                    <table class="table table-bordered table-hover align-middle">
                        <thead class="table-light">
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
                                <td>Your AI Fundamentals assessment is pending.</td>
                                <td><span class="badge bg-warning text-dark">Assessment</span></td>
                                <td>09-05-2026</td>
                                <td><span class="badge bg-danger">Unread</span></td>
                            </tr>

                            <tr>
                                <td>2</td>
                                <td>Badge Earned</td>
                                <td>You earned the Robotics Explorer badge.</td>
                                <td><span class="badge bg-success">Achievement</span></td>
                                <td>08-05-2026</td>
                                <td><span class="badge bg-success">Read</span></td>
                            </tr>

                            <tr>
                                <td>3</td>
                                <td>Certificate Eligibility</td>
                                <td>You are close to becoming certificate eligible.</td>
                                <td><span class="badge bg-primary">Certificate</span></td>
                                <td>07-05-2026</td>
                                <td><span class="badge bg-success">Read</span></td>
                            </tr>
                        </tbody>
                    </table>

                    <div class="alert alert-info mt-3 mb-0">
                        Notifications will later be connected to admin announcements and achievement updates.
                    </div>

                </div>
            </div>

        </div>

    </div>
</div>

@endsection