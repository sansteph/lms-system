@extends('layouts.app')

@section('content')

<div class="container-fluid">

    <div class="row">

        @include('layouts.sidebar')

        <div class="col-md-10 col-lg-10 p-4">

            <div class="page-header d-flex justify-content-between align-items-center mb-4">

                <div>

                    <h2>Class Timetable</h2>

                    <p class="text-muted">
                        Manage weekly timetable schedules.
                    </p>

                </div>

                <div>

                    <a href="{{ route('timetable.copy.week') }}"
                       class="btn btn-success">

                        Copy Last Week

                    </a>

                    <button class="btn btn-primary"
                            data-bs-toggle="modal"
                            data-bs-target="#addTimetableModal">

                        Create New Timetable

                    </button>

                </div>

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

            <div class="card shadow border-0">

                <div class="card-body">

                    <table class="table table-bordered table-hover">

                        <thead class="table-light">

                            <tr>

                                <th>Sl No</th>

                                <th>Class</th>

                                <th>Date</th>

                                <th>Day</th>

                                <th>Day Type</th>

                                <th>From</th>
                                
                                <th>To</th>

                                <th>Action</th>

                            </tr>

                        </thead>

                        <tbody>

                            @forelse($timetables as $index => $row)

                                <tr>

                                    <td>
                                        {{ $index + 1 }}
                                    </td>

                                    <td>
                                        {{ $row->schoolClass->class_name ?? 'N/A' }}
                                    </td>

                                    <td>
                                        {{ \Carbon\Carbon::parse($row->session_date)->format('d-m-Y') }}
                                    </td>

                                    <td>
                                        {{ $row->day }}
                                    </td>

                                    <td>

                                        @if($row->day_type == 'Holiday')

                                            <span class="badge bg-danger">
                                                Holiday
                                            </span>

                                        @else

                                            <span class="badge bg-success">
                                                Working Day
                                            </span>

                                        @endif

                                    </td>

                                    <td>
                                        {{ $row->from_time ? \Carbon\Carbon::parse($row->from_time)->format('h:i A') : '-' }}
                                    </td>

                                    <td>
                                        {{ $row->to_time ? \Carbon\Carbon::parse($row->to_time)->format('h:i A') : '-' }}
                                    </td>

                                    <td>

                                        <a href="{{ route('timetable.delete', $row->id) }}"
                                           class="btn btn-sm btn-danger"
                                           onclick="return confirm('Delete timetable entry?')">

                                            Delete

                                        </a>

                                    </td>

                                </tr>

                            @empty

                                <tr>

                                    <td colspan="8"
                                        class="text-center">

                                        No timetable entries found.

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

<div class="modal fade"
     id="addTimetableModal"
     tabindex="-1">

    <div class="modal-dialog">

        <div class="modal-content">

            <form method="POST"
                  action="{{ route('timetable.store') }}">

                @csrf

                <div class="modal-header">

                    <h5 class="modal-title">

                        Create Timetable

                    </h5>

                    <button type="button"
                            class="btn-close"
                            data-bs-dismiss="modal">
                    </button>

                </div>

                <div class="modal-body">

                    <div class="mb-3">

                        <label class="form-label">
                            Class
                        </label>

                        <select name="class_id"
                                class="form-control"
                                required>

                            <option value="">
                                Select Class
                            </option>

                            @foreach($classes as $class)

                                <option value="{{ $class->id }}">

                                    {{ $class->class_name }}
                                    -
                                    {{ $class->section }}

                                </option>

                            @endforeach

                        </select>

                    </div>

                    <div class="mb-3">

                        <label class="form-label">

                            Date

                        </label>

                        <input type="date"
                               name="session_date"
                               id="session_date"
                               class="form-control"
                               required>

                    </div>

                    <div class="mb-3">

                        <label class="form-label">

                            From Time

                        </label>

                        <input type="time"
                            name="from_time"
                            class="form-control"
                            required>

                    </div>

                    <div class="mb-3">

                        <label class="form-label">

                            To Time

                        </label>

                        <input type="time"
                            name="to_time"
                            class="form-control"
                            required>

                    </div>

                    <div class="mb-3">

                        <label class="form-label">

                            Day

                        </label>

                        <input type="text"
                               id="day"
                               class="form-control"
                               readonly>

                    </div>

                    <div class="mb-3">

                        <label class="form-label">

                            Day Type

                        </label>

                        <select name="day_type"
                                class="form-control">

                            <option value="Working Day">

                                Working Day

                            </option>

                            <option value="Holiday">

                                Holiday

                            </option>

                        </select>

                    </div>

                </div>

                <div class="modal-footer">

                    <button type="submit"
                            class="btn btn-success">

                        Save

                    </button>

                </div>

            </form>

        </div>

    </div>

</div>

<script>

document.getElementById('session_date')
.addEventListener('change', function () {

    let selectedDate = new Date(this.value);

    let day = selectedDate.toLocaleDateString(
        'en-US',
        { weekday: 'long' }
    );

    document.getElementById('day').value = day;
});

</script>

@endsection