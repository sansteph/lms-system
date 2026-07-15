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

            <div class="card shadow border-0 mb-4">
                <div class="card-body p-4">
                    <div class="d-flex flex-wrap align-items-center gap-4">
                        @if($student->profile_image)
                            <img src="{{ \Illuminate\Support\Facades\Storage::url($student->profile_image) }}"
                                 alt="Profile image"
                                 class="rounded-circle border"
                                 style="width: 112px; height: 112px; object-fit: cover;">
                        @else
                            <div class="rounded-circle bg-primary text-white d-flex align-items-center justify-content-center"
                                 style="width: 112px; height: 112px; font-size: 38px; font-weight: 700;">
                                {{ strtoupper(substr($student->name, 0, 1)) }}
                            </div>
                        @endif

                        <div class="flex-grow-1">
                            <h2 class="mb-1">{{ $student->name }}</h2>
                            <div class="text-muted">Class {{ $student->class }} - {{ $student->section }}</div>
                            <div class="small text-muted">{{ $student->institute }}</div>
                            <div class="small text-muted mt-1">ID: {{ $student->student_id }}</div>
                        </div>

                        <div class="d-flex flex-wrap gap-2">
                            <span class="badge bg-light text-dark border px-3 py-2">
                                {{ $badgeCount }} Badges
                            </span>
                            <span class="badge bg-light text-dark border px-3 py-2">
                                {{ $achievements->count() }} Achievements
                            </span>
                            <span class="badge bg-light text-dark border px-3 py-2">
                                {{ $mySpaceItems->count() }} Ideas / Projects
                            </span>
                        </div>

                        @if($student->linkedin_url)
                            <a href="{{ $student->linkedin_url }}" target="_blank" class="btn btn-outline-primary">
                                LinkedIn Profile
                            </a>
                        @endif
                    </div>
                </div>
            </div>

            <div class="row g-4">
                <div class="col-xl-4">
                    <div class="card shadow border-0 mb-4">
                        <div class="card-body p-4">
                            <h5 class="mb-3">Profile Image & Links</h5>
                            <form method="POST" action="{{ route('student.profile.update') }}" enctype="multipart/form-data" class="mb-3">
                                @csrf
                                <div class="mb-3">
                                    <label class="form-label">Profile Image</label>
                                    <input type="file" name="profile_image" class="form-control" accept=".jpg,.jpeg,.png,.webp">
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">LinkedIn Profile</label>
                                    <input type="url" name="linkedin_url" class="form-control" value="{{ old('linkedin_url', $student->linkedin_url) }}" placeholder="https://linkedin.com/in/...">
                                </div>
                                <button type="submit" class="btn btn-primary w-100">Update Profile</button>
                            </form>

                            @if($student->profile_image)
                                <form method="POST" action="{{ route('student.profile.remove-image') }}">
                                    @csrf
                                    <button type="submit" class="btn btn-outline-danger w-100">Remove Profile Image</button>
                                </form>
                            @endif
                        </div>
                    </div>

                    <div class="card shadow border-0">
                        <div class="card-body p-4">
                            <h5 class="mb-3">Student Details</h5>
                            <div class="mb-3">
                                <label class="form-label">Student ID</label>
                                <input type="text" class="form-control" value="{{ $student->student_id }}" readonly>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Student Name</label>
                                <input type="text" class="form-control" value="{{ $student->name }}" readonly>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Official Email ID</label>
                                <input type="text" class="form-control" value="{{ $student->email ?: 'Not provided' }}" readonly>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Contact</label>
                                <input type="text" class="form-control" value="{{ $student->contact }}" readonly>
                            </div>
                            <div class="mb-0">
                                <label class="form-label">Badges</label>
                                <input type="text" class="form-control" value="{{ $badgeCount }} Badges" readonly>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-xl-8">
                    <div class="row g-4 mb-4">
                        <div class="col-md-4"><div class="dashboard-card"><h6>Approved Achievements</h6><h2>{{ $achievements->count() }}</h2></div></div>
                        <div class="col-md-4"><div class="dashboard-card"><h6>Ideas / Projects</h6><h2>{{ $mySpaceItems->count() }}</h2></div></div>
                        <div class="col-md-4"><div class="dashboard-card"><h6>Badges</h6><h2>{{ $badgeCount }}</h2></div></div>
                    </div>

                    <div class="card shadow border-0 mb-4">
                        <div class="card-body p-4">
                            <h5 class="mb-3">Approved Achievements</h5>
                            @forelse($achievements as $achievement)
                                <div class="border rounded p-3 mb-3">
                                    <h6 class="mb-1">{{ $achievement->title }}</h6>
                                    <div class="text-muted small">
                                        {{ $achievement->achievement_type }}
                                        @if($achievement->organizer)
                                            | {{ $achievement->organizer }}
                                        @endif
                                    </div>
                                    <p class="mb-2 mt-2">{{ $achievement->description }}</p>
                                    <span class="badge bg-success">Approved</span>
                                </div>
                            @empty
                                <div class="text-muted">No approved achievements yet.</div>
                            @endforelse
                        </div>
                    </div>

                    <div class="card shadow border-0">
                        <div class="card-body p-4">
                            <h5 class="mb-3">Approved Ideas & Projects</h5>
                            @forelse($mySpaceItems as $item)
                                <div class="border rounded p-3 mb-3">
                                    <div class="d-flex justify-content-between flex-wrap gap-2">
                                        <div>
                                            <h6 class="mb-1">{{ $item->title }}</h6>
                                            <div class="text-muted small">{{ $item->type }} | {{ $item->status }}</div>
                                        </div>
                                        @if($item->repository_link)
                                            <a href="{{ $item->repository_link }}" target="_blank" class="btn btn-sm btn-outline-primary">Open</a>
                                        @endif
                                    </div>
                                    <p class="mb-0 mt-2">{{ $item->description }}</p>
                                </div>
                            @empty
                                <div class="text-muted">No approved My Space submissions yet.</div>
                            @endforelse
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

@endsection
