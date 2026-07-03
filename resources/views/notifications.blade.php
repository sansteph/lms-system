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
                        Send announcements and notifications to students and STEM Engineers.
                    </p>
                </div>

                <button class="btn btn-primary btn-sm"
                        data-bs-toggle="modal"
                        data-bs-target="#createNotificationModal">
                    Create Notification
                </button>

            </div>

            @if(session('success'))
                <div class="alert alert-success">
                    {{ session('success') }}
                </div>
            @endif

            @if($errors->any())
                <div class="alert alert-danger">
                    Please fill all required fields correctly.
                </div>
            @endif

            <div class="row g-4 mb-4">

                <div class="col-md-3">
                    <div class="dashboard-card">
                        <h6>Total Notifications</h6>
                        <h2>{{ $notifications->count() }}</h2>
                    </div>
                </div>

                <div class="col-md-3">
                    <div class="dashboard-card">
                        <h6>Students</h6>
                        <h2>{{ $notifications->where('target', 'Students')->count() }}</h2>
                    </div>
                </div>

                <div class="col-md-3">
                    <div class="dashboard-card">
                        <h6>STEM Engineers</h6>
                        <h2>{{ $notifications->where('target', 'Teachers')->count() }}</h2>
                    </div>
                </div>

                <div class="col-md-3">
                    <div class="dashboard-card">
                        <h6>Classes</h6>
                        <h2>{{ $notifications->where('target', 'Class')->count() }}</h2>
                    </div>
                </div>

            </div>

            <div class="card shadow border-0">

                <div class="card-body">

                    <form method="GET"
                          action="{{ route('notifications') }}"
                          class="row mb-3">

                        <div class="col-md-4">
                            <input type="text"
                                   name="search"
                                   class="form-control"
                                   placeholder="Search notifications"
                                   value="{{ request('search') }}">
                        </div>

                        <div class="col-md-2">
                            <button type="submit"
                                    class="btn btn-primary w-100">
                                Search
                            </button>
                        </div>

                    </form>

                    <table class="table table-bordered table-hover align-middle">

                        <thead class="table-light">
                            <tr>
                                <th>Sl. No</th>
                                <th>Title</th>
                                <th>Message</th>
                                <th>Institute</th>
                                <th>Target</th>
                                <th>Date</th>
                                <th width="180">Actions</th>
                            </tr>
                        </thead>

                        <tbody>

                            @forelse($notifications as $index => $notification)

                                <tr>

                                    <td>{{ $index + 1 }}</td>

                                    <td>{{ $notification->title }}</td>

                                    <td>{{ $notification->message }}</td>

                                    <td>{{ $notification->institute ?? 'All Institutes' }}</td>

                                    <td>

                                        @if($notification->target == 'Students')

                                            <span class="badge bg-primary">
                                                Students
                                            </span>

                                        @elseif($notification->target == 'Teachers')

                                            <span class="badge bg-warning text-dark">
                                                STEM Engineers
                                            </span>

                                        @else

                                            <span class="badge bg-info">
                                                Class
                                            </span>

                                        @endif

                                    </td>

                                    <td>{{ $notification->notification_date }}</td>

                                    <td>

                                       <button class="btn btn-sm btn-outline-primary"
                                            data-bs-toggle="modal"
                                            data-bs-target="#editNotificationModal{{ $notification->id }}">
                                            Edit
                                        </button>

                                        <a href="{{ route('notifications.delete', $notification->id) }}"
                                            class="btn btn-sm btn-outline-danger"
                                            onclick="return confirm('Are you sure you want to delete this notification?')">
                                            Delete
                                        </a>
                                    </td>

                                </tr>

                            @empty

                                <tr>
                                    <td colspan="7"
                                        class="text-center text-muted">
                                        No notifications found
                                    </td>
                                </tr>

                            @endforelse

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

            <form method="POST"
                  action="{{ route('notifications.store') }}">

                @csrf

                <div class="modal-header">

                    <h5 class="modal-title">
                        Create Notification
                    </h5>

                    <button type="button"
                            class="btn-close"
                            data-bs-dismiss="modal">
                    </button>

                </div>

                <div class="modal-body">

                    <div class="row g-3">

                        <div class="col-md-6">

                            <label class="form-label">
                                Notification Title
                            </label>

                            <input type="text"
                                   name="title"
                                   class="form-control"
                                   placeholder="Enter title"
                                   required>

                        </div>

                        @if(session('user_role') == 'Admin')

                            <div class="col-md-6">

                                <label class="form-label">
                                    Institute
                                </label>

                                <input type="text"
                                    name="institute"
                                    class="form-control"
                                    required>

                            </div>

                        @endif

                        <div class="col-md-6">

                            <label class="form-label">
                                Target Audience
                            </label>

                            <select name="target"
                                    class="form-control"
                                    required>

                                <option value="">
                                    Select Target
                                </option>

                                <option value="Students">
                                    Students
                                </option>

                                <option value="Teachers">
                                    STEM Engineers
                                </option>

                                <option value="Class">
                                    Class
                                </option>

                            </select>

                        </div>

                        <div class="col-12">

                            <label class="form-label">
                                Message
                            </label>

                            <textarea name="message"
                                      class="form-control"
                                      rows="4"
                                      placeholder="Enter notification message"
                                      required></textarea>

                        </div>

                        <div class="col-md-6">

                            <label class="form-label">
                                Schedule Date
                            </label>

                            <input type="date"
                                   name="notification_date"
                                   class="form-control"
                                   required>

                        </div>

                    </div>

                </div>

                <div class="modal-footer">

                    <button type="button"
                            class="btn btn-outline-secondary"
                            data-bs-dismiss="modal">

                        Cancel

                    </button>

                    <button type="submit"
                            class="btn btn-primary">

                        Send Notification

                    </button>

                </div>

            </form>

        </div>

    </div>

</div>
@foreach($notifications as $notification)
<div class="modal fade" id="editNotificationModal{{ $notification->id }}" tabindex="-1">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content">

            <form method="POST" action="{{ route('notifications.update', $notification->id) }}">
                @csrf

                <div class="modal-header">
                    <h5 class="modal-title">Edit Notification</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>

                <div class="modal-body">
                    <div class="row g-3">

                        <div class="col-md-6">
                            <label class="form-label">Notification Title</label>
                            <input type="text"
                                   name="title"
                                   class="form-control"
                                   value="{{ $notification->title }}"
                                   required>
                        </div>

                        @if(session('user_role') == 'Admin')

                            <div class="col-md-6">

                                <label class="form-label">
                                    Institute
                                </label>

                                <input type="text"
                                    name="institute"
                                    class="form-control"
                                    value="{{ $notification->institute }}"
                                    required>

                            </div>

                        @endif

                        <div class="col-md-6">
                            <label class="form-label">Target Audience</label>
                            <select name="target" class="form-control" required>
                                <option value="Students" {{ $notification->target == 'Students' ? 'selected' : '' }}>
                                    Students
                                </option>
                                <option value="Teachers" {{ $notification->target == 'Teachers' ? 'selected' : '' }}>
                                    STEM Engineers
                                </option>
                                <option value="Class" {{ $notification->target == 'Class' ? 'selected' : '' }}>
                                    Class
                                </option>
                            </select>
                        </div>

                        <div class="col-12">
                            <label class="form-label">Message</label>
                            <textarea name="message"
                                      class="form-control"
                                      rows="4"
                                      required>{{ $notification->message }}</textarea>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">Schedule Date</label>
                            <input type="date"
                                   name="notification_date"
                                   class="form-control"
                                   value="{{ $notification->notification_date }}"
                                   required>
                        </div>

                    </div>
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Update Notification</button>
                </div>

            </form>

        </div>
    </div>
</div>
@endforeach

@endsection
