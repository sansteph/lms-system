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

            <div class="card shadow border-0 mb-4">
                <div class="card-body p-4">
                    <div class="d-flex flex-wrap align-items-center gap-4">
                        @if($teacher->profile_image)
                            <img src="{{ \Illuminate\Support\Facades\Storage::url($teacher->profile_image) }}"
                                 alt="Profile image"
                                 class="rounded-circle border"
                                 style="width: 112px; height: 112px; object-fit: cover;">
                        @else
                            <div class="rounded-circle bg-primary text-white d-flex align-items-center justify-content-center"
                                 style="width: 112px; height: 112px; font-size: 38px; font-weight: 700;">
                                {{ strtoupper(substr($teacher->name, 0, 1)) }}
                            </div>
                        @endif

                        <div class="flex-grow-1">
                            <h2 class="mb-1">{{ $teacher->name }}</h2>
                            <div class="text-muted">{{ $teacher->designation ?: 'STEM Engineer' }}</div>
                            <div class="small text-muted">{{ $teacher->institute }}</div>
                            <div class="small text-muted mt-1">ID: {{ $teacher->user_id }}</div>
                        </div>

                        <div class="d-flex flex-wrap gap-2">
                            <span class="badge bg-light text-dark border px-3 py-2">
                                {{ $teacher->qualification ?: 'Qualification not set' }}
                            </span>
                            <span class="badge bg-light text-dark border px-3 py-2">
                                {{ $mySpaceItems->count() }} Ideas / Projects
                            </span>
                        </div>

                        @if($teacher->linkedin_url)
                            <a href="{{ $teacher->linkedin_url }}" target="_blank" class="btn btn-outline-primary">
                                LinkedIn Profile
                            </a>
                        @endif
                    </div>
                </div>
            </div>

            <div class="row g-4">
                <div class="col-xl-5">
                    <div class="card shadow border-0 mb-4">
                        <div class="card-body p-4">
                            <h5 class="mb-3">Edit Profile</h5>
                            <form method="POST" action="{{ route('teacher.profile.update') }}" enctype="multipart/form-data">
                                @csrf
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
                                    <div class="col-md-6">
                                        <label class="form-label">LinkedIn Profile</label>
                                        <input type="url" name="linkedin_url" class="form-control" value="{{ old('linkedin_url', $teacher->linkedin_url) }}" placeholder="https://linkedin.com/in/...">
                                    </div>
                                    <div class="col-12">
                                        <label class="form-label">Profile Image</label>
                                        <input type="file" name="profile_image" class="form-control" accept=".jpg,.jpeg,.png,.webp">
                                    </div>
                                    <div class="col-12">
                                        <button type="submit" class="btn btn-primary">Update Profile</button>
                                    </div>
                                </div>
                            </form>
                        </div>
                    </div>

                    <div class="card shadow border-0">
                        <div class="card-body p-4">
                            <h5 class="mb-3">Professional Snapshot</h5>
                            <div class="mb-3">
                                <label class="form-label">Qualification</label>
                                <input type="text" class="form-control" value="{{ $teacher->qualification ?: 'Not provided' }}" readonly>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Designation</label>
                                <input type="text" class="form-control" value="{{ $teacher->designation ?: 'STEM Engineer' }}" readonly>
                            </div>
                            <a href="{{ route('teacher.achievements') }}" class="btn btn-outline-primary w-100">
                                Manage My Achievements
                            </a>
                        </div>
                    </div>
                </div>

                <div class="col-xl-7">
                    <div class="row g-4 mb-4">
                        <div class="col-md-6"><div class="dashboard-card"><h6>Ideas / Projects</h6><h2>{{ $mySpaceItems->count() }}</h2></div></div>
                        <div class="col-md-6"><div class="dashboard-card"><h6>Joined</h6><h5>{{ $teacher->joined_on ? \Carbon\Carbon::parse($teacher->joined_on)->format('d M Y') : $teacher->created_at->format('d M Y') }}</h5></div></div>
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
                                <div class="text-muted">No approved or featured My Space submissions yet.</div>
                            @endforelse
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

@endsection
