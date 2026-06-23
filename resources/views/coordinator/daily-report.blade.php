@extends('layouts.app')

@section('content')

<div class="container-fluid">
    <div class="row">

        @include('layouts.coordinator-sidebar')

        <div class="col-md-10 col-lg-10 p-4">

            <div class="page-header mb-4">
                <h2 class="mb-1">Daily Teaching Report</h2>
                <p class="text-muted mb-0">
                    Monitor today’s scheduled, live, completed, and pending sessions by STEM Engineer.
                </p>
            </div>

            <div class="card shadow border-0">
                <div class="card-body">

                    <table class="table table-bordered table-hover align-middle">

                        <thead class="table-light">
                            <tr>
                                <th>Sl. No</th>
                                <th>STEM Engineer</th>
                                <th>Institute</th>
                                <th>Scheduled</th>
                                <th>Live</th>
                                <th>Completed</th>
                                <th>Pending</th>
                                <th>Teaching Hours</th>
                                <th>Completion</th>
                            </tr>
                        </thead>

                        <tbody>

                            @forelse($dailyReports as $index => $report)

                                @php
                                    $completion = $report['scheduled'] > 0
                                        ? round(($report['completed'] / $report['scheduled']) * 100)
                                        : 0;
                                @endphp

                                <tr>
                                    <td>{{ $index + 1 }}</td>

                                    <td>{{ $report['teacher']->name }}</td>

                                    <td>{{ $report['teacher']->institute ?? 'N/A' }}</td>

                                    <td>{{ $report['scheduled'] }}</td>

                                    <td>
                                        <span class="badge bg-warning text-dark">
                                            {{ $report['live'] }}
                                        </span>
                                    </td>

                                    <td>
                                        <span class="badge bg-success">
                                            {{ $report['completed'] }}
                                        </span>
                                    </td>

                                    <td>
                                        <span class="badge bg-danger">
                                            {{ $report['pending'] }}
                                        </span>
                                    </td>

                                    <td>{{ $report['duration_hours'] }} hrs</td>

                                    <td>
                                        @if($completion >= 80)
                                            <span class="badge bg-success">
                                                {{ $completion }}%
                                            </span>
                                        @elseif($completion >= 50)
                                            <span class="badge bg-warning text-dark">
                                                {{ $completion }}%
                                            </span>
                                        @else
                                            <span class="badge bg-danger">
                                                {{ $completion }}%
                                            </span>
                                        @endif
                                    </td>
                                </tr>

                            @empty

                                <tr>
                                    <td colspan="9" class="text-center text-muted">
                                        No STEM Engineers found.
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