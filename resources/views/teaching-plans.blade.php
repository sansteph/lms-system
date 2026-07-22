@extends('layouts.app')

@section('content')

<style>
    .teaching-plan-section-navigator {
        border: 1px solid #dbe7f4;
        border-radius: 14px;
        background: linear-gradient(135deg, #f8fbff 0%, #eef6ff 100%);
        box-shadow: 0 14px 32px rgba(15, 23, 42, 0.07);
        padding: 18px;
    }

    .teaching-plan-section-pill {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        border-radius: 999px;
        background: #0f3b7a;
        color: #ffffff;
        padding: 8px 14px;
        font-weight: 700;
        font-size: 13px;
    }

    .teaching-plan-nav-button {
        min-width: 190px;
        border-radius: 12px;
        padding: 10px 16px;
        font-weight: 700;
    }

    .teaching-plan-nav-button small {
        display: block;
        font-size: 11px;
        font-weight: 500;
        opacity: .72;
    }

    @media(max-width: 576px) {
        .teaching-plan-nav-button {
            width: 100%;
        }
    }
</style>

<div class="container-fluid">
    <div class="row">
        @include('layouts.sidebar')

        <div class="col-md-10 col-lg-10 p-4">
            <div class="page-header d-flex justify-content-between align-items-center flex-wrap gap-3 mb-4">
                <div>
                    <h2 class="mb-1">Teaching Plans</h2>
                    <p class="text-muted mb-0">
                        Manage reusable Teaching Plan Templates and live institute teaching progress.
                    </p>
                </div>

                <div class="d-flex gap-2">
                    @if(!app()->environment('production'))
                        <form method="POST" action="{{ route('teaching-plans.run-release-check') }}">
                            @csrf
                            <button class="btn btn-outline-primary btn-sm">Run Release Check</button>
                        </form>
                    @endif
                    @if(session('user_role') == 'Admin')
                        <button class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#deployTeachingPlanTemplatesModal">
                            Deploy Templates
                        </button>
                        <button class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#createTeachingPlanTemplateModal">
                            Create Template
                        </button>
                    @endif
                    @if(session('user_role') != 'Admin' || !$teachingPlanSectionPager || $teachingPlanSectionPager['current_type'] == 'institute')
                        <button class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#createTeachingPlanModal">
                            Generate Institute Plan
                        </button>
                    @endif
                </div>
            </div>

            @if(session('success'))
                <div class="alert alert-success">{{ session('success') }}</div>
            @endif

            @if(session('error'))
                <div class="alert alert-danger">{{ session('error') }}</div>
            @endif

            @if(session('deployment_messages'))
                <div class="alert alert-info">
                    @foreach(session('deployment_messages') as $message)
                        <div>{{ $message }}</div>
                    @endforeach
                </div>
            @endif

            @if($errors->any())
                <div class="alert alert-danger">{{ $errors->first() }}</div>
            @endif

            @if(session('user_role') == 'Admin' && $teachingPlanSectionPager)
                <div class="teaching-plan-section-navigator mb-4">
                    <div class="d-flex justify-content-between align-items-center flex-wrap gap-3">
                        <div>
                            <div class="teaching-plan-section-pill mb-2">
                                Section {{ $teachingPlanSectionPager['current_page'] }} of {{ $teachingPlanSectionPager['last_page'] }}
                            </div>
                            <h5 class="mb-1">{{ $teachingPlanSectionPager['current_label'] }}</h5>
                            <p class="text-muted mb-0">
                                Browse reusable templates first, then institute teaching plans one institute at a time.
                            </p>
                        </div>

                        <div class="d-flex gap-2 flex-wrap">
                            @if($teachingPlanSectionPager['previous_url'])
                                <a href="{{ $teachingPlanSectionPager['previous_url'] }}" class="btn btn-outline-primary teaching-plan-nav-button">
                                    Previous
                                    <small>{{ $teachingPlanSectionPager['previous_label'] }}</small>
                                </a>
                            @else
                                <button type="button" class="btn btn-outline-secondary teaching-plan-nav-button" disabled>
                                    Previous
                                    <small>Start of list</small>
                                </button>
                            @endif

                            @if($teachingPlanSectionPager['next_url'])
                                <a href="{{ $teachingPlanSectionPager['next_url'] }}" class="btn btn-primary teaching-plan-nav-button">
                                    Next
                                    <small>{{ $teachingPlanSectionPager['next_label'] }}</small>
                                </a>
                            @else
                                <button type="button" class="btn btn-outline-secondary teaching-plan-nav-button" disabled>
                                    Next
                                    <small>End of list</small>
                                </button>
                            @endif
                        </div>
                    </div>
                </div>
            @endif

            @if(session('user_role') == 'Admin' && (!$teachingPlanSectionPager || $teachingPlanSectionPager['current_type'] == 'templates'))
                <div class="card shadow border-0 mb-4">
                    <div class="card-body">
                        <h5 class="mb-3">Teaching Plan Templates</h5>
                        <div class="table-responsive">
                            <table class="table table-bordered table-hover align-middle mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th>Template</th>
                                        <th>Class</th>
                                        <th>Course</th>
                                        <th>Release</th>
                                        <th>Deployments</th>
                                        <th>Status</th>
                                        <th width="120">Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($templates as $template)
                                        <tr>
                                            <td>
                                                <strong>{{ $template->title ?? $template->course->course_title ?? 'Template' }}</strong>
                                                @if($template->remarks)
                                                    <br><small class="text-muted">{{ \Illuminate\Support\Str::limit($template->remarks, 80) }}</small>
                                                @endif
                                            </td>
                                            <td>{{ $template->class }} {{ $template->section }}</td>
                                            <td>{{ $template->course->course_title ?? 'Course removed' }}</td>
                                            <td>{{ $template->release_day }} / {{ $template->contents_per_week }} per batch</td>
                                            <td>{{ $template->deployed_plans_count }}</td>
                                            <td>{{ ucfirst($template->status) }}</td>
                                            <td>
                                                <a href="{{ route('teaching-plans.delete', $template->id) }}"
                                                   class="btn btn-sm btn-outline-danger"
                                                   onclick="return confirm('Delete this Teaching Plan Template? Course contents and deployed institute plans will be kept.')">
                                                    Delete
                                                </a>
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="7" class="text-center text-muted">No Teaching Plan Templates created yet.</td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            @endif

            @if(session('user_role') != 'Admin' || !$teachingPlanSectionPager || $teachingPlanSectionPager['current_type'] == 'institute')
                @php
                    $deployedPlans = $plans->whereNotNull('parent_template_id');
                    $standalonePlans = $plans->whereNull('parent_template_id');
                @endphp

                @foreach([
                    'Deployed Template Plans' => $deployedPlans,
                    'Standalone Institute Plans' => $standalonePlans,
                ] as $sectionTitle => $sectionPlans)
                    <h5 class="mb-3 mt-4">{{ $sectionTitle }}</h5>

                    @forelse($sectionPlans->groupBy(fn ($plan) => $plan->institute ?: 'Unassigned Institute') as $instituteName => $institutePlans)
                    <div class="card shadow border-0 mb-4">
                        <div class="card-header bg-primary text-white fw-semibold">
                            {{ $instituteName }} · {{ $institutePlans->count() }} plan{{ $institutePlans->count() == 1 ? '' : 's' }}
                        </div>
                        <div class="card-body">
                            @foreach($institutePlans->sortBy(fn ($plan) => trim($plan->class . ' ' . $plan->section))->groupBy(fn ($plan) => trim($plan->class . ' ' . $plan->section) ?: 'Unassigned Class') as $classLabel => $classPlans)
                                <div class="border rounded p-3 mb-3">
                                    <h6 class="fw-semibold mb-3">{{ $classLabel }} · {{ $classPlans->count() }} plan{{ $classPlans->count() == 1 ? '' : 's' }}</h6>

                                    <div class="row g-4">
                                        @foreach($classPlans as $plan)
                                            <div class="col-12">
                                                <div class="card border">
                                                    <div class="card-body">
                                                        <div class="d-flex justify-content-between align-items-start flex-wrap gap-3 mb-3">
                                                            <div>
                                                                <h5 class="mb-1">{{ $plan->title ?? $plan->course->course_title ?? 'Course' }}</h5>
                                                                <div class="text-muted small">
                                                                    {{ $plan->class }} {{ $plan->section }} |
                                                                    Starts {{ $plan->start_date ? \Carbon\Carbon::parse($plan->start_date)->format('d M Y') : 'Not set' }} |
                                                                    {{ $plan->contents_per_week }} content(s) per week
                                                                    @if($plan->parentTemplate)
                                                                        | From template #{{ $plan->parentTemplate->id }}
                                                                    @endif
                                                                </div>
                                                            </div>
                                                            <div class="d-flex gap-2 flex-wrap">
                                                                <span class="badge bg-{{ $plan->status == 'active' ? 'success' : ($plan->status == 'completed' ? 'primary' : 'secondary') }}">
                                                                    {{ ucfirst($plan->status) }}
                                                                </span>
                                                                <form method="POST" action="{{ route('teaching-plans.release-next', $plan->id) }}">
                                                                    @csrf
                                                                    <button class="btn btn-sm btn-outline-primary">Release Next Week</button>
                                                                </form>
                                                                <button class="btn btn-sm btn-outline-warning" data-bs-toggle="modal" data-bs-target="#addLaggedContentModal{{ $plan->id }}">
                                                                    Add Lagged Content
                                                                </button>
                                                                <button class="btn btn-sm btn-outline-secondary" data-bs-toggle="modal" data-bs-target="#editTeachingPlanModal{{ $plan->id }}">
                                                                    Edit
                                                                </button>
                                                                <a href="{{ route('teaching-plans.delete', $plan->id) }}"
                                                                   class="btn btn-sm btn-outline-danger"
                                                                   onclick="return confirm('Delete this Teaching Plan? Session history will stay, but plan links will be cleared.')">
                                                                    Delete
                                                                </a>
                                                            </div>
                                                        </div>

                                                        <div class="table-responsive">
                                                            <table class="table table-bordered table-hover align-middle">
                                                                <thead class="table-light">
                                                                    <tr>
                                                                        <th style="width: 90px;">Week</th>
                                                                        <th>Release</th>
                                                                        <th>Status</th>
                                                                        <th>Contents</th>
                                                                        <th style="width: 210px;">Week Control</th>
                                                                    </tr>
                                                                </thead>
                                                                <tbody>
                                                                    @foreach($plan->weeks->sortBy('week_number') as $week)
                                                                        <tr>
                                                                            <td>Week {{ $week->week_number }}</td>
                                                                            <td>
                                                                                {{ $week->release_date ? $week->release_date->format('d M Y') : '-' }}
                                                                                <div class="text-muted small">
                                                                                    {{ $week->week_start_date ? $week->week_start_date->format('d M') : '-' }}
                                                                                    -
                                                                                    {{ $week->week_end_date ? $week->week_end_date->format('d M Y') : '-' }}
                                                                                </div>
                                                                            </td>
                                                                            <td>{{ ucfirst($week->status) }}</td>
                                                                            <td>
                                                                                @foreach($week->items->sortBy('sort_order') as $item)
                                                                                    <div class="border rounded p-2 mb-2">
                                                                                        <div>
                                                                                            <strong>{{ $item->content->content_title ?? 'Content' }}</strong>
                                                                                            <div class="text-muted small">Order {{ $item->sort_order }} | {{ ucfirst($item->status) }}</div>
                                                                                        </div>
                                                                                    </div>
                                                                                @endforeach
                                                                            </td>
                                                                            <td>
                                                                                <form method="POST" action="{{ route('teaching-plans.weeks.update', [$plan->id, $week->id]) }}" class="d-flex gap-2">
                                                                                    @csrf
                                                                                    <select name="status" class="form-select form-select-sm">
                                                                                        @foreach(['locked', 'released', 'completed', 'skipped'] as $status)
                                                                                            <option value="{{ $status }}" {{ $week->status == $status ? 'selected' : '' }}>{{ ucfirst($status) }}</option>
                                                                                        @endforeach
                                                                                    </select>
                                                                                    <button class="btn btn-sm btn-outline-primary">Save</button>
                                                                                </form>
                                                                            </td>
                                                                        </tr>
                                                                    @endforeach
                                                                </tbody>
                                                            </table>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        @endforeach
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                    @empty
                        <div class="card shadow border-0 mb-4">
                            <div class="card-body text-center text-muted">No {{ strtolower($sectionTitle) }} found.</div>
                        </div>
                    @endforelse
                @endforeach
            @endif
        </div>
    </div>
</div>

@if(session('user_role') == 'Admin')
    <div class="modal fade" id="createTeachingPlanTemplateModal" tabindex="-1">
        <div class="modal-dialog modal-lg modal-dialog-scrollable">
            <div class="modal-content">
                <form method="POST" action="{{ route('teaching-plan-templates.store') }}">
                    @csrf
                    <div class="modal-header">
                        <h5 class="modal-title">Create Teaching Plan Template</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label">Template Name</label>
                                <input type="text" name="title" class="form-control" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Blueprint Course</label>
                                <select name="course_id" class="form-control" required>
                                    <option value="">Select Course</option>
                                    @foreach($templateCourses as $course)
                                        <option value="{{ $course->id }}">
                                            {{ $course->course_title }} ({{ $course->active_course_contents_count ?? $course->course_contents_count }} active contents)
                                            @if($course->is_template_source)
                                                - Template Source
                                            @elseif($course->institute)
                                                - {{ $course->institute }}
                                            @endif
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Class</label>
                                <input type="text" name="class" class="form-control" required>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Section</label>
                                <input type="text" name="section" class="form-control">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Release Day</label>
                                <select name="release_day" class="form-control" required>
                                    @foreach(['Friday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Saturday', 'Sunday'] as $day)
                                        <option value="{{ $day }}" {{ $day == 'Friday' ? 'selected' : '' }}>{{ $day }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Contents Per Batch</label>
                                <input type="number" name="contents_per_week" class="form-control" min="1" max="10" value="2" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Release Policy</label>
                                <select name="release_policy" class="form-control" required>
                                    <option value="release_next_only_if_previous_completed">Release next only if previous completed</option>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Status</label>
                                <select name="status" class="form-control" required>
                                    <option value="active">Active</option>
                                    <option value="inactive">Inactive</option>
                                </select>
                            </div>
                            <div class="col-md-12">
                                <label class="form-label">Remarks</label>
                                <textarea name="remarks" class="form-control" rows="3"></textarea>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">Save Template</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="modal fade" id="deployTeachingPlanTemplatesModal" tabindex="-1">
        <div class="modal-dialog modal-lg modal-dialog-scrollable">
            <div class="modal-content">
                <form method="POST" action="{{ route('teaching-plan-templates.deploy') }}">
                    @csrf
                    <div class="modal-header">
                        <h5 class="modal-title">Deploy Teaching Plan Templates</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <p class="text-muted small mb-3">
                            Select institutes and the classes they conduct. Matching active Teaching Plan Templates will be deployed as live Institute Teaching Plans.
                        </p>
                        <div class="border rounded p-3" style="max-height: 420px; overflow-y: auto;">
                            @foreach($institutes as $institute)
                                @php
                                    $availableClasses = $classesByInstitute->get($institute->institute_name, collect());
                                @endphp
                                <div class="border rounded p-3 mb-3 bg-light">
                                    <label class="fw-semibold d-block mb-2">
                                        <input type="checkbox" name="selected_institute_ids[]" value="{{ $institute->id }}" class="me-2">
                                        {{ $institute->institute_name }}
                                    </label>

                                    @if($availableClasses->isNotEmpty())
                                        <div class="row g-2">
                                            @foreach($availableClasses as $className)
                                                <div class="col-md-6">
                                                    <label class="small d-block">
                                                        <input type="checkbox"
                                                               name="classes_by_institute[{{ $institute->id }}][]"
                                                               value="{{ $className }}"
                                                               class="me-1">
                                                        {{ $className }}
                                                    </label>
                                                </div>
                                            @endforeach
                                        </div>
                                    @else
                                        <div class="text-muted small mb-2">No active classes found for this institute.</div>
                                    @endif

                                    <input type="text"
                                           name="custom_classes_by_institute[{{ $institute->id }}]"
                                           class="form-control form-control-sm mt-2"
                                           placeholder="Optional extra classes, comma separated">
                                </div>
                            @endforeach
                        </div>
                    </div>
                    <div class="modal-footer flex-wrap gap-2 position-sticky bottom-0 bg-white">
                        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">Deploy Templates</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endif

<div class="modal fade" id="createTeachingPlanModal" tabindex="-1">
    <div class="modal-dialog modal-lg modal-dialog-scrollable">
        <div class="modal-content">
            <form method="POST" action="{{ route('teaching-plans.store') }}">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title">Generate Weekly Teaching Plan</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">Course</label>
                            <select name="course_id" class="form-control" required>
                                <option value="">Select Course</option>
                                @foreach($courses as $course)
                                    <option value="{{ $course->id }}">
                                        {{ $course->course_title }} ({{ $course->course_contents_count }} contents)
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Class</label>
                            <input type="text" name="class" class="form-control" required>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Section</label>
                            <input type="text" name="section" class="form-control">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Start Date</label>
                            <input type="date" name="start_date" class="form-control" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Release Day</label>
                            <select name="release_day" class="form-control" required>
                                @foreach(['Friday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Saturday', 'Sunday'] as $day)
                                    <option value="{{ $day }}" {{ $day == 'Friday' ? 'selected' : '' }}>{{ $day }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Contents Per Week</label>
                            <input type="number" name="contents_per_week" class="form-control" min="1" max="10" value="2" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Release Policy</label>
                            <select name="release_policy" class="form-control" required>
                                <option value="release_next_only_if_previous_completed">Release next only if previous completed</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Status</label>
                            <select name="status" class="form-control" required>
                                <option value="active">Active</option>
                                <option value="inactive">Inactive</option>
                            </select>
                        </div>
                        <div class="col-md-12">
                            <label class="form-label">Remarks</label>
                            <textarea name="remarks" class="form-control" rows="3"></textarea>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Generate Plan</button>
                </div>
            </form>
        </div>
    </div>
</div>

@foreach($plans as $plan)
    <div class="modal fade" id="editTeachingPlanModal{{ $plan->id }}" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <form method="POST" action="{{ route('teaching-plans.update', $plan->id) }}">
                    @csrf
                    <div class="modal-header">
                        <h5 class="modal-title">Update Teaching Plan</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label">Status</label>
                            <select name="status" class="form-control" required>
                                @foreach(['active', 'inactive', 'completed'] as $status)
                                    <option value="{{ $status }}" {{ $plan->status == $status ? 'selected' : '' }}>{{ ucfirst($status) }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Remarks</label>
                            <textarea name="remarks" class="form-control" rows="3">{{ $plan->remarks }}</textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">Update</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="modal fade" id="addLaggedContentModal{{ $plan->id }}" tabindex="-1">
        <div class="modal-dialog modal-lg modal-dialog-scrollable">
            <div class="modal-content">
                <form method="POST" action="{{ route('teaching-plans.lagged-content.store', $plan->id) }}">
                    @csrf
                    <div class="modal-header">
                        <h5 class="modal-title">Add Lagged Content</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <p class="text-muted small mb-3">
                            Add catch-up or repeat content for {{ trim($plan->class . ' ' . $plan->section) }}. STEM Engineers will see it under Pending Sessions.
                        </p>

                        <div class="border rounded p-3" style="max-height: 420px; overflow-y: auto;">
                            @forelse(($plan->course?->courseContents ?? collect())->where('status', 'active')->sortBy('sort_order') as $courseContent)
                                <label class="d-flex align-items-start gap-2 border rounded p-2 mb-2">
                                    <input type="checkbox"
                                           name="course_content_ids[]"
                                           value="{{ $courseContent->id }}"
                                           class="mt-1">
                                    <span>
                                        <strong>{{ $courseContent->content->content_title ?? $courseContent->title ?? 'Content' }}</strong>
                                        <span class="d-block text-muted small">
                                            Order {{ $courseContent->sort_order ?? '-' }}
                                        </span>
                                    </span>
                                </label>
                            @empty
                                <div class="text-center text-muted py-3">
                                    No active course content is available for this plan.
                                </div>
                            @endforelse
                        </div>
                    </div>
                    <div class="modal-footer flex-wrap gap-2 position-sticky bottom-0 bg-white">
                        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">Add to Pending Sessions</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endforeach

@endsection
