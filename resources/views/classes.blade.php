@extends('layouts.app')

@section('content')

@php
    $sectionOptions = ['A', 'B', 'C', 'D', 'E'];
    $classSectionOptions = array_merge($sectionOptions, ['Combined']);
@endphp

<div class="container-fluid">
    <div class="row">

        @include('layouts.sidebar')

        <div class="col-md-10 col-lg-10 p-4">

            <div class="page-header d-flex justify-content-between align-items-center flex-wrap gap-3 mb-4">
                <div>
                    <h2 class="mb-1">Class Management</h2>
                    <p class="text-muted mb-0">
                        Manage classes, sections, institutes, and academic year details.
                    </p>
                </div>

                <button class="btn btn-primary btn-sm"
                        data-bs-toggle="modal"
                        data-bs-target="#addClassModal">
                    Add Class
                </button>
            </div>

            @if(session('success'))
                <div class="alert alert-success">{{ session('success') }}</div>
            @endif

            @if($errors->any())
                <div class="alert alert-danger">{{ $errors->first() }}</div>
            @endif

            @include('partials.section-navigator', [
                'sectionPager' => $sectionPager ?? null,
                'sectionDescription' => 'Browse classes institute by institute to keep the management page focused.',
            ])

            @include('partials.section-navigator', [
                'sectionPager' => $classSectionPager ?? null,
                'sectionDescription' => 'Browse one class at a time inside the selected institute.',
            ])

            @include('partials.section-navigator', [
                'sectionPager' => $sectionOnlyPager ?? null,
                'sectionDescription' => 'Showing this section only.',
            ])

            @if(!empty($selectedClassName))
                <div class="alert alert-light border d-flex justify-content-between align-items-center flex-wrap gap-2">
                    <div>
                        <span class="fw-semibold">Current class and section:</span>
                        Class {{ $selectedClassName }}
                        @if(!empty($selectedSectionName))
                            · Section {{ $selectedSectionName }}
                        @endif
                    </div>

                    @if(!empty($managedInstitute))
                        <span class="text-muted small">{{ $managedInstitute }}</span>
                    @endif
                </div>
            @endif

            <div class="card shadow border-0">
                <div class="card-body">

                    <form method="GET" action="{{ route('classes') }}" class="row mb-3">
                        @if(request()->has('section_page'))
                            <input type="hidden" name="section_page" value="{{ request('section_page') }}">
                        @endif

                        @if(request()->has('class_page'))
                            <input type="hidden" name="class_page" value="{{ request('class_page') }}">
                        @endif

                        @if(request()->has('class_name_filter'))
                            <input type="hidden" name="class_name_filter" value="{{ request('class_name_filter') }}">
                        @endif

                        @if(request()->has('section_page_filter'))
                            <input type="hidden" name="section_page_filter" value="{{ request('section_page_filter') }}">
                        @endif

                        @if(request()->has('section_name_filter'))
                            <input type="hidden" name="section_name_filter" value="{{ request('section_name_filter') }}">
                        @endif

                        <div class="col-md-4">
                            <input type="text"
                                   name="search"
                                   class="form-control"
                                   placeholder="Search by class, section, or academic year"
                                   value="{{ request('search') }}">
                        </div>

                        <div class="col-md-2">
                            <button type="submit" class="btn btn-primary w-100">
                                Search
                            </button>
                        </div>
                    </form>

                    <div class="table-responsive lms-table-shell">
                    <table class="table table-bordered table-hover align-middle lms-table-fit">
                        <thead class="table-light">
                            <tr>
                                <th>Sl. No</th>
                                <th>Class</th>
                                <th>Institute</th>
                                <th>Section</th>
                                <th>Academic Year</th>
                                <th>Status</th>
                                <th width="180">Actions</th>
                            </tr>
                        </thead>

                        <tbody>
                            @php
                                $classRows = method_exists($classes, 'getCollection') ? $classes->getCollection() : collect($classes);
                                $groupedClasses = $classRows
                                    ->sortBy([
                                        ['institute', 'asc'],
                                        ['class_name', 'asc'],
                                        ['section', 'asc'],
                                    ])
                                    ->groupBy(fn ($class) => $class->institute ?: 'Unassigned Institute');
                                $rowNumber = 1;
                            @endphp

                            @forelse($groupedClasses as $instituteName => $instituteClasses)
                                <tr class="table-primary">
                                    <td colspan="7" class="fw-semibold">
                                        {{ $instituteName }} · {{ $instituteClasses->count() }} class section{{ $instituteClasses->count() == 1 ? '' : 's' }}
                                    </td>
                                </tr>

                                @foreach($instituteClasses->groupBy('class_name') as $className => $classSections)
                                    <tr class="table-light">
                                        <td colspan="7" class="fw-semibold ps-4">
                                            Class {{ $className }} · {{ $classSections->count() }} section{{ $classSections->count() == 1 ? '' : 's' }}
                                        </td>
                                    </tr>

                                    @foreach($classSections as $class)
                                        <tr>
                                            <td>{{ $rowNumber++ }}</td>
                                            <td>{{ $class->class_name }}</td>
                                            <td>{{ $class->institute ?? 'N/A' }}</td>
                                            <td>{{ $class->section }}</td>
                                            <td>{{ $class->academic_year }}</td>
                                            <td>
                                                @if($class->status == 1)
                                                    <span class="badge bg-success">Active</span>
                                                @else
                                                    <span class="badge bg-danger">Inactive</span>
                                                @endif
                                            </td>

                                            <td>
                                                <button class="btn btn-sm btn-outline-primary"
                                                        data-bs-toggle="modal"
                                                        data-bs-target="#editClassModal{{ $class->id }}">
                                                    Edit
                                                </button>

                                                <a href="{{ route('classes.delete', $class->id) }}"
                                                   class="btn btn-sm btn-outline-danger"
                                                   onclick="return confirm('Are you sure you want to delete this class?')">
                                                    Delete
                                                </a>
                                            </td>
                                        </tr>
                                    @endforeach
                                @endforeach

                                @foreach($instituteClasses as $class)
                                    <div class="modal fade" id="editClassModal{{ $class->id }}" tabindex="-1">
                                    <div class="modal-dialog modal-lg modal-dialog-centered">
                                        <div class="modal-content">

                                            <form method="POST" action="{{ route('classes.update', $class->id) }}">
                                                @csrf

                                                <div class="modal-header">
                                                    <h5 class="modal-title">Edit Class</h5>
                                                    <button type="button"
                                                            class="btn-close"
                                                            data-bs-dismiss="modal">
                                                    </button>
                                                </div>

                                                <div class="modal-body">

                                                    <div class="row g-3">

                                                        <div class="col-md-6">
                                                            <label class="form-label">Class Name</label>
                                                            <input type="text"
                                                                   name="class_name"
                                                                   class="form-control"
                                                                   value="{{ $class->class_name }}"
                                                                   required>
                                                        </div>

                                                        <div class="col-md-6">
                                                            <label class="form-label">Institute</label>

                                                            @if(session('user_role') == 'InstituteAdmin')
                                                                <input type="hidden"
                                                                       name="institute"
                                                                       value="{{ session('user_institute') }}">

                                                                <input type="text"
                                                                       class="form-control"
                                                                       value="{{ session('user_institute') }}"
                                                                       readonly>
                                                            @else
                                                                <input type="text"
                                                                       name="institute"
                                                                       class="form-control"
                                                                       value="{{ $class->institute }}"
                                                                       required>
                                                            @endif
                                                        </div>

                                                        <div class="col-md-6">
                                                            <label class="form-label">Section</label>
                                                            <select name="section" class="form-control" required>
                                                                @if(!in_array($class->section, $classSectionOptions))
                                                                    <option value="{{ $class->section }}" selected>
                                                                        {{ $class->section }}
                                                                    </option>
                                                                @endif

                                                                @foreach($classSectionOptions as $sectionOption)
                                                                    <option value="{{ $sectionOption }}" {{ $class->section == $sectionOption ? 'selected' : '' }}>
                                                                        {{ $sectionOption }}
                                                                    </option>
                                                                @endforeach
                                                            </select>
                                                        </div>

                                                        <div class="col-md-6">
                                                            <label class="form-label">Academic Year</label>
                                                            <input type="text"
                                                                   name="academic_year"
                                                                   class="form-control"
                                                                   value="{{ $class->academic_year }}"
                                                                   required>
                                                        </div>

                                                        <div class="col-md-6">
                                                            <label class="form-label">Status</label>
                                                            <select name="status"
                                                                    class="form-control"
                                                                    required>
                                                                <option value="1" {{ $class->status == 1 ? 'selected' : '' }}>
                                                                    Active
                                                                </option>

                                                                <option value="0" {{ $class->status == 0 ? 'selected' : '' }}>
                                                                    Inactive
                                                                </option>
                                                            </select>
                                                        </div>

                                                    </div>

                                                </div>

                                                <div class="modal-footer">
                                                    <button type="button"
                                                            class="btn btn-outline-secondary"
                                                            data-bs-dismiss="modal">
                                                        Cancel
                                                    </button>

                                                    <button type="submit"
                                                        class="btn btn-primary">
                                                        Update Class
                                                    </button>
                                                </div>

                                            </form>

                                        </div>
                                    </div>
                                </div>
                                @endforeach

                            @empty

                                <tr>
                                    <td colspan="7" class="text-center text-muted">
                                        No classes found
                                    </td>
                                </tr>

                            @endforelse
                        </tbody>
                    </table>
                    </div>

                </div>

                @if(method_exists($classes, 'links'))
                    <div class="px-3 pb-3">
                        {{ $classes->links('pagination::bootstrap-5') }}
                    </div>
                @endif
            </div>

        </div>
    </div>
</div>

<div class="modal fade" id="addClassModal" tabindex="-1">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content">

            <form method="POST" action="{{ route('classes.store') }}">
                @csrf

                <div class="modal-header">
                    <h5 class="modal-title">Add Class</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>

                <div class="modal-body">
                    <div class="row g-3">

                        <div class="col-md-6">
                            <label class="form-label">Class Name</label>
                            <input type="text"
                                   name="class_name"
                                   class="form-control"
                                   placeholder="Example: VIII"
                                   required>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">Institute</label>

                            @if(session('user_role') == 'InstituteAdmin')
                                <input type="hidden"
                                       name="institute"
                                       value="{{ session('user_institute') }}">

                                <input type="text"
                                       class="form-control"
                                       value="{{ session('user_institute') }}"
                                       readonly>
                            @else
                                <input type="text"
                                       name="institute"
                                       class="form-control"
                                       placeholder="Enter institute name"
                                       required>
                            @endif
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">Section</label>
                            <div class="dropdown">
                                <button class="btn btn-outline-secondary dropdown-toggle w-100 text-start"
                                        type="button"
                                        data-bs-toggle="dropdown"
                                        data-bs-auto-close="outside"
                                        aria-expanded="false">
                                    Select section(s)
                                </button>

                                <div class="dropdown-menu w-100 p-3">
                                    @foreach($classSectionOptions as $sectionOption)
                                        <div class="form-check">
                                            <input class="form-check-input"
                                                   type="checkbox"
                                                   name="sections[]"
                                                   value="{{ $sectionOption }}"
                                                   id="addSection{{ $sectionOption }}">
                                            <label class="form-check-label" for="addSection{{ $sectionOption }}">
                                                {{ $sectionOption }}
                                            </label>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                            <div class="form-text">
                                Select A and B to create separate class rows. Combined is a separate section.
                            </div>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">Academic Year</label>
                            <input type="text"
                                   name="academic_year"
                                   class="form-control"
                                   placeholder="2026"
                                   required>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">Status</label>
                            <select name="status" class="form-control" required>
                                <option value="1">Active</option>
                                <option value="0">Inactive</option>
                            </select>
                        </div>

                    </div>
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Save Class</button>
                </div>

            </form>

        </div>
    </div>
</div>

@endsection
