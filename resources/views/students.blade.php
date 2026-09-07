@extends('layouts.app')

@section('content')
@php
    $studentManagementContext = $studentManagementContext ?? 'admin';
    $isTeacherStudentManagement = $studentManagementContext === 'teacher';
    $managedInstitute = $managedInstitute ?? session('user_institute');
    $showFilterPlaceholder = $showFilterPlaceholder ?? false;
    $studentRouteNames = [
        'index' => $isTeacherStudentManagement ? 'teacher.student-management' : 'students',
        'store' => $isTeacherStudentManagement ? 'teacher.students.store' : 'students.store',
        'bulkUpload' => $isTeacherStudentManagement ? 'teacher.students.bulk-upload' : 'students.bulk-upload',
        'bulkTemplate' => $isTeacherStudentManagement ? 'teacher.students.bulk-template' : 'students.bulk-template',
        'update' => $isTeacherStudentManagement ? 'teacher.students.update' : 'students.update',
        'delete' => $isTeacherStudentManagement ? 'teacher.students.delete' : 'students.delete',
    ];
@endphp

<div class="container-fluid">
    <div class="row">
        @include($isTeacherStudentManagement ? 'layouts.teacher-sidebar' : 'layouts.sidebar')

        <div class="col-md-10 col-lg-10 p-4">
            <div class="page-header d-flex justify-content-between align-items-center flex-wrap gap-3 mb-4">
                <div>
                    <h2 class="mb-1">Student Management</h2>
                    <p class="text-muted mb-0">Manage student records, sections, and status.</p>
                </div>

                <div class="d-flex gap-2 flex-wrap">
                    <a href="{{ route($studentRouteNames['bulkTemplate']) }}" class="btn btn-outline-primary btn-sm">
                        Download CSV Template
                    </a>

                    <button class="btn btn-outline-primary btn-sm" data-bs-toggle="modal" data-bs-target="#bulkUploadStudentsModal">
                        Bulk Upload
                    </button>

                    <button class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#addStudentModal">
                        Add Student
                    </button>
                </div>
            </div>

            @if(session('success'))
                <div class="alert alert-success">
                    {{ session('success') }}
                </div>
            @endif

            @if($errors->any())
                <div class="alert alert-danger">
                    {{ $errors->first() }}
                </div>
            @endif

            @if(session('bulk_upload_errors'))
                <div class="alert alert-warning">
                    <div class="fw-semibold mb-2">Skipped rows</div>
                    <ul class="mb-0">
                        @foreach(session('bulk_upload_errors') as $bulkUploadError)
                            <li>{{ $bulkUploadError }}</li>
                        @endforeach
                    </ul>
                    @if(count(session('bulk_upload_errors')) >= 30)
                        <div class="small text-muted mt-2">Only the first 30 skipped-row messages are shown.</div>
                    @endif
                </div>
            @endif

            <div class="card border-0 shadow-sm mb-3">
                <div class="card-body">
                    <div class="d-flex align-items-center gap-2 mb-3">
                        <span class="badge bg-primary-subtle text-primary">Filter Students</span>
                        <span class="text-muted small">Select the relevant class and section, then search for a specific learner when needed.</span>
                    </div>
                    <form method="GET" action="{{ route($studentRouteNames['index']) }}" class="row g-3 align-items-end">
                        @if(request()->has('section_page'))
                            <input type="hidden" name="section_page" value="{{ request('section_page') }}">
                        @endif
                        @if(request()->has('class_page'))
                            <input type="hidden" name="class_page" value="{{ request('class_page') }}">
                        @endif
                        @if(request()->has('student_class'))
                            <input type="hidden" name="student_class" value="{{ request('student_class') }}">
                        @endif
                        @if(request()->has('student_section_page'))
                            <input type="hidden" name="student_section_page" value="{{ request('student_section_page') }}">
                        @endif
                        @if(request()->has('student_section'))
                            <input type="hidden" name="student_section" value="{{ request('student_section') }}">
                        @endif

                        @if(session('user_role') === 'Admin' && !$isTeacherStudentManagement)
                            <div class="col-md-3">
                                <label class="form-label">Institute</label>
                                <select name="institute" class="form-select">
                                    <option value="">All Institutes</option>
                                    @foreach(($instituteOptions ?? collect()) as $instituteOption)
                                        <option value="{{ $instituteOption }}" {{ request('institute') === $instituteOption ? 'selected' : '' }}>
                                            {{ $instituteOption }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                        @endif

                        @if($isTeacherStudentManagement || in_array(session('user_role'), ['Admin', 'InstituteAdmin'], true))
                            <div class="col-md-3">
                                <label class="form-label">Class</label>
                                <select name="student_class" class="form-select">
                                    <option value="">All Classes</option>
                                    @foreach(($classOptions ?? collect()) as $classOption)
                                        <option value="{{ $classOption }}" {{ request('student_class') === $classOption ? 'selected' : '' }}>
                                            {{ $classOption }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="col-md-3">
                                <label class="form-label">Section</label>
                                <select name="student_section" class="form-select">
                                    <option value="">All Sections</option>
                                    @foreach(($sectionOptions ?? collect()) as $sectionOption)
                                        <option value="{{ $sectionOption }}" {{ request('student_section') === $sectionOption ? 'selected' : '' }}>
                                            {{ $sectionOption }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                        @endif

                        <div class="col-md-4">
                            <label class="form-label">Search</label>
                            <input type="text"
                                   name="search"
                                   class="form-control"
                                   placeholder="Search by name or ID"
                                   value="{{ request('search') }}">
                        </div>

                        <div class="col-md-2">
                            <button type="submit" class="btn btn-primary w-100">Apply Filters</button>
                        </div>
                    </form>
                </div>
            </div>

            @if($showFilterPlaceholder)
                @include('partials.filter-placeholder')
            @else
                <div class="card shadow border-0">
                    <div class="card-body">
                        <div class="table-responsive lms-table-shell">
                            <table class="table table-bordered table-hover align-middle lms-table-fit">
                                <thead class="table-light">
                                    <tr>
                                        <th>Sl. No</th>
                                        <th>Student ID</th>
                                        <th>Name</th>
                                        <th>Institute</th>
                                        <th>Class</th>
                                        <th>Section</th>
                                        <th>Contact</th>
                                        <th>Status</th>
                                        <th width="180">Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @php
                                        $studentRows = method_exists($students, 'getCollection') ? $students->getCollection() : collect($students);
                                        $groupedStudents = $studentRows
                                            ->sortBy([
                                                ['institute', 'asc'],
                                                ['class', 'asc'],
                                                ['section', 'asc'],
                                                ['name', 'asc'],
                                            ])
                                            ->groupBy(fn ($student) => $student->institute ?: 'Unassigned Institute');
                                        $rowNumber = 1;
                                    @endphp

                                    @forelse($groupedStudents as $instituteName => $instituteStudents)
                                        <tr class="table-primary">
                                            <td colspan="9" class="fw-semibold">
                                                {{ $instituteName }} - {{ $instituteStudents->count() }} student{{ $instituteStudents->count() == 1 ? '' : 's' }}
                                            </td>
                                        </tr>

                                        @foreach($instituteStudents->groupBy(fn ($student) => trim($student->class . ' ' . $student->section)) as $classLabel => $classStudents)
                                            <tr class="table-light">
                                                <td colspan="9" class="fw-semibold ps-4">
                                                    {{ $classLabel ?: 'Unassigned Class' }} - {{ $classStudents->count() }} student{{ $classStudents->count() == 1 ? '' : 's' }}
                                                </td>
                                            </tr>

                                            @foreach($classStudents as $student)
                                                <tr>
                                                    <td>{{ $rowNumber++ }}</td>
                                                    <td>{{ $student->student_id }}</td>
                                                    <td>{{ $student->name }}</td>
                                                    <td>{{ $student->institute }}</td>
                                                    <td>{{ $student->class }}</td>
                                                    <td>{{ $student->section }}</td>
                                                    <td>{{ $student->contact }}</td>
                                                    <td>
                                                        @if($student->status)
                                                            <span class="badge bg-success">Active</span>
                                                        @else
                                                            <span class="badge bg-danger">Inactive</span>
                                                        @endif
                                                    </td>
                                                    <td>
                                                        <button class="btn btn-sm btn-outline-primary"
                                                                data-bs-toggle="modal"
                                                                data-bs-target="#editStudentModal{{ $student->id }}">
                                                            Edit
                                                        </button>

                                                        <a href="{{ route($studentRouteNames['delete'], $student->id) }}"
                                                           class="btn btn-sm btn-outline-danger"
                                                           onclick="return confirm('Are you sure you want to delete this student? This will also remove their assessment history, badges, and certificate eligibility.')">
                                                            Delete
                                                        </a>
                                                    </td>
                                                </tr>
                                            @endforeach
                                        @endforeach

                                        @foreach($instituteStudents as $student)
                                            <tr>
                                                <td colspan="9" class="p-0 border-0">
                                            <div class="modal fade" id="editStudentModal{{ $student->id }}" tabindex="-1" aria-hidden="true">
                                                <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
                                                    <div class="modal-content">
                                                        <form method="POST" action="{{ route($studentRouteNames['update'], $student->id) }}">
                                                            @csrf

                                                            <div class="modal-header">
                                                                <h5 class="modal-title">Edit Student</h5>
                                                                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                                            </div>

                                                            <div class="modal-body">
                                                                <div class="row g-3">
                                                                    <div class="col-md-6">
                                                                        <label class="form-label">Student ID</label>
                                                                        <input type="text" name="student_id" class="form-control" value="{{ $student->student_id }}" required>
                                                                    </div>

                                                                    <div class="col-md-6">
                                                                        <label class="form-label">Student Name</label>
                                                                        <input type="text" name="name" class="form-control" value="{{ $student->name }}" required>
                                                                    </div>

                                                                    <div class="col-md-6">
                                                                        <label class="form-label">Institute</label>
                                                                        @if(in_array(session('user_role'), ['InstituteAdmin', 'Teacher'], true))
                                                                            <input type="hidden" name="institute" value="{{ $managedInstitute }}">
                                                                            <input type="text" class="form-control" value="{{ $managedInstitute }}" readonly>
                                                                        @else
                                                                            <select name="institute" class="form-select" required>
                                                                                <option value="">Select institute</option>
                                                                                @foreach(($instituteOptions ?? collect()) as $instituteOption)
                                                                                    <option value="{{ $instituteOption }}" {{ $student->institute === $instituteOption ? 'selected' : '' }}>
                                                                                        {{ $instituteOption }}
                                                                                    </option>
                                                                                @endforeach
                                                                                @if($student->institute && !collect($instituteOptions ?? [])->contains($student->institute))
                                                                                    <option value="{{ $student->institute }}" selected>{{ $student->institute }}</option>
                                                                                @endif
                                                                            </select>
                                                                        @endif
                                                                    </div>

                                                                    <div class="col-md-3">
                                                                        <label class="form-label">Class</label>
                                                                        <select name="class" class="form-select" required>
                                                                            <option value="">Select class</option>
                                                                            @foreach(($classOptions ?? collect()) as $classOption)
                                                                                <option value="{{ $classOption }}" {{ $student->class === $classOption ? 'selected' : '' }}>
                                                                                    {{ $classOption }}
                                                                                </option>
                                                                            @endforeach
                                                                            @if($student->class && !collect($classOptions ?? [])->contains($student->class))
                                                                                <option value="{{ $student->class }}" selected>{{ $student->class }}</option>
                                                                            @endif
                                                                        </select>
                                                                    </div>

                                                                    <div class="col-md-3">
                                                                        <label class="form-label">Section</label>
                                                                        <select name="section" class="form-select" required>
                                                                            <option value="">Select section</option>
                                                                            @foreach(($sectionOptions ?? collect()) as $sectionOption)
                                                                                <option value="{{ $sectionOption }}" {{ $student->section === $sectionOption ? 'selected' : '' }}>
                                                                                    {{ $sectionOption }}
                                                                                </option>
                                                                            @endforeach
                                                                            @if($student->section && !collect($sectionOptions ?? [])->contains($student->section))
                                                                                <option value="{{ $student->section }}" selected>{{ $student->section }}</option>
                                                                            @endif
                                                                        </select>
                                                                    </div>

                                                                    <div class="col-md-6">
                                                                        <label class="form-label">Contact</label>
                                                                        <input type="text" name="contact" class="form-control" value="{{ $student->contact }}" required>
                                                                        <div class="form-text">This number will also be used as the guardian contact.</div>
                                                                    </div>

                                                                    <div class="col-md-6">
                                                                        <label class="form-label">Email Address</label>
                                                                        <input type="email" name="email" class="form-control" value="{{ $student->email }}">
                                                                    </div>

                                                                    <div class="col-md-6">
                                                                        <label class="form-label">Guardian Name</label>
                                                                        <input type="text" name="guardian_name" class="form-control" value="{{ $student->guardian_name }}">
                                                                    </div>

                                                                    <div class="col-md-6">
                                                                        <label class="form-label">Password</label>
                                                                        <input type="password" name="password" class="form-control" placeholder="Leave blank to keep existing password" autocomplete="new-password">
                                                                    </div>

                                                                    <div class="col-md-6">
                                                                        <label class="form-label">Robotics Club Member</label>
                                                                        <select name="is_robotics_club_member" class="form-control" required>
                                                                            <option value="0" {{ !$student->is_robotics_club_member ? 'selected' : '' }}>No</option>
                                                                            <option value="1" {{ $student->is_robotics_club_member ? 'selected' : '' }}>Yes</option>
                                                                        </select>
                                                                    </div>

                                                                    <div class="col-md-6">
                                                                        <label class="form-label">Status</label>
                                                                        <select name="status" class="form-control" required>
                                                                            <option value="1" {{ $student->status == 1 ? 'selected' : '' }}>Active</option>
                                                                            <option value="0" {{ $student->status == 0 ? 'selected' : '' }}>Inactive</option>
                                                                        </select>
                                                                    </div>
                                                                </div>
                                                            </div>

                                                            <div class="modal-footer">
                                                                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                                                                <button type="submit" class="btn btn-primary">Update Student</button>
                                                            </div>
                                                        </form>
                                                    </div>
                                                </div>
                                            </div>
                                                </td>
                                            </tr>
                                        @endforeach
                                    @empty
                                        <tr>
                                            <td colspan="9" class="text-center text-muted">No students found</td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>

                    @if(method_exists($students, 'links'))
                        <div class="px-3 pb-3">
                            {{ $students->links('pagination::bootstrap-5') }}
                        </div>
                    @endif
                </div>
            @endif
        </div>
    </div>
</div>

<div class="modal fade" id="addStudentModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content">
            <form method="POST" action="{{ route($studentRouteNames['store']) }}">
                @csrf

                <div class="modal-header">
                    <h5 class="modal-title">Add Student</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>

                <div class="modal-body">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">Student ID</label>
                            <input type="text" name="student_id" class="form-control" value="{{ old('student_id') }}" required>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">Student Name</label>
                            <input type="text" name="name" class="form-control" value="{{ old('name') }}" required>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">Institute</label>
                            @if(in_array(session('user_role'), ['InstituteAdmin', 'Teacher'], true))
                                <input id="addStudentInstitute" type="hidden" name="institute" value="{{ $managedInstitute }}">
                                <input type="text" class="form-control" value="{{ $managedInstitute }}" readonly>
                            @else
                                <select id="addStudentInstitute" name="institute" class="form-select" required>
                                    <option value="">Select institute</option>
                                    @foreach(($instituteOptions ?? collect()) as $instituteOption)
                                        <option value="{{ $instituteOption }}" {{ old('institute') === $instituteOption ? 'selected' : '' }}>
                                            {{ $instituteOption }}
                                        </option>
                                    @endforeach
                                    @if(old('institute') && !collect($instituteOptions ?? [])->contains(old('institute')))
                                        <option value="{{ old('institute') }}" selected>{{ old('institute') }}</option>
                                    @endif
                                </select>
                            @endif
                        </div>

                        <div class="col-md-3">
                            <label class="form-label">Class</label>
                            <select id="addStudentClass" name="class" class="form-select" required>
                                <option value="">Select class</option>
                                @foreach(($classOptions ?? collect()) as $classOption)
                                    <option value="{{ $classOption }}" {{ old('class') === $classOption ? 'selected' : '' }}>
                                        {{ $classOption }}
                                    </option>
                                @endforeach
                                @if(old('class') && !collect($classOptions ?? [])->contains(old('class')))
                                    <option value="{{ old('class') }}" selected>{{ old('class') }}</option>
                                @endif
                            </select>
                        </div>

                        <div class="col-md-3">
                            <label class="form-label">Section</label>
                            <select id="addStudentSection" name="section" class="form-select" required>
                                <option value="">Select section</option>
                                @foreach(($sectionOptions ?? collect()) as $sectionOption)
                                    <option value="{{ $sectionOption }}" {{ old('section') === $sectionOption ? 'selected' : '' }}>
                                        {{ $sectionOption }}
                                    </option>
                                @endforeach
                                @if(old('section') && !collect($sectionOptions ?? [])->contains(old('section')))
                                    <option value="{{ old('section') }}" selected>{{ old('section') }}</option>
                                @endif
                            </select>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">Contact</label>
                            <input type="text" name="contact" class="form-control" value="{{ old('contact') }}" required>
                            <div class="form-text">Use this as the student or guardian contact number.</div>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">Email Address</label>
                            <input type="email" name="email" class="form-control" value="{{ old('email') }}">
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">Guardian Name</label>
                            <input type="text" name="guardian_name" class="form-control" value="{{ old('guardian_name') }}">
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">Password</label>
                            <input type="password" name="password" class="form-control" placeholder="Enter Password" required>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">Robotics Club Member</label>
                            <select name="is_robotics_club_member" class="form-control" required>
                                <option value="0" {{ old('is_robotics_club_member', '0') == '0' ? 'selected' : '' }}>No</option>
                                <option value="1" {{ old('is_robotics_club_member') == '1' ? 'selected' : '' }}>Yes</option>
                            </select>
                        </div>
                    </div>
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Save Student</button>
                </div>
            </form>
        </div>
    </div>
</div>

<div class="modal fade" id="bulkUploadStudentsModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content">
            <form method="POST" action="{{ route($studentRouteNames['bulkUpload']) }}" enctype="multipart/form-data">
                @csrf

                <div class="modal-header">
                    <h5 class="modal-title">Bulk Upload Students</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>

                <div class="modal-body">
                    <div class="alert alert-info">
                        Upload a CSV using the provided template. Valid rows will be added; invalid or duplicate rows will be skipped with row-wise reasons.
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Student CSV File</label>
                        <input type="file" name="students_csv" class="form-control" accept=".csv,text/csv,text/plain" required>
                    </div>

                    <div class="border rounded p-3 bg-light small">
                        <div class="fw-semibold mb-2">Required columns</div>
                        <div>
                            student_id, name,
                            @if(session('user_role') == 'Admin')
                                institute,
                            @endif
                            class, section, contact, password
                        </div>
                        <div class="fw-semibold mt-3 mb-2">Optional columns</div>
                        <div>email, guardian_name, is_robotics_club_member, status</div>
                        @if(in_array(session('user_role'), ['InstituteAdmin', 'Teacher'], true))
                            <div class="text-muted mt-3">
                                Uploads are automatically assigned to {{ $managedInstitute }}, even if the CSV contains a different institute value.
                            </div>
                        @endif
                    </div>
                </div>

                <div class="modal-footer">
                    <a href="{{ route($studentRouteNames['bulkTemplate']) }}" class="btn btn-outline-primary me-auto">
                        Download Template
                    </a>
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Upload Students</button>
                </div>
            </form>
        </div>
    </div>
</div>

<style>
    #addStudentModal .modal-body,
    [id^="editStudentModal"] .modal-body {
        max-height: 70vh;
        overflow-y: auto;
    }
</style>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const instituteField = document.getElementById('addStudentInstitute');
    const classField = document.getElementById('addStudentClass');
    const sectionField = document.getElementById('addStudentSection');
    const sectionMap = @json($sectionOptionsByInstituteClass ?? []);

    if (!instituteField || !classField || !sectionField) return;

    const refreshSections = function () {
        const selectedInstitute = instituteField.value || '';
        const selectedClass = classField.value || '';
        const selectedSection = sectionField.value || '';
        const sectionSet = new Set();
        const instituteBuckets = selectedInstitute
            ? [sectionMap[selectedInstitute] || {}]
            : Object.values(sectionMap);

        instituteBuckets.forEach(function (classes) {
            (classes[selectedClass] || []).forEach(function (section) {
                sectionSet.add(section);
            });
        });

        sectionField.replaceChildren(new Option('Select section', ''));
        Array.from(sectionSet).sort().forEach(function (section) {
            sectionField.add(new Option(section, section, false, section === selectedSection));
        });
        if (selectedSection && sectionSet.has(selectedSection)) {
            sectionField.value = selectedSection;
        }
    };

    instituteField.addEventListener('change', refreshSections);
    classField.addEventListener('change', refreshSections);
    refreshSections();
});
</script>
@endpush

@endsection
