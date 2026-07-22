@extends('layouts.app')

@section('content')

<div class="container-fluid">
    <div class="row">
        @include('layouts.student-sidebar')

        <div class="col-md-10 col-lg-10 p-4">
            @if(session('success'))
                <div class="alert alert-success">{{ session('success') }}</div>
            @endif

            @if($errors->any())
                <div class="alert alert-danger">Please check the profile image or LinkedIn URL.</div>
            @endif

            <div class="social-profile-card mb-4">
                <div class="social-profile-header">
                    <div class="social-profile-main">
                        @if($student->profile_image)
                            <img src="{{ \Illuminate\Support\Facades\Storage::url($student->profile_image) }}"
                                 alt="Profile image"
                                 class="social-profile-avatar">
                        @else
                            <div class="social-profile-initial">
                                {{ strtoupper(substr($student->name, 0, 1)) }}
                            </div>
                        @endif

                        <div class="flex-grow-1">
                            <h2 class="social-profile-name">{{ $student->name }}</h2>
                            <div class="social-profile-meta">
                                Class {{ $student->class }} - {{ $student->section }}<br>
                                {{ $student->institute }}<br>
                                ID: {{ $student->student_id }}
                            </div>
                        </div>

                        <button type="button"
                                class="profile-edit-icon"
                                data-bs-toggle="modal"
                                data-bs-target="#editStudentProfileModal"
                                title="Edit Profile">
                            <i class="fa fa-pen"></i>
                        </button>
                    </div>
                </div>

                <div class="profile-stat-grid">
                    <div class="profile-stat-tile">
                        <span>Badges</span>
                        <strong>{{ $badgeCount }}</strong>
                    </div>
                    <div class="profile-stat-tile">
                        <span>Achievements</span>
                        <strong>{{ $achievements->count() }}</strong>
                    </div>
                    <div class="profile-stat-tile">
                        <span>Ideas / Projects</span>
                        <strong>{{ $mySpaceItems->count() }}</strong>
                    </div>
                </div>
            </div>

            <div class="row g-4">
                <div class="col-xl-4">
                    <div class="profile-panel">
                        <div class="profile-panel-header">
                            <div>
                                <h5>Student Details</h5>
                                <p>Official LMS identity.</p>
                            </div>
                        </div>
                        <div class="profile-panel-body">
                            <div class="profile-info-item">
                                <span class="profile-label">Student ID</span>
                                <strong>{{ $student->student_id }}</strong>
                            </div>
                            <div class="profile-info-item">
                                <span class="profile-label">Student Name</span>
                                <strong>{{ $student->name }}</strong>
                            </div>
                            <div class="profile-info-item">
                                <span class="profile-label">Official Email ID</span>
                                <strong>{{ $student->email ?: 'Not provided' }}</strong>
                            </div>
                            <div class="profile-info-item">
                                <span class="profile-label">Contact</span>
                                <strong>{{ $student->contact }}</strong>
                            </div>
                            <div class="profile-info-item mb-0">
                                <span class="profile-label">Badges</span>
                                <strong>{{ $badgeCount }} Badges</strong>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-xl-8">
                    <div class="profile-panel mb-4">
                        <div class="profile-panel-header">
                            <div>
                                <h5>Achievements</h5>
                                <p>Approved achievements from competitions and activities.</p>
                            </div>
                        </div>
                        <div class="profile-panel-body">
                            @forelse($achievements as $achievement)
                                <div class="profile-feed-item">
                                    <h6>{{ $achievement->title }}</h6>
                                    <span>
                                        {{ $achievement->achievement_type }}
                                        @if($achievement->organizer)
                                            | {{ $achievement->organizer }}
                                        @endif
                                    </span>
                                    <p>{{ $achievement->description }}</p>
                                    <span class="badge bg-success">Approved</span>
                                </div>
                            @empty
                                <div class="profile-empty-state">No approved achievements yet.</div>
                            @endforelse
                        </div>
                    </div>

                    <div class="profile-panel">
                        <div class="profile-panel-header">
                            <div>
                                <h5>Ideas & Projects</h5>
                                <p>Featured My Space submissions.</p>
                            </div>
                        </div>
                        <div class="profile-panel-body">
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
                                <div class="profile-empty-state">No approved My Space submissions yet.</div>
                            @endforelse
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="editStudentProfileModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST" action="{{ route('student.profile.update') }}" enctype="multipart/form-data">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title">Edit Profile</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Profile Image</label>
                        <input type="file" name="profile_image" class="form-control" accept=".jpg,.jpeg,.png,.webp">
                    </div>
                </div>
                <div class="modal-footer flex-wrap gap-2">
                    @if($student->profile_image)
                        <button type="submit"
                                form="removeStudentProfileImageForm"
                                class="btn btn-outline-danger me-auto">
                            Remove Image
                        </button>
                    @endif
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Update Profile</button>
                </div>
            </form>

            @if($student->profile_image)
                <form id="removeStudentProfileImageForm" method="POST" action="{{ route('student.profile.remove-image') }}">
                    @csrf
                </form>
            @endif
        </div>
    </div>
</div>

@endsection
