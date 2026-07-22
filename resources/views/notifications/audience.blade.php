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
        </div>
    </div>
</div>

@endsection
