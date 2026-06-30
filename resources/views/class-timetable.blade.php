@extends('layouts.app')

@section('content')

<div class="container-fluid">
    <div class="row">

        @include('layouts.sidebar')

        <div class="col-md-10 col-lg-10 p-4">

            <div class="page-header d-flex justify-content-between align-items-center flex-wrap gap-3 mb-4">

                <div>
                    <h2>Class Timetable</h2>
                    <p class="text-muted mb-0">
                        Manage weekly timetable schedules.
                    </p>
                </div>

                <div class="d-flex gap-2 flex-wrap">

                    <a href="{{ route('timetable.copy.week', ['week' => $weekOffset]) }}"
                       class="btn btn-success">
                        Copy To Next Week
                    </a>

                    <button class="btn btn-primary"
                            data-bs-toggle="modal"
                            data-bs-target="#addTimetableModal">
                        Create New Timetable
                    </button>

                </div>

            </div>

            <div class="card shadow-sm border-0 mb-4">
                <div class="card-body d-flex justify-content-between align-items-center flex-wrap gap-3">

                    <a href="{{ route('timetable', ['week' => $weekOffset - 1]) }}"
                       class="btn btn-outline-secondary">
                        ← Previous Week
                    </a>

                    <h5 class="mb-0">
                        {{ $weekStart->format('d M') }}
                        -
                        {{ $weekEnd->format('d M Y') }}
                    </h5>

                    <a href="{{ route('timetable', ['week' => $weekOffset + 1]) }}"
                       class="btn btn-outline-secondary">
                        Next Week →
                    </a>

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

            @php
                $days = [
                    'Monday',
                    'Tuesday',
                    'Wednesday',
                    'Thursday',
                    'Friday',
                    'Saturday'
                ];

                $groupedTimetables = $timetables->groupBy('day');
            @endphp

            @foreach($days as $day)

                <div class="card shadow border-0 mb-4">

                    <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">

                        <h5 class="mb-0">
                            {{ $day }}
                        </h5>

                        <span class="badge bg-light text-dark">
                            {{ isset($groupedTimetables[$day]) ? $groupedTimetables[$day]->count() : 0 }} Sessions
                        </span>

                    </div>

                    <div class="card-body">

                        <div class="table-responsive">

                            <table class="table table-bordered table-hover align-middle mb-0">

                                <thead class="table-light">
                                    <tr>
                                        <th>Class</th>
                                        <th>Scheduled Topic</th>
                                        <th>Date</th>
                                        <th>Day Type</th>
                                        <th>Status</th>
                                        <th>From</th>
                                        <th>To</th>
                                        <th width="120">Action</th>
                                    </tr>
                                </thead>

                                <tbody>

                                    @forelse($groupedTimetables[$day] ?? [] as $row)

                                        <tr>

                                            <td>
                                                {{ $row->schoolClass->class_name ?? 'N/A' }}

                                                @if($row->schoolClass)
                                                    -
                                                    {{ $row->schoolClass->section }}
                                                @endif
                                            </td>

                                            <td>
                                                @if($row->content)

                                                    {{ $row->content->content_title }}

                                                    <br>

                                                    <small class="text-muted">
                                                        Lesson {{ $row->content->lesson_order }}
                                                    </small>

                                                @else

                                                    <span class="text-muted">
                                                        N/A
                                                    </span>

                                                @endif
                                            </td>

                                            <td>
                                                {{ \Carbon\Carbon::parse($row->session_date)->format('d-m-Y') }}
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
                                                @if($row->status == 'Scheduled')

                                                    <span class="badge bg-primary">
                                                        Scheduled
                                                    </span>

                                                @elseif($row->status == 'Started')

                                                    <span class="badge bg-warning text-dark">
                                                        Live
                                                    </span>

                                                @elseif($row->status == 'Completed')

                                                    <span class="badge bg-success">
                                                        Completed
                                                    </span>

                                                @elseif($row->status == 'Cancelled')

                                                    <span class="badge bg-danger">
                                                        Cancelled
                                                    </span>

                                                @else

                                                    <span class="badge bg-secondary">
                                                        {{ $row->status }}
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
                                                <div class="d-flex flex-column gap-2">

                                                    <button class="btn btn-sm btn-warning"
                                                            data-bs-toggle="modal"
                                                            data-bs-target="#editTimetableModal{{ $row->id }}">
                                                        Edit
                                                    </button>

                                                    <a href="{{ route('timetable.delete', $row->id) }}"
                                                       class="btn btn-sm btn-danger"
                                                       onclick="return confirm('Delete timetable entry?')">
                                                        Delete
                                                    </a>

                                                </div>
                                            </td>

                                        </tr>

                                    @empty

                                        <tr>
                                            <td colspan="8"
                                                class="text-center text-muted py-4">
                                                No classes scheduled for {{ $day }}.
                                            </td>
                                        </tr>

                                    @endforelse

                                </tbody>

                            </table>

                        </div>

                    </div>

                </div>

            @endforeach

        </div>

    </div>
</div>

{{-- Add Timetable Modal --}}
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
                                    {{ $class->class_name }} - {{ $class->section }}
                                </option>

                            @endforeach

                        </select>

                    </div>

                    <div class="mb-3">

                        <label class="form-label">
                            Content / Topic
                        </label>

                        <select name="content_id"
                                class="form-select"
                                required>

                            <option value="">
                                Select Content
                            </option>

                            @foreach($contents as $content)

                                <option value="{{ $content->id }}">
                                    {{ $content->content_title }}
                                    (Lesson {{ $content->lesson_order }})
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

                    <button type="button"
                            class="btn btn-light"
                            data-bs-dismiss="modal">
                        Cancel
                    </button>

                    <button type="submit"
                            class="btn btn-success">
                        Save
                    </button>

                </div>

            </form>

        </div>

    </div>

</div>

{{-- Edit Timetable Modals --}}
@foreach($timetables as $row)

    <div class="modal fade"
         id="editTimetableModal{{ $row->id }}"
         tabindex="-1">

        <div class="modal-dialog">

            <div class="modal-content">

                <form method="POST"
                      action="{{ route('timetable.update', $row->id) }}">

                    @csrf

                    <div class="modal-header">

                        <h5 class="modal-title">
                            Edit Timetable
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

                                @foreach($classes as $class)

                                    <option value="{{ $class->id }}"
                                        {{ $row->class_id == $class->id ? 'selected' : '' }}>

                                        {{ $class->class_name }} - {{ $class->section }}

                                    </option>

                                @endforeach

                            </select>

                        </div>

                        <div class="mb-3">

                            <label class="form-label">
                                Content / Topic
                            </label>

                            <select name="content_id"
                                    class="form-select"
                                    required>

                                <option value="">
                                    Select Content
                                </option>

                                @foreach($contents as $content)

                                    <option value="{{ $content->id }}"
                                        {{ $row->content_id == $content->id ? 'selected' : '' }}>

                                        {{ $content->content_title }}
                                        (Lesson {{ $content->lesson_order }})

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
                                   class="form-control"
                                   value="{{ $row->session_date }}"
                                   required>

                        </div>

                        <div class="mb-3">

                            <label class="form-label">
                                From Time
                            </label>

                            <input type="time"
                                   name="from_time"
                                   class="form-control"
                                   value="{{ $row->from_time }}"
                                   required>

                        </div>

                        <div class="mb-3">

                            <label class="form-label">
                                To Time
                            </label>

                            <input type="time"
                                   name="to_time"
                                   class="form-control"
                                   value="{{ $row->to_time }}"
                                   required>

                        </div>

                        <div class="mb-3">

                            <label class="form-label">
                                Day Type
                            </label>

                            <select name="day_type"
                                    class="form-control">

                                <option value="Working Day"
                                    {{ $row->day_type == 'Working Day' ? 'selected' : '' }}>
                                    Working Day
                                </option>

                                <option value="Holiday"
                                    {{ $row->day_type == 'Holiday' ? 'selected' : '' }}>
                                    Holiday
                                </option>

                            </select>

                        </div>

                    </div>

                    <div class="modal-footer">

                        <button type="button"
                                class="btn btn-light"
                                data-bs-dismiss="modal">
                            Cancel
                        </button>

                        <button type="submit"
                                class="btn btn-success">
                            Update
                        </button>

                    </div>

                </form>

            </div>

        </div>

    </div>

@endforeach

<script>
    const sessionDateInput = document.getElementById('session_date');

    if (sessionDateInput) {
        sessionDateInput.addEventListener('change', function () {
            let selectedDate = new Date(this.value);

            let day = selectedDate.toLocaleDateString(
                'en-US',
                { weekday: 'long' }
            );

            document.getElementById('day').value = day;
        });
    }
</script>

@endsection