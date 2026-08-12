@extends('layouts.app')

@section('content')

<div class="container-fluid">
    <div class="row">
        @include('layouts.sidebar')

        <div class="col-md-10 col-lg-10 p-4">
            <div class="page-header mb-4">
                <h2 class="mb-1">Notifications</h2>
                <p class="text-muted mb-0">Create institute notices for STEM Engineers and students.</p>
            </div>

            @if(session('success'))
                <div class="alert alert-success">{{ session('success') }}</div>
            @endif

            @if($errors->any())
                <div class="alert alert-danger">{{ $errors->first() }}</div>
            @endif

            <div class="card shadow border-0 mb-4">
                <div class="card-body lms-report-filter-card">
                    <div class="d-flex justify-content-between align-items-start flex-wrap gap-3 mb-3">
                        <div>
                            <h5 class="mb-1">Apply filters for easy navigation</h5>
                            <p class="text-muted mb-0">Choose a date range to load the relevant notifications.</p>
                        </div>
                        <span class="badge bg-light text-dark border">Notification Filters</span>
                    </div>

                    <form method="GET" action="{{ route('notifications') }}">
                        <div class="lms-report-filter-grid">
                            <div class="lms-report-action-group">
                                <label class="form-label">From Date</label>
                                <input type="date"
                                       name="from_date"
                                       class="form-control"
                                       value="{{ request('from_date') }}">
                            </div>

                            <div class="lms-report-action-group">
                                <label class="form-label">To Date</label>
                                <input type="date"
                                       name="to_date"
                                       class="form-control"
                                       value="{{ request('to_date') }}">
                            </div>
                        </div>

                        <div class="lms-report-button-band">
                            <button type="submit" class="btn btn-primary lms-report-action-button">Apply Filters</button>
                            <a href="{{ route('notifications') }}" class="btn btn-outline-secondary lms-report-action-clear">Clear</a>
                        </div>
                    </form>
                </div>
            </div>

            <div class="card shadow border-0 mb-4">
                <div class="card-body">
                    <h5 class="mb-3">Create Notification</h5>
                    <form method="POST" action="{{ route('notifications.store') }}" class="row g-3">
                        @csrf
                        <div class="col-md-6">
                            <label class="form-label">Title</label>
                            <input type="text" name="title" class="form-control" required>
                        </div>

                        <div class="col-md-3">
                            <label class="form-label">Target</label>
                            <select name="target" class="form-select" required>
                                <option value="all">All</option>
                                <option value="teachers">STEM Engineers</option>
                                <option value="students">Students</option>
                            </select>
                        </div>

                        <div class="col-md-3">
                            <label class="form-label">Status</label>
                            <select name="status" class="form-select" required>
                                <option value="active">Active</option>
                                <option value="draft">Draft</option>
                                <option value="archived">Archived</option>
                            </select>
                        </div>

                        @if(session('user_role') == 'Admin')
                            <div class="col-md-4">
                                <label class="form-label">Institute</label>
                                <select name="institute" class="form-select">
                                    <option value="">All Institutes</option>
                                    @foreach($institutes as $institute)
                                        <option value="{{ $institute->institute_name }}">{{ $institute->institute_name }}</option>
                                    @endforeach
                                </select>
                            </div>
                        @endif

                        <div class="col-md-4">
                            <label class="form-label">Starts At</label>
                            <input type="date" name="starts_at" class="form-control">
                        </div>

                        <div class="col-md-4">
                            <label class="form-label">Expires At</label>
                            <input type="date" name="expires_at" class="form-control">
                        </div>

                        <div class="col-12">
                            <label class="form-label">Message</label>
                            <textarea name="message" class="form-control" rows="3" required></textarea>
                        </div>

                        <div class="col-12">
                            <button type="submit" class="btn btn-primary">Create Notification</button>
                        </div>
                    </form>
                </div>
            </div>

            @if(!($hasFilters ?? false))
                @include('partials.filter-placeholder')
            @else
                <div class="card shadow border-0">
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-bordered table-hover align-middle">
                                <thead class="table-light">
                                    <tr>
                                        <th>Title</th>
                                        <th>Target</th>
                                        <th>Institute</th>
                                        <th>Created</th>
                                        <th>Validity</th>
                                        <th>Status</th>
                                        <th>Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($notifications as $notification)
                                        <tr>
                                            <td>
                                                <strong>{{ $notification->title }}</strong>
                                                <div class="small text-muted">{{ Str::limit($notification->message, 120) }}</div>
                                            </td>
                                            <td>{{ ucwords(str_replace('_', ' ', $notification->target)) }}</td>
                                            <td>{{ $notification->institute ?: 'All Institutes' }}</td>
                                            <td>{{ $notification->created_at ? $notification->created_at->format('d M Y') : '-' }}</td>
                                            <td>
                                                {{ $notification->starts_at ? $notification->starts_at->format('d M Y') : 'Now' }}
                                                -
                                                {{ $notification->expires_at ? $notification->expires_at->format('d M Y') : 'No expiry' }}
                                            </td>
                                            <td>{{ ucfirst($notification->status) }}</td>
                                            <td>
                                                <form method="POST"
                                                      action="{{ route('notifications.delete', $notification->id) }}"
                                                      onsubmit="return confirm('Delete this notification?');">
                                                    @csrf
                                                    <button class="btn btn-sm btn-outline-danger">Delete</button>
                                                </form>
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="7" class="text-center text-muted">No notifications created.</td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            @endif
        </div>
    </div>
</div>

@endsection
