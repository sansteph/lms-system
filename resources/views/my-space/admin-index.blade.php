@extends('layouts.app')

@section('content')

<div class="container-fluid">
    <div class="row">

        @include('layouts.sidebar')

        <div class="col-md-10 col-lg-10 p-4">

            <div class="page-header mb-4">
                <h2>
                    @if(($submitterType ?? null) == 'Teacher')
                        STEM Engineer My Space Review
                    @elseif(($submitterType ?? null) == 'Student')
                        Student My Space Review
                    @else
                        My Space Review
                    @endif
                </h2>
                <p class="text-muted mb-0">
                    Review, approve, reject, and feature submitted ideas and projects.
                </p>
            </div>

            @if(session('success'))
                <div class="alert alert-success">
                    {{ session('success') }}
                </div>
            @endif

            @include('partials.section-navigator', [
                'sectionPager' => $sectionPager ?? null,
                'sectionDescription' => 'Browse My Space submissions institute by institute.',
            ])

            @if(($submitterType ?? null) == 'Student')
                @include('partials.section-navigator', [
                    'sectionPager' => $classSectionPager ?? null,
                    'sectionDescription' => 'Browse student submissions one class at a time inside the selected institute.',
                ])

                @include('partials.section-navigator', [
                    'sectionPager' => $studentSectionPager ?? null,
                    'sectionDescription' => 'Showing student submissions from this section only.',
                ])

                @if(!empty($selectedStudentClass))
                    <div class="alert alert-light border d-flex justify-content-between align-items-center flex-wrap gap-2">
                        <div>
                            <span class="fw-semibold">Current student scope:</span>
                            Class {{ $selectedStudentClass }}
                            @if(!empty($selectedStudentSection))
                                &middot; Section {{ $selectedStudentSection }}
                            @endif
                        </div>

                        @if(!empty($currentInstitute))
                            <span class="text-muted small">{{ $currentInstitute }}</span>
                        @endif
                    </div>
                @endif
            @endif

            <div class="card shadow border-0">
                <div class="card-body">

                    <div class="table-responsive">

                        <table class="table table-hover align-middle">

                            <thead>
                                <tr>
                                    <th>Title</th>
                                    <th>Type</th>
                                    <th>Submitter</th>
                                    <th>Role</th>
                                    <th>Status</th>
                                    <th width="220">Actions</th>
                                </tr>
                            </thead>

                            <tbody>

                                @php
                                    $itemRows = method_exists($items, 'getCollection') ? $items->getCollection() : collect($items);
                                    $teacherItems = $itemRows->where('created_by_type', 'Teacher');
                                    $studentItems = $itemRows->where('created_by_type', 'Student');
                                    $sections = [
                                        'STEM Engineer Reviews' => $teacherItems,
                                        'Student Reviews' => $studentItems,
                                    ];
                                    $hasItems = $itemRows->isNotEmpty();
                                @endphp

                                @forelse($sections as $sectionTitle => $sectionItems)
                                    @continue($sectionItems->isEmpty())

                                    <tr class="table-primary">
                                        <td colspan="6" class="fw-semibold">
                                            {{ $sectionTitle }} · {{ $sectionItems->count() }} submission{{ $sectionItems->count() == 1 ? '' : 's' }}
                                        </td>
                                    </tr>

                                    @php
                                        $groupedItems = $sectionItems->groupBy(function ($item) {
                                            $submitter = $item->submitter();

                                            if ($item->created_by_type == 'Student') {
                                                return ($submitter->institute ?? 'Student Deleted / Unassigned Institute') . '|' . trim(($submitter->class ?? '') . ' ' . ($submitter->section ?? ''));
                                            }

                                            return ($submitter->institute ?? 'STEM Engineer Deleted / Unassigned Institute') . '|';
                                        });
                                    @endphp

                                    @foreach($groupedItems as $groupKey => $groupItems)
                                        @php
                                            [$instituteName, $classLabel] = array_pad(explode('|', $groupKey, 2), 2, '');
                                        @endphp

                                        <tr class="table-light">
                                            <td colspan="6" class="fw-semibold ps-4">
                                                {{ $instituteName }}
                                                @if($classLabel)
                                                    · {{ $classLabel }}
                                                @endif
                                                · {{ $groupItems->count() }} submission{{ $groupItems->count() == 1 ? '' : 's' }}
                                            </td>
                                        </tr>

                                        @foreach($groupItems as $item)

                                    <tr>
                                        <td>{{ $item->title }}</td>

                                        <td>
                                            <span class="badge bg-info">
                                                {{ $item->type }}
                                            </span>
                                        </td>

                                        <td>

                                            @php
                                                $submitter = $item->submitter();
                                            @endphp

                                            @if($submitter)

                                                <strong>{{ $submitter->name }}</strong>

                                                <br>

                                                <small class="text-muted">

                                                    {{ $item->created_by_type == 'Student'
                                                        ? $submitter->student_id
                                                        : $submitter->user_id }}

                                                </small>

                                            @else

                                                Deleted User

                                            @endif

                                        </td>

                                        <td>
                                            {{ $item->created_by_type }}
                                        </td>

                                        <td>
                                            @if($item->status == 'Approved')
                                                <span class="badge bg-success">Approved</span>
                                            @elseif($item->status == 'Rejected')
                                                <span class="badge bg-danger">Rejected</span>
                                            @elseif($item->status == 'Featured')
                                                <span class="badge bg-warning text-dark">Featured</span>
                                            @else
                                                <span class="badge bg-secondary">{{ $item->status }}</span>
                                            @endif
                                        </td>

                                        <td>
                                            <div class="d-flex flex-wrap gap-2">

                                                <a href="{{ route('admin.my-space.show', $item->id) }}" class="btn btn-sm btn-primary">
                                                    View
                                                </a>

                                                <form method="POST"
                                                      action="{{ route('admin.my-space.approve', $item->id) }}">
                                                    @csrf
                                                    <button type="submit"
                                                            class="btn btn-sm btn-success">
                                                        Approve
                                                    </button>
                                                </form>

                                                <form method="POST"
                                                      action="{{ route('admin.my-space.reject', $item->id) }}">
                                                    @csrf
                                                    <button type="submit"
                                                            class="btn btn-sm btn-outline-danger">
                                                        Reject
                                                    </button>
                                                </form>

                                                <form method="POST"
                                                      action="{{ route('admin.my-space.feature', $item->id) }}">
                                                    @csrf
                                                    <button type="submit"
                                                            class="btn btn-sm btn-outline-primary">
                                                        Feature
                                                    </button>
                                                </form>

                                            </div>
                                        </td>
                                    </tr>

                                        @endforeach

                                    @endforeach

                                @empty

                                @endforelse

                                @if(!$hasItems)
                                    <tr>
                                        <td colspan="6" class="text-center text-muted py-4">
                                            No My Space submissions found.
                                        </td>
                                    </tr>
                                @endif

                            </tbody>

                        </table>

                    </div>

                </div>

                @if(method_exists($items, 'links'))
                    <div class="px-3 pb-3">
                        {{ $items->links('pagination::bootstrap-5') }}
                    </div>
                @endif
            </div>

        </div>

    </div>
</div>

@endsection
