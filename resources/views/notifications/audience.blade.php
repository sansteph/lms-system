@extends('layouts.app')

@section('content')

<div class="container-fluid">
    <div class="row">
        @if($sidebar == 'teacher')
            @include('layouts.teacher-sidebar')
        @else
            @include('layouts.student-sidebar')
        @endif

        <div class="col-md-10 col-lg-10 p-4">
            <div class="page-header mb-4">
                <h2 class="mb-1">{{ $title }}</h2>
                <p class="text-muted mb-0">Latest updates shared by the institute and LMS admin team.</p>
            </div>

            <div class="card shadow border-0 mb-4">
                <div class="card-body lms-report-filter-card">
                    <div class="d-flex justify-content-between align-items-start flex-wrap gap-3 mb-3">
                        <div>
                            <h5 class="mb-1">Apply filters for easy navigation</h5>
                            <p class="text-muted mb-0">Choose a date range to load the relevant notifications.</p>
                        </div>
                        <span class="badge bg-light text-dark border">Notification Filters</span>
                    </div>

                    <form method="GET"
                          action="{{ $sidebar == 'teacher' ? route('teacher.notifications') : route('student.notifications') }}">
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
                            <a href="{{ $sidebar == 'teacher' ? route('teacher.notifications') : route('student.notifications') }}"
                               class="btn btn-outline-secondary lms-report-action-clear">
                                Clear
                            </a>
                        </div>
                    </form>
                </div>
            </div>

            @if($showFilterPlaceholder ?? false)
                @include('partials.filter-placeholder')
            @else
                <div class="row g-4">
                    @forelse($notifications as $notification)
                        <div class="col-12">
                            <div class="card shadow border-0">
                                <div class="card-body">
                                    <div class="d-flex justify-content-between align-items-start flex-wrap gap-2 mb-2">
                                        <h5 class="mb-0">{{ $notification->title }}</h5>
                                        <span class="badge bg-primary">
                                            {{ $notification->institute ?: 'All Institutes' }}
                                        </span>
                                    </div>
                                    <p class="mb-2">{{ $notification->message }}</p>
                                    <div class="small text-muted">
                                        Posted {{ $notification->created_at ? $notification->created_at->format('d M Y') : '-' }}
                                        @if($notification->expires_at)
                                            | Valid until {{ $notification->expires_at->format('d M Y') }}
                                        @endif
                                    </div>
                                </div>
                            </div>
                        </div>
                    @empty
                        <div class="col-12">
                            <div class="card shadow border-0">
                                <div class="card-body text-center text-muted">
                                    No active notifications.
                                </div>
                            </div>
                        </div>
                    @endforelse
                </div>
            @endif
        </div>
    </div>
</div>

@endsection
