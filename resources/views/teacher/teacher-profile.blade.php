@extends('layouts.app')

@section('content')

<div class="container-fluid">
    <div class="row">
        @include('layouts.teacher-sidebar')

        <div class="col-md-10 col-lg-10 p-4">
            @if(session('success'))
                <div class="alert alert-success">{{ session('success') }}</div>
            @endif

            @if($errors->any())
                <div class="alert alert-danger">Please check the highlighted profile fields.</div>
            @endif

            <div class="social-profile-card mb-4">
                <div class="social-profile-header">
                    <div class="social-profile-main">
                        @if($teacher->profile_image)
                            <img src="{{ \Illuminate\Support\Facades\Storage::url($teacher->profile_image) }}"
                                 alt="Profile image"
                                 class="social-profile-avatar">
                        @else
                            <div class="social-profile-initial">
                                {{ strtoupper(substr($teacher->name, 0, 1)) }}
                            </div>
                        @endif

                        <div class="flex-grow-1">
                            <h2 class="social-profile-name">{{ $teacher->name }}</h2>
                            <div class="social-profile-meta">
                                {{ $teacher->designation ?: 'STEM Engineer' }}<br>
                                {{ $teacher->institute }}<br>
                                ID: {{ $teacher->user_id }}
                            </div>
                        </div>

                        <button type="button"
                                class="profile-edit-icon"
                                data-bs-toggle="modal"
                                data-bs-target="#editTeacherProfileModal"
                                title="Edit Profile">
                            <i class="fa fa-pen"></i>
                        </button>
                    </div>
                </div>

                <div class="profile-stat-grid">
                    <div class="profile-stat-tile">
                        <span>Qualification</span>
                        <strong>{{ $teacher->qualification ?: 'Not set' }}</strong>
                    </div>
                    <div class="profile-stat-tile">
                        <span>Ideas / Projects</span>
                        <strong>{{ $mySpaceItems->count() }}</strong>
                    </div>
                    <div class="profile-stat-tile">
                        <span>Achievements</span>
                        <strong>{{ $achievements->count() }}</strong>
                    </div>
                    <div class="profile-stat-tile">
                        <span>Joined</span>
                        <strong>{{ $teacher->joined_on ? \Carbon\Carbon::parse($teacher->joined_on)->format('d M Y') : $teacher->created_at->format('d M Y') }}</strong>
                    </div>
                </div>
            </div>

            <div class="row g-4">
                <div class="col-12">
                    <div class="profile-panel">
                        <div class="profile-panel-header">
                            <div>
                                <h5>Activity Portfolio</h5>
                                <p>Approved achievements, ideas, and featured projects appear here.</p>
                            </div>
                        </div>
                        <div class="profile-panel-body">
                            <h6 class="profile-section-label">Achievements</h6>
                            @forelse($achievements as $achievement)
                                <div class="profile-feed-item">
                                    <div>
                                        <h6>{{ $achievement->title }}</h6>
                                        <span>
                                            {{ $achievement->achievement_type }}
                                            @if($achievement->organizer)
                                                | {{ $achievement->organizer }}
                                            @endif
                                            @if($achievement->achievement_date)
                                                | {{ \Carbon\Carbon::parse($achievement->achievement_date)->format('d M Y') }}
                                            @endif
                                        </span>
                                    </div>
                                    @if($achievement->description)
                                        <p>{{ $achievement->description }}</p>
                                    @endif
                                    @if($achievement->position)
                                        <span class="badge bg-light text-dark border mt-2">{{ $achievement->position }}</span>
                                    @endif
                                </div>
                            @empty
                                <div class="profile-empty-state mb-4">
                                    No approved achievements yet.
                                </div>
                            @endforelse

                            <h6 class="profile-section-label mt-4">Ideas & Projects</h6>
                            @forelse($mySpaceItems as $item)
                                <div class="profile-feed-item">
                                    <div>
                                        <h6>{{ $item->title }}</h6>
                                        <span>{{ $item->type }} | {{ $item->status }}</span>
                                    </div>
                                    <p>{{ $item->description }}</p>
                                    @if($item->repository_link)
                                        <a href="{{ $item->repository_link }}" target="_blank" class="btn btn-sm btn-outline-primary">Open Project</a>
                                    @endif
                                </div>
                            @empty
                                <div class="profile-empty-state">
                                    No approved or featured My Space submissions yet.
                                </div>
                            @endforelse
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="editTeacherProfileModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-scrollable">
        <div class="modal-content">
            <form method="POST" action="{{ route('teacher.profile.update') }}" enctype="multipart/form-data">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title">Edit Profile</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">STEM Engineer ID</label>
                            <input type="text" name="user_id" class="form-control" value="{{ old('user_id', $teacher->user_id) }}" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">STEM Engineer Name</label>
                            <input type="text" name="name" class="form-control" value="{{ old('name', $teacher->name) }}" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Official Email ID</label>
                            <input type="email" name="email" class="form-control" value="{{ old('email', $teacher->email) }}" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Designation</label>
                            <input type="text" name="designation" class="form-control" value="{{ old('designation', $teacher->designation ?: 'STEM Engineer') }}">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Qualification</label>
                            <input type="text" name="qualification" class="form-control" value="{{ old('qualification', $teacher->qualification) }}" placeholder="B.Tech, M.Sc, B.Ed..." required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Joined On</label>
                            <input type="date" name="joined_on" class="form-control" value="{{ old('joined_on', $teacher->joined_on ?: optional($teacher->created_at)->format('Y-m-d')) }}">
                        </div>
                        <div class="col-12">
                            <label class="form-label">Profile Image</label>
                            <input type="file" name="profile_image" class="form-control" accept=".jpg,.jpeg,.png,.webp">
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Update Profile</button>
                </div>
            </form>
        </div>
    </div>
</div>

@endsection
