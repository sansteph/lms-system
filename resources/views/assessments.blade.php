@extends('layouts.app')

@section('content')

<div class="container-fluid">
    <div class="row">

        @include('layouts.sidebar')

        <div class="col-md-10 col-lg-10 p-4">

            <div class="page-header d-flex justify-content-between align-items-center flex-wrap gap-3 mb-4">
                <div>
                    <h2 class="mb-1">Assessment Management</h2>

                    <p class="text-muted mb-0">
                        Create student and teacher assessments, generate links, and manage status.
                    </p>
                </div>

                <button class="btn btn-primary btn-sm"
                        data-bs-toggle="modal"
                        data-bs-target="#createAssessmentModal">
                    Create Assessment
                </button>
            </div>

            @if(session('success'))
                <div class="alert alert-success">
                    {{ session('success') }}
                </div>
            @endif

            @if($errors->any())
                <div class="alert alert-danger">
                    Please fill all required fields correctly.
                </div>
            @endif

            <div class="row g-4 mb-4">

                <div class="col-md-3">
                    <div class="dashboard-card">
                        <h6>Total Assessments</h6>
                        <h2>{{ $assessments->count() }}</h2>
                    </div>
                </div>

                <div class="col-md-3">
                    <div class="dashboard-card">
                        <h6>Student Assessments</h6>
                        <h2>{{ $assessments->where('assessment_type', 'Student')->count() }}</h2>
                    </div>
                </div>

                <div class="col-md-3">
                    <div class="dashboard-card">
                        <h6>Teacher Assessments</h6>
                        <h2>{{ $assessments->where('assessment_type', 'Teacher')->count() }}</h2>
                    </div>
                </div>

                <div class="col-md-3">
                    <div class="dashboard-card">
                        <h6>Active Assessments</h6>
                        <h2>{{ $assessments->where('status', 1)->count() }}</h2>
                    </div>
                </div>

            </div>

            <div class="card shadow border-0">

                <div class="card-body">

                    <form method="GET"
                          action="{{ route('assessments') }}"
                          class="row mb-3">

                        <div class="col-md-4">
                            <input type="text"
                                   name="search"
                                   class="form-control"
                                   placeholder="Search by title or class"
                                   value="{{ request('search') }}">
                        </div>

                        <div class="col-md-2">
                            <button type="submit"
                                    class="btn btn-primary w-100">
                                Search
                            </button>
                        </div>

                    </form>

                    <table class="table table-bordered table-hover align-middle">

                        <thead class="table-light">
                            <tr>
                                <th>Sl. No</th>
                                <th>Assessment Title</th>
                                <th>Type</th>
                                <th>Class</th>
                                <th>Total Marks</th>
                                <th>Questions</th>
                                <th>Readiness</th>
                                <th>Duration</th>
                                <th>Status</th>
                                <th width="320">Actions</th>
                            </tr>
                        </thead>

                        <tbody>

                            @forelse($assessments as $index => $assessment)

                                <tr>

                                    <td>{{ $index + 1 }}</td>

                                    <td>{{ $assessment->assessment_title }}</td>

                                    <td>

                                        @if($assessment->assessment_type == 'Student')

                                            <span class="badge bg-primary">
                                                Student
                                            </span>

                                        @else

                                            <span class="badge bg-warning text-dark">
                                                Teacher
                                            </span>

                                        @endif

                                    </td>

                                    <td>{{ $assessment->assigned_class }}</td>

                                    <td>{{ $assessment->calculated_marks }}</td>

                                    <td>{{ $assessment->questions->count() }}</td>
                                    <td>

                                        @if($assessment->questions->count() > 0)

                                            <span class="badge bg-success">
                                                Ready
                                            </span>

                                        @else

                                            <span class="badge bg-danger">
                                                Not Ready
                                            </span>

                                        @endif

                                    </td>

                                    <td>{{ $assessment->duration }}</td>

                                    <td>

                                        @if($assessment->status == 1)

                                            <span class="badge bg-success">
                                                Active
                                            </span>

                                        @else

                                            <span class="badge bg-danger">
                                                Inactive
                                            </span>

                                        @endif

                                    </td>

                                    <td class="text-nowrap">

                                        <div class="d-flex align-items-center gap-2">

                                            <button class="btn btn-sm btn-info">
                                                Link
                                            </button>

                                            <button class="btn btn-sm btn-warning"
                                                    data-bs-toggle="modal"
                                                    data-bs-target="#editAssessmentModal{{ $assessment->id }}">
                                                Edit
                                            </button>

                                            <a href="{{ route('assessments.delete', $assessment->id) }}"
                                            class="btn btn-sm btn-danger"
                                            onclick="return confirm('Are you sure you want to delete this assessment?')">

                                                Delete

                                            </a>

                                        </div>

                                    </td>

                                </tr>

                            @empty

                                <tr>
                                    <td colspan="10"
                                        class="text-center text-muted">
                                        No assessments found
                                    </td>
                                </tr>

                            @endforelse

                        </tbody>

                    </table>

                    <div class="alert alert-info mt-3 mb-0">

                        Assessment link format example:

                        <strong>
                            /student-assessment?institute_id=INS001&student_id=STU001&assessment_id=ASM001
                        </strong>

                    </div>

                </div>

            </div>

        </div>

    </div>
</div>

<!-- Create Assessment Modal -->
<div class="modal fade" id="createAssessmentModal" tabindex="-1">

    <div class="modal-dialog modal-lg modal-dialog-centered">

        <div class="modal-content">

            <form method="POST"
                  action="{{ route('assessments.store') }}"
                  enctype="multipart/form-data">

                @csrf

                <div class="modal-header">

                    <h5 class="modal-title">
                        Create Assessment
                    </h5>

                    <button type="button"
                            class="btn-close"
                            data-bs-dismiss="modal">
                    </button>

                </div>

                <div class="modal-body">

                    <div class="row g-3">

                        <div class="col-md-6">

                            <label class="form-label">
                                Assessment Title
                            </label>

                            <input type="text"
                                   name="assessment_title"
                                   class="form-control"
                                   placeholder="Enter assessment title"
                                   required>

                        </div>

                        <div class="col-md-6">

                            <label class="form-label">
                                Assessment Type
                            </label>

                            <select name="assessment_type"
                                    class="form-control"
                                    required>

                                <option value="">
                                    Select Assessment Type
                                </option>

                                <option value="Student">
                                    Student
                                </option>

                                <option value="Teacher">
                                    Teacher
                                </option>

                            </select>

                        </div>

                        <div class="col-md-6">

                            <label class="form-label">
                                Class / Group
                            </label>

                            <input type="text"
                                   name="assigned_class"
                                   class="form-control"
                                   placeholder="Example: VIII - A"
                                   required>

                        </div>

                        <div class="col-md-3">

                            <label class="form-label">
                                Total Marks
                            </label>

                            <input type="number"
                                   name="total_marks"
                                   class="form-control"
                                   required>

                        </div>

                        <div class="col-md-3">

                            <label class="form-label">
                                Duration
                            </label>

                            <input type="text"
                                   name="duration"
                                   class="form-control"
                                   placeholder="45 mins"
                                   required>

                        </div>

                        <div class="col-md-6">

                            <label class="form-label">
                                Question Paper Type
                            </label>

                            <select name="question_paper_type"
                                    class="form-control">

                                <option value="Upload Question Paper">
                                    Upload Question Paper
                                </option>

                                <option value="Create Questions Later">
                                    Create Questions Later
                                </option>

                            </select>

                        </div>

                        <div class="col-md-6">

                            <label class="form-label">
                                Question Paper File
                            </label>

                            <input type="file"
                                   name="file"
                                   class="form-control">

                        </div>

                        <div class="col-md-6">

                            <label class="form-label">
                                Status
                            </label>

                            <select name="status"
                                    class="form-control"
                                    required>

                                <option value="1">
                                    Active
                                </option>

                                <option value="0">
                                    Inactive
                                </option>

                            </select>

                        </div>

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

                        Save Assessment

                    </button>

                </div>

            </form>

        </div>

    </div>

</div>
@foreach($assessments as $assessment)
<div class="modal fade" id="editAssessmentModal{{ $assessment->id }}" tabindex="-1">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content">

            <form method="POST"
                  action="{{ route('assessments.update', $assessment->id) }}"
                  enctype="multipart/form-data">
                @csrf

                <div class="modal-header">
                    <h5 class="modal-title">Edit Assessment</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>

                <div class="modal-body">
                    <div class="row g-3">

                        <div class="col-md-6">
                            <label class="form-label">Assessment Title</label>
                            <input type="text" name="assessment_title" class="form-control"
                                   value="{{ $assessment->assessment_title }}" required>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">Assessment Type</label>
                            <select name="assessment_type" class="form-control" required>
                                <option value="Student" {{ $assessment->assessment_type == 'Student' ? 'selected' : '' }}>Student</option>
                                <option value="Teacher" {{ $assessment->assessment_type == 'Teacher' ? 'selected' : '' }}>Teacher</option>
                            </select>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">Class / Group</label>
                            <input type="text" name="assigned_class" class="form-control"
                                   value="{{ $assessment->assigned_class }}" required>
                        </div>

                        <div class="col-md-3">
                            <label class="form-label">Total Marks</label>
                            <input type="number" name="total_marks" class="form-control"
                                   value="{{ $assessment->total_marks }}" required>
                        </div>

                        <div class="col-md-3">
                            <label class="form-label">Duration</label>
                            <input type="text" name="duration" class="form-control"
                                   value="{{ $assessment->duration }}" required>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">Question Paper Type</label>
                            <select name="question_paper_type" class="form-control">
                                <option value="Upload Question Paper"
                                    {{ $assessment->question_paper_type == 'Upload Question Paper' ? 'selected' : '' }}>
                                    Upload Question Paper
                                </option>
                                <option value="Create Questions Later"
                                    {{ $assessment->question_paper_type == 'Create Questions Later' ? 'selected' : '' }}>
                                    Create Questions Later
                                </option>
                            </select>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">Replace Question Paper</label>
                            <input type="file" name="file" class="form-control">
                            <small class="text-muted">Leave empty to keep existing file.</small>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">Status</label>
                            <select name="status" class="form-control" required>
                                <option value="1" {{ $assessment->status == 1 ? 'selected' : '' }}>Active</option>
                                <option value="0" {{ $assessment->status == 0 ? 'selected' : '' }}>Inactive</option>
                            </select>
                        </div>

                    </div>
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-success">Update Assessment</button>
                </div>

            </form>

        </div>
    </div>
</div>
@endforeach

@endsection