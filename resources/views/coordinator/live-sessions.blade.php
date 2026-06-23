@extends('layouts.app')

@section('content')

<div class="container-fluid">
    <div class="row">

        @include('layouts.coordinator-sidebar')

        <div class="col-md-10 col-lg-10 p-4">

            <div class="page-header mb-4">
                <h2 class="mb-1">Live Sessions</h2>
                <p class="text-muted mb-0">
                    Monitor currently running STEM Engineer sessions.
                </p>
            </div>

            <div class="card shadow border-0">

                <div class="card-body">

                    <table class="table table-bordered table-hover align-middle">

                        <thead class="table-light">
                            <tr>
                                <th>Sl. No</th>
                                <th>STEM Engineer</th>
                                <th>Class</th>
                                <th>Topic</th>
                                <th>Started At</th>
                                <th>Live Duration</th>
                                <th>Status</th>
                            </tr>
                        </thead>

                        <tbody>

                            @forelse($liveSessions as $index => $session)

                                <tr>
                                    <td>{{ $index + 1 }}</td>

                                    <td>
                                        {{ $session->stemEngineer->name ?? 'N/A' }}
                                    </td>

                                    <td>
                                        {{ $session->schoolClass->class_name ?? 'Deleted Class' }}
                                        -
                                        {{ $session->schoolClass->section ?? '' }}
                                    </td>

                                    <td>
                                        {{ $session->content->content_title ?? 'N/A' }}
                                    </td>

                                    <td>
                                        {{ \Carbon\Carbon::parse($session->started_at)->format('d-m-Y h:i A') }}
                                    </td>

                                    <td>
                                        {{ \Carbon\Carbon::parse($session->started_at)->diffForHumans(null, true) }}
                                    </td>

                                    <td>
                                        <span class="badge bg-warning text-dark">
                                            Live
                                        </span>
                                    </td>
                                </tr>

                            @empty

                                <tr>
                                    <td colspan="7"
                                        class="text-center text-muted">
                                        No live sessions currently running.
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