@extends('layouts.app')

@section('content')

<div class="container-fluid">
    <div class="row">
        @include('layouts.teacher-sidebar')

        <div class="col-md-10 col-lg-10 p-4">
            <div class="d-flex justify-content-between align-items-center flex-wrap gap-3 mb-4">
                <div>
                    <h2 class="fw-bold mb-1">My Achievements</h2>
                    <p class="text-muted mb-0">
                        Upload certifications, workshops, competitions, and professional achievements.
                    </p>
                </div>
            </div>

            @if(session('success'))
                <div class="alert alert-success">{{ session('success') }}</div>
            @endif

            @if($errors->any())
                <div class="alert alert-danger">Please fill all required fields correctly.</div>
            @endif

            <div class="row g-4 mb-4">
                <div class="col-md-4">
                    <div class="dashboard-card">
                        <h6>Total Uploaded</h6>
                        <h2>{{ $achievements->count() }}</h2>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="dashboard-card">
                        <h6>Approved</h6>
                        <h2>{{ $achievements->where('verification_status', 'Approved')->count() }}</h2>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="dashboard-card">
                        <h6>Pending Review</h6>
                        <h2>{{ $achievements->where('verification_status', 'Pending')->count() }}</h2>
                    </div>
                </div>
            </div>

            <div class="card shadow border-0 mb-4">
                <div class="card-body p-4">
                    <h5 class="mb-3">Upload Achievement</h5>
                    <form method="POST" action="{{ route('teacher.achievements.store') }}" enctype="multipart/form-data">
                        @csrf
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label">Achievement Type</label>
                                <select name="achievement_type" class="form-select" required>
                                    <option value="">Select Type</option>
                                    <option value="Certification">Certification</option>
                                    <option value="Workshop">Workshop</option>
                                    <option value="Competition">Competition</option>
                                    <option value="Exhibition">Exhibition</option>
                                    <option value="Course">Extra Course</option>
                                    <option value="Professional Recognition">Professional Recognition</option>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Achievement Date</label>
                                <input type="date" name="achievement_date" class="form-control">
                            </div>
                            <div class="col-md-12">
                                <label class="form-label">Title</label>
                                <input type="text" name="title" class="form-control" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Organizer</label>
                                <input type="text" name="organizer" class="form-control">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Position / Result</label>
                                <input type="text" name="position" class="form-control">
                            </div>
                            <div class="col-md-12">
                                <label class="form-label">Description</label>
                                <textarea name="description" class="form-control" rows="4"></textarea>
                            </div>
                            <div class="col-md-12">
                                <label class="form-label">Certificate / Proof</label>
                                <input type="file" name="certificate_file" class="form-control" accept=".pdf,.jpg,.jpeg,.png">
                                <small class="text-muted">Accepted formats: PDF, JPG, JPEG, PNG. Max 5MB.</small>
                            </div>
                            <div class="col-12">
                                <button type="submit" class="btn btn-primary">
                                    <i class="fa fa-upload me-2"></i>
                                    Submit Achievement
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>

            <div class="card shadow border-0">
                <div class="card-body p-4">
                    <h5 class="mb-4">Uploaded Achievements</h5>

                    <div class="row g-4">
                        @forelse($achievements as $achievement)
                            <div class="col-md-6 col-lg-4">
                                <div class="card border-0 shadow-sm h-100">
                                    <div class="card-body d-flex flex-column">
                                        <div class="d-flex justify-content-between align-items-start mb-3">
                                            <div>
                                                <h6 class="fw-bold mb-1">{{ $achievement->title }}</h6>
                                                <small class="text-muted">{{ $achievement->achievement_type }}</small>
                                            </div>

                                            @if($achievement->verification_status == 'Approved')
                                                <span class="badge bg-success">Verified</span>
                                            @elseif($achievement->verification_status == 'Rejected')
                                                <span class="badge bg-danger">Rejected</span>
                                            @else
                                                <span class="badge bg-warning text-dark">Pending</span>
                                            @endif
                                        </div>

                                        <div class="mb-2">
                                            <small class="text-muted d-block">Organizer</small>
                                            <span>{{ $achievement->organizer ?? '-' }}</span>
                                        </div>

                                        <div class="mb-2">
                                            <small class="text-muted d-block">Position</small>
                                            <span>{{ $achievement->position ?? '-' }}</span>
                                        </div>

                                        <div class="mb-3">
                                            <small class="text-muted d-block">Achievement Date</small>
                                            <span>
                                                {{ $achievement->achievement_date ? \Carbon\Carbon::parse($achievement->achievement_date)->format('d M Y') : '-' }}
                                            </span>
                                        </div>

                                        <p class="text-muted small flex-grow-1">{{ $achievement->description }}</p>

                                        @if($achievement->certificate_file)
                                            <a href="{{ asset('storage/' . $achievement->certificate_file) }}"
                                               target="_blank"
                                               class="btn btn-outline-primary btn-sm w-100 mb-2">
                                                View Certificate
                                            </a>
                                        @endif

                                        <form method="POST"
                                              action="{{ route('teacher.achievements.delete', $achievement->id) }}"
                                              onsubmit="return confirm('Delete this achievement?')">
                                            @csrf
                                            <button type="submit" class="btn btn-sm btn-outline-danger w-100">
                                                Delete
                                            </button>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        @empty
                            <div class="col-12">
                                <div class="text-center py-5">
                                    <i class="fa fa-trophy text-muted mb-3" style="font-size: 70px;"></i>
                                    <h5>No achievements uploaded yet</h5>
                                    <p class="text-muted mb-0">Use the form above to add your professional achievements.</p>
                                </div>
                            </div>
                        @endforelse
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

@endsection
