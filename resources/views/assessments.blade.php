@extends('layouts.app')

@section('content')

<div class="container-fluid">
    <div class="row">
        @include('layouts.teacher-sidebar')

        <div class="col-md-10 col-lg-10 p-4">
            <div class="page-header d-flex justify-content-between align-items-center flex-wrap gap-3 mb-4">
                <div>
                    <h2 class="mb-1">Assessment Management</h2>
                    <p class="text-muted mb-0">Upload question papers and manage assessment settings.</p>
                </div>

                <div class="d-flex gap-2 flex-wrap">
                    <button class="btn btn-outline-primary btn-sm" data-bs-toggle="modal" data-bs-target="#aiAssessmentModal">
                        <i class="fa fa-wand-magic-sparkles me-1"></i>
                        Create with AI
                    </button>
                    <button class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#createAssessmentModal">
                        Upload Question Paper
                    </button>
                </div>
            </div>

            @if(session('success'))
                <div class="alert alert-success">{{ session('success') }}</div>
            @endif

            @if(session('error'))
                <div class="alert alert-danger">{{ session('error') }}</div>
            @endif

            @if($errors->any())
                <div class="alert alert-danger">Please fill all required fields correctly.</div>
            @endif

            @php
                $assessmentRows = method_exists($assessments, 'getCollection') ? $assessments->getCollection() : collect($assessments);
            @endphp

            @include('partials.section-navigator', ['sectionPager' => $sectionPager ?? null])

            <div class="row g-4 mb-4">
                <div class="col-md-3"><div class="dashboard-card"><h6>Total Assessments</h6><h2>{{ $assessmentRows->count() }}</h2></div></div>
                <div class="col-md-3"><div class="dashboard-card"><h6>Pending Approval</h6><h2>{{ $assessmentRows->where('question_paper_status', 'Pending Approval')->count() }}</h2></div></div>
                <div class="col-md-3"><div class="dashboard-card"><h6>Approved Papers</h6><h2>{{ $assessmentRows->where('question_paper_status', 'Approved')->count() }}</h2></div></div>
                <div class="col-md-3"><div class="dashboard-card"><h6>Rejected Papers</h6><h2>{{ $assessmentRows->where('question_paper_status', 'Rejected')->count() }}</h2></div></div>
            </div>

            <div class="card shadow border-0">
                <div class="card-body">
                    <form method="GET" action="{{ route('teacher.assessments') }}" class="row mb-3">
                        <div class="col-md-4">
                            <input type="text" name="search" class="form-control" placeholder="Search by title or class" value="{{ request('search') }}">
                        </div>
                        <div class="col-md-3">
                            <select name="class" class="form-control">
                                <option value="">All Classes</option>
                                @foreach($classOptions as $classOption)
                                    <option value="{{ $classOption }}" {{ ($selectedClass ?? request('class')) == $classOption ? 'selected' : '' }}>
                                        {{ $classOption }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-2">
                            <button type="submit" class="btn btn-primary w-100">Search</button>
                        </div>
                        <div class="col-md-2">
                            <a href="{{ route('teacher.assessments') }}" class="btn btn-outline-secondary w-100">Clear</a>
                        </div>
                    </form>

                    <div class="table-responsive">
                        <table class="table table-bordered table-hover align-middle">
                            <thead class="table-light">
                                <tr>
                                    <th>Sl. No</th>
                                    <th>Assessment Title</th>
                                    <th>Class</th>
                                    <th>Category</th>
                                    <th>Assessment Date</th>
                                    <th>Total Marks</th>
                                    <th>Duration</th>
                                    <th>Question Paper</th>
                                    <th>Approval</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @php $rowNumber = 1; @endphp
                                @forelse($assessmentRows->groupBy(fn ($assessment) => $assessment->assigned_class ?: 'Unassigned Class') as $classLabel => $classAssessments)
                                    <tr class="table-primary">
                                        <td colspan="11" class="fw-semibold">{{ $classLabel }}</td>
                                    </tr>
                                    @foreach($classAssessments as $assessment)
                                        @php
                                            $extension = strtolower(pathinfo($assessment->file_path ?? '', PATHINFO_EXTENSION));
                                            $previewExtensions = ['ppt', 'pptx', 'doc', 'docx'];
                                            $paperVariant = in_array($extension, $previewExtensions) && $assessment->question_paper_preview_path ? 'preview' : 'file';
                                        @endphp
                                        <tr>
                                            <td>{{ $rowNumber++ }}</td>
                                            <td>{{ $assessment->assessment_title }}</td>
                                            <td>{{ $assessment->assigned_class }}</td>
                                            <td>
                                                <span class="badge {{ $assessment->assessment_category == 'Annual' ? 'bg-dark' : 'bg-info' }}">
                                                    {{ $assessment->assessment_category ?? 'Monthly' }}
                                                </span>
                                            </td>
                                            <td>{{ $assessment->assessment_date ? \Carbon\Carbon::parse($assessment->assessment_date)->format('d M Y') : 'Not Set' }}</td>
                                            <td>{{ $assessment->total_marks }}</td>
                                            <td>{{ $assessment->duration }} mins</td>
                                            <td>
                                                @if($assessment->file_path)
                                                    <a href="{{ route('assessment.paper', [$assessment->id, $paperVariant]) }}" target="_blank" class="btn btn-sm btn-outline-primary">View</a>
                                                    @if($assessment->ai_generated)
                                                        <div class="small text-info mt-1">AI generated</div>
                                                    @endif
                                                @else
                                                    <span class="badge bg-secondary">Missing</span>
                                                @endif
                                            </td>
                                            <td>
                                                @if($assessment->question_paper_status == 'Approved')
                                                    <span class="badge bg-success">Approved</span>
                                                @elseif($assessment->question_paper_status == 'Rejected')
                                                    <span class="badge bg-danger">Rejected</span>
                                                @else
                                                    <span class="badge bg-warning text-dark">Pending Approval</span>
                                                @endif
                                            </td>
                                            <td>
                                                @if($assessment->status == 1)
                                                    <span class="badge bg-success">Active</span>
                                                @else
                                                    <span class="badge bg-danger">Inactive</span>
                                                @endif
                                            </td>
                                            <td class="text-nowrap">
                                                <div class="d-flex flex-column gap-2">
                                                    <button class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#editAssessmentModal{{ $assessment->id }}">Edit</button>
                                                    <a href="{{ route('teacher.assessments.delete', $assessment->id) }}" class="btn btn-sm btn-outline-danger" onclick="return confirm('Are you sure you want to delete this assessment?')">Delete</a>
                                                </div>
                                            </td>
                                        </tr>
                                    @endforeach
                                @empty
                                    <tr><td colspan="11" class="text-center text-muted">No assessments found</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    @if(method_exists($assessments, 'links'))
                        <div class="mt-3">
                            {{ $assessments->links('pagination::bootstrap-5') }}
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="aiAssessmentModal" tabindex="-1">
    <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content">
            <form method="POST" action="{{ route('teacher.assessments.ai-generate') }}">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title">Create AI Question Paper</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="row g-3">
                        <div class="col-md-6"><label class="form-label">Assessment Title</label><input type="text" name="assessment_title" class="form-control" required></div>
                        <div class="col-md-6"><label class="form-label">Assessment Type</label><select name="assessment_type" class="form-control" required><option value="Student">Student</option></select></div>
                        <div class="col-md-6"><label class="form-label">STEM Engineer</label><input type="text" class="form-control" value="{{ $teacher->name ?? 'Assigned STEM Engineer' }}" readonly></div>
                        <div class="col-md-6">
                            <label class="form-label">Class</label>
                            <select name="assigned_class" id="aiAssessmentClass" class="form-control" required>
                                <option value="">Select Class</option>
                                @foreach($classOptions as $classOption)
                                    <option value="{{ $classOption }}">{{ $classOption }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-6"><label class="form-label">Assessment Category</label><select name="assessment_category" class="form-control" required><option value="Monthly">Monthly</option><option value="Annual">Annual</option></select></div>
                        <div class="col-md-6"><label class="form-label">Assessment Date</label><input type="date" name="assessment_date" class="form-control" required></div>
                        <div class="col-md-3"><label class="form-label">Start Time</label><input type="time" name="start_time" class="form-control" required></div>
                        <div class="col-md-3"><label class="form-label">End Time</label><input type="time" name="end_time" class="form-control" required></div>
                        <div class="col-md-3"><label class="form-label">Total Marks</label><input type="number" name="total_marks" class="form-control" min="1" value="50" required></div>
                        <div class="col-md-3"><label class="form-label">Duration</label><input type="text" name="duration" class="form-control" value="45" required></div>
                        <div class="col-md-6"><label class="form-label">Status</label><select name="status" class="form-control" required><option value="1">Active after approval</option><option value="0">Inactive</option></select></div>

                        <div class="col-12">
                            <label class="form-label">Select Assessment Contents</label>
                            <div class="ai-content-picker border rounded p-3">
                                @forelse($availableContents as $content)
                                    <label class="ai-content-option" data-class="{{ $content->assessment_class_label }}">
                                        <input type="checkbox" name="content_ids[]" value="{{ $content->id }}">
                                        <span>
                                            <strong>{{ $content->content_title }}</strong>
                                            <small>{{ $content->assessment_class_label }} · Lesson {{ $content->lesson_order ?? 'N/A' }}</small>
                                        </span>
                                    </label>
                                @empty
                                    <div class="alert alert-warning mb-0">
                                        No released or completed teaching plan contents are available for AI assessment generation.
                                    </div>
                                @endforelse
                            </div>
                            <small class="text-muted">Only contents from the selected class will be accepted. Admin approval is still required before students can access the assessment.</small>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Generate & Send for Approval</button>
                </div>
            </form>
        </div>
    </div>
</div>

<div class="modal fade" id="createAssessmentModal" tabindex="-1">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content">
            <form method="POST" action="{{ route('teacher.assessments.store') }}" enctype="multipart/form-data">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title">Upload Question Paper</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body"><div class="row g-3">
                    <div class="col-md-6"><label class="form-label">Assessment Title</label><input type="text" name="assessment_title" class="form-control" required></div>
                    <div class="col-md-6"><label class="form-label">Assessment Type</label><select name="assessment_type" class="form-control" required><option value="Student">Student</option></select></div>
                    <div class="col-md-6"><label class="form-label">STEM Engineer</label><input type="text" class="form-control" value="{{ $teacher->name ?? 'Assigned STEM Engineer' }}" readonly></div>
                    <div class="col-md-6"><label class="form-label">Class</label><select name="assigned_class" class="form-control" required><option value="">Select Class</option>@foreach($classOptions as $classOption)<option value="{{ $classOption }}">{{ $classOption }}</option>@endforeach</select></div>
                    <div class="col-md-6"><label class="form-label">Assessment Category</label><select name="assessment_category" class="form-control" required><option value="Monthly">Monthly</option><option value="Annual">Annual</option></select></div>
                    <div class="col-md-6"><label class="form-label">Assessment Date</label><input type="date" name="assessment_date" class="form-control" required></div>
                    <div class="col-md-3"><label class="form-label">Start Time</label><input type="time" name="start_time" class="form-control"><small class="text-muted">Optional</small></div>
                    <div class="col-md-3"><label class="form-label">End Time</label><input type="time" name="end_time" class="form-control"><small class="text-muted">Optional</small></div>
                    <div class="col-md-3"><label class="form-label">Total Marks</label><input type="number" name="total_marks" class="form-control" min="1" required></div>
                    <div class="col-md-3"><label class="form-label">Duration</label><input type="text" name="duration" class="form-control" placeholder="45" required></div>
                    <div class="col-md-6"><label class="form-label">Approved Question Paper</label><input type="file" name="file" class="form-control" accept=".pdf" required><small class="text-muted">Admin approval is required before students can access it.</small></div>
                    <div class="col-md-6"><label class="form-label">Status</label><select name="status" class="form-control" required><option value="1">Active after approval</option><option value="0">Inactive</option></select></div>
                </div></div>
                <div class="modal-footer"><button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button><button type="submit" class="btn btn-primary">Send for Approval</button></div>
            </form>
        </div>
    </div>
</div>

@foreach($assessments as $assessment)
<div class="modal fade" id="editAssessmentModal{{ $assessment->id }}" tabindex="-1">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content">
            <form method="POST" action="{{ route('teacher.assessments.update', $assessment->id) }}" enctype="multipart/form-data">
                @csrf
                <div class="modal-header"><h5 class="modal-title">Edit Assessment</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
                <div class="modal-body"><div class="row g-3">
                    <div class="col-md-6"><label class="form-label">Assessment Title</label><input type="text" name="assessment_title" class="form-control" value="{{ $assessment->assessment_title }}" required></div>
                    <div class="col-md-6"><label class="form-label">Assessment Type</label><select name="assessment_type" class="form-control" required><option value="Student" selected>Student</option></select></div>
                    <div class="col-md-6"><label class="form-label">STEM Engineer</label><input type="text" class="form-control" value="{{ $assessment->teacher->name ?? ($teacher->name ?? 'Assigned STEM Engineer') }}" readonly></div>
                    <div class="col-md-6"><label class="form-label">Class</label><select name="assigned_class" class="form-control" required>@foreach($classOptions as $classOption)<option value="{{ $classOption }}" {{ $assessment->assigned_class == $classOption ? 'selected' : '' }}>{{ $classOption }}</option>@endforeach</select></div>
                    <div class="col-md-6"><label class="form-label">Assessment Category</label><select name="assessment_category" class="form-control" required><option value="Monthly" {{ $assessment->assessment_category == 'Monthly' ? 'selected' : '' }}>Monthly</option><option value="Annual" {{ $assessment->assessment_category == 'Annual' ? 'selected' : '' }}>Annual</option></select></div>
                    <div class="col-md-6"><label class="form-label">Assessment Date</label><input type="date" name="assessment_date" class="form-control" value="{{ $assessment->assessment_date }}" required></div>
                    <div class="col-md-3"><label class="form-label">Start Time</label><input type="time" name="start_time" class="form-control" value="{{ $assessment->start_time ? \Illuminate\Support\Str::of($assessment->start_time)->substr(0, 5) : '' }}"></div>
                    <div class="col-md-3"><label class="form-label">End Time</label><input type="time" name="end_time" class="form-control" value="{{ $assessment->end_time ? \Illuminate\Support\Str::of($assessment->end_time)->substr(0, 5) : '' }}"></div>
                    <div class="col-md-3"><label class="form-label">Total Marks</label><input type="number" name="total_marks" class="form-control" value="{{ $assessment->total_marks }}" min="1" required></div>
                    <div class="col-md-3"><label class="form-label">Duration</label><input type="text" name="duration" class="form-control" value="{{ $assessment->duration }}" required></div>
                    <div class="col-md-6"><label class="form-label">Replace Question Paper</label><input type="file" name="file" class="form-control" accept=".pdf"><small class="text-muted">Replacing the paper sends it back for approval.</small></div>
                    <div class="col-md-6"><label class="form-label">Status</label><select name="status" class="form-control" required><option value="1" {{ $assessment->status == 1 ? 'selected' : '' }}>Active after approval</option><option value="0" {{ $assessment->status == 0 ? 'selected' : '' }}>Inactive</option></select></div>
                </div></div>
                <div class="modal-footer"><button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button><button type="submit" class="btn btn-primary">Update Assessment</button></div>
            </form>
        </div>
    </div>
</div>
@endforeach

<script>
document.addEventListener('DOMContentLoaded', function () {
    const classSelect = document.getElementById('aiAssessmentClass');
    const options = Array.from(document.querySelectorAll('.ai-content-option'));

    if (!classSelect || !options.length) {
        return;
    }

    const filterOptions = function () {
        const selectedClass = (classSelect.value || '').trim();

        options.forEach(function (option) {
            const optionClass = (option.dataset.class || '').trim();
            const shouldShow = selectedClass && optionClass === selectedClass;

            option.classList.toggle('is-hidden', !shouldShow);

            if (!shouldShow) {
                const checkbox = option.querySelector('input[type="checkbox"]');

                if (checkbox) {
                    checkbox.checked = false;
                }
            }
        });
    };

    classSelect.addEventListener('change', filterOptions);
    filterOptions();
});
</script>

@endsection

