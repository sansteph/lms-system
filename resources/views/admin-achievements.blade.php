@extends('layouts.app')

@section('content')

<div class="container py-4">

    <h3 class="fw-bold mb-4">
        Student Achievements
    </h3>

    @if(session('success'))

        <div class="alert alert-success">

            {{ session('success') }}

        </div>

    @endif

    <div class="card shadow border-0">

        <div class="card-body">

            <div class="table-responsive">

                <table class="table table-bordered align-middle">

                    <thead class="table-light">

                        <tr>

                            <th>ID</th>

                            <th>Student ID</th>

                            <th>Type</th>

                            <th>Title</th>

                            <th>Organizer</th>

                            <th>Status</th>

                            <th>Certificate</th>

                            <th>Actions</th>

                        </tr>

                    </thead>

                    <tbody>

                        @forelse($achievements as $achievement)

                            <tr>

                                <td>
                                    {{ $achievement->id }}
                                </td>

                                <td>
                                    {{ $achievement->student_id }}
                                </td>

                                <td>
                                    {{ $achievement->achievement_type }}
                                </td>

                                <td>
                                    {{ $achievement->title }}
                                </td>

                                <td>
                                    {{ $achievement->organizer }}
                                </td>

                                <td>

                                    @if($achievement->verification_status == 'Approved')

                                        <span class="badge bg-success">
                                            Approved
                                        </span>

                                    @elseif($achievement->verification_status == 'Rejected')

                                        <span class="badge bg-danger">
                                            Rejected
                                        </span>

                                    @else

                                        <span class="badge bg-warning text-dark">
                                            Pending
                                        </span>

                                    @endif

                                </td>

                                <td>

                                    <a href="{{ asset('storage/' . $achievement->certificate_file) }}"
                                       target="_blank"
                                       class="btn btn-sm btn-outline-primary">

                                        View

                                    </a>

                                </td>

                                <td>

                                    <div class="d-flex gap-2">

                                        <form action="{{ route('admin.achievements.approve', $achievement->id) }}"
                                              method="POST">

                                            @csrf

                                            <button class="btn btn-success btn-sm">

                                                Approve

                                            </button>

                                        </form>

                                        <form action="{{ route('admin.achievements.reject', $achievement->id) }}"
                                              method="POST">

                                            @csrf

                                            <button class="btn btn-danger btn-sm">

                                                Reject

                                            </button>

                                        </form>

                                    </div>

                                </td>

                            </tr>

                        @empty

                            <tr>

                                <td colspan="8"
                                    class="text-center text-muted py-4">

                                    No achievements found

                                </td>

                            </tr>

                        @endforelse

                    </tbody>

                </table>

            </div>

        </div>

    </div>

</div>

@endsection