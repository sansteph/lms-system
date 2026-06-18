@extends('layouts.app')

@section('content')

<div class="container-fluid">
    <div class="row">

        @include('layouts.sidebar')

        <div class="col-md-10 col-lg-10 p-4">

            <div class="page-header mb-4">
                <h2 class="mb-1">Class Session Report</h2>
                <p class="text-muted mb-0">
                    Track which STEM Engineer handled each class and how long they spent on assigned content.
                </p>
            </div>

            <div class="card shadow border-0">
                <div class="card-body">

                    <table class="table table-bordered table-hover align-middle">

                        <thead class="table-light">
                            <tr>
                                <th>Class</th>
                                <th>Institute</th>
                                <th>Content</th>
                                <th>STEM Engineer</th>
                                <th>Started At</th>
                                <th>Ended At</th>
                                <th>Duration</th>
                                <th>Status</th>
                            </tr>
                        </thead>

                        <tbody>

                            @forelse($sessions as $session)

                                <tr>
                                    <td>
                                        {{ $session->schoolClass->class_name ?? 'Deleted Class' }}
                                        -
                                        {{ $session->schoolClass->section ?? '' }}
                                    </td>

                                    <td>
                                        {{ $session->schoolClass->institute ?? 'N/A' }}
                                    </td>

                                    <td>
                                        {{ $session->content->content_title ?? 'No Content' }}
                                    </td>

                                    <td>
                                        {{ $session->stemEngineer->name ?? 'Deleted Engineer' }}
                                    </td>

                                    <td>
                                        {{ $session->started_at }}
                                    </td>

                                    <td>
                                        {{ $session->ended_at ?? 'In Progress' }}
                                    </td>

                                    <td>
                                        {{ gmdate('H:i:s', $session->duration_seconds ?? 0) }}
                                    </td>

                                    <td>
                                        @if($session->status == 'Started')
                                            <span class="badge bg-warning text-dark">Started</span>
                                        @else
                                            <span class="badge bg-success">Completed</span>
                                        @endif
                                    </td>
                                </tr>

                            @empty

                                <tr>
                                    <td colspan="8" class="text-center text-muted">
                                        No class sessions found.
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

@endsection