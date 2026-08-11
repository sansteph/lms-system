@extends('layouts.app')

@section('content')

<div class="container-fluid">
    <div class="row">

        @if(session('user_role') == 'Teacher')
            @include('layouts.teacher-sidebar')
        @else
            @include('layouts.student-sidebar')
        @endif

        <div class="col-md-10 col-lg-10 p-4">

            <div class="page-header mb-4">
                <h2>My Space</h2>
                <p class="text-muted">
                    Share innovative ideas and showcase projects.
                </p>
            </div>

            @if(session('success'))
                <div class="alert alert-success">
                    {{ session('success') }}
                </div>
            @endif

            @if(session('error'))
                <div class="alert alert-danger">
                    {{ session('error') }}
                </div>
            @endif

            <div class="mb-4">

                @if(session('user_role') == 'Teacher')

                    <a href="{{ route('teacher.my-space.create') }}"
                       class="btn btn-primary">
                        Submit New Entry
                    </a>

                @else

                    <a href="{{ route('student.my-space.create') }}"
                       class="btn btn-primary">
                        Submit New Entry
                    </a>

                @endif

            </div>

            <div class="card shadow border-0">

                <div class="card-body">

                    <div class="table-responsive lms-table-shell">
                    <table class="table table-hover align-middle lms-table-fit">

                        <thead>
                            <tr>
                                <th>Title</th>
                                <th>Type</th>
                                <th>Date</th>
                                <th>Status</th>
                                <th width="220">Actions</th>    
                            </tr>
                        </thead>

                        <tbody>

                            @forelse($items as $item)

                                <tr>

                                    <td>{{ $item->title }}</td>

                                    <td>
                                        <span class="badge bg-info">
                                            {{ $item->type }}
                                        </span>
                                    </td>

                                    <td>
                                        {{ $item->created_at->format('d M Y') }}
                                    </td>

                                    <td>
                                        <span class="badge bg-success">
                                            {{ $item->status }}
                                        </span>
                                    </td>

                                    <td>

                                        <div class="d-flex gap-2 flex-wrap">

                                            @if(session('user_role') == 'Teacher')

                                                <a href="{{ route('teacher.my-space.show', $item->id) }}"
                                                class="btn btn-sm btn-primary">
                                                    View
                                                </a>

                                            @else

                                                <a href="{{ route('student.my-space.show', $item->id) }}"
                                                class="btn btn-sm btn-primary">
                                                    View
                                                </a>

                                            @endif

                                            @if(in_array($item->status, ['Pending', 'Rejected']))

                                                @if(session('user_role') == 'Teacher')

                                                    <a href="{{ route('teacher.my-space.edit', $item->id) }}"
                                                    class="btn btn-sm btn-outline-primary">
                                                        Edit
                                                    </a>

                                                    <form method="POST"
                                                        action="{{ route('teacher.my-space.delete', $item->id) }}">
                                                        @csrf

                                                        <button type="submit"
                                                                class="btn btn-sm btn-outline-danger"
                                                                onclick="return confirm('Delete this submission?')">
                                                            Delete
                                                        </button>
                                                    </form>

                                                @else

                                                    <a href="{{ route('student.my-space.edit', $item->id) }}"
                                                    class="btn btn-sm btn-outline-primary">
                                                        Edit
                                                    </a>

                                                    <form method="POST"
                                                        action="{{ route('student.my-space.delete', $item->id) }}">
                                                        @csrf

                                                        <button type="submit"
                                                                class="btn btn-sm btn-outline-danger"
                                                                onclick="return confirm('Delete this submission?')">
                                                            Delete
                                                        </button>
                                                    </form>

                                                @endif

                                            @endif

                                        </div>

                                    </td>

                                </tr>

                            @empty

                                <tr>
                                    <td colspan="5" class="text-center text-muted">
                                        No ideas or projects submitted yet.
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
</div>

@endsection
