@extends('layouts.app')

@section('content')

<div class="container-fluid">
    <div class="row">

        @include('layouts.teacher-sidebar')

        <div class="col-md-10 col-lg-10 p-4">

            <div class="page-header d-flex justify-content-between align-items-center mb-4">

                <div>
                    <h2 class="mb-1">
                        Student Results
                    </h2>

                    <p class="text-muted mb-0">
                        View submitted assessments, scores, percentages, badges, and performance analytics.
                    </p>
                </div>

                <div class="d-flex gap-2 flex-wrap">
                    <form method="POST" action="{{ route('teacher.results.ai-insights') }}">
                        @csrf
                        <input type="hidden" name="search" value="{{ request('search') }}">
                        <input type="hidden" name="class" value="{{ request('class') }}">
                        <input type="hidden" name="badge" value="{{ request('badge') }}">
                        <input type="hidden" name="status" value="{{ request('status') }}">
                        <input type="hidden" name="sort" value="{{ request('sort') }}">
                        <button type="submit" class="btn btn-primary">
                            <i class="fa fa-wand-magic-sparkles me-1"></i>
                            AI Insights
                        </button>
                    </form>

                    <form method="POST" action="{{ route('teacher.results.ai-insights.download') }}">
                        @csrf
                        <input type="hidden" name="search" value="{{ request('search') }}">
                        <input type="hidden" name="class" value="{{ request('class') }}">
                        <input type="hidden" name="badge" value="{{ request('badge') }}">
                        <input type="hidden" name="status" value="{{ request('status') }}">
                        <input type="hidden" name="sort" value="{{ request('sort') }}">
                        <button type="submit" class="btn btn-outline-primary">
                            <i class="fa fa-file-pdf me-1"></i>
                            Download AI PDF
                        </button>
                    </form>

                    <a href="{{ route('results.export', request()->query()) }}" class="btn btn-success">
                        <i class="fa fa-file-csv me-1"></i>
                        Export CSV
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
                $aiInsights = session('aiInsights');
            @endphp

            @if($aiInsights)
                <div class="card shadow border-0 mb-4">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-start flex-wrap gap-2 mb-3">
                            <div>
                                <h5 class="mb-1">AI Generated Student Result Insights</h5>
                                <p class="text-muted mb-0">
                                    Generated from the currently filtered student result data{{ isset($aiInsights['generated_at']) ? ' at ' . $aiInsights['generated_at'] : '' }}.
                                </p>
                            </div>
                            @if(!empty($aiInsights['model']))
                                <span class="badge bg-info">{{ $aiInsights['model'] }}</span>
                            @endif
                        </div>

                        <p class="mb-3">{{ $aiInsights['summary'] ?? 'No summary returned.' }}</p>

                        <div class="row g-3">
                            <div class="col-md-4">
                                <h6>Highlights</h6>
                                <ul class="mb-0">
                                    @forelse($aiInsights['highlights'] ?? [] as $item)
                                        <li>{{ $item }}</li>
                                    @empty
                                        <li>No highlights returned.</li>
                                    @endforelse
                                </ul>
                            </div>
                            <div class="col-md-4">
                                <h6>Risks</h6>
                                <ul class="mb-0">
                                    @forelse($aiInsights['risks'] ?? [] as $item)
                                        <li>{{ $item }}</li>
                                    @empty
                                        <li>No risks returned.</li>
                                    @endforelse
                                </ul>
                            </div>
                            <div class="col-md-4">
                                <h6>Recommendations</h6>
                                <ul class="mb-0">
                                    @forelse($aiInsights['recommendations'] ?? [] as $item)
                                        <li>{{ $item }}</li>
                                    @empty
                                        <li>No recommendations returned.</li>
                                    @endforelse
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>
            @endif

            <div class="row g-4 mb-4">

                <div class="col-md-3">
                    <div class="dashboard-card">
                        <h6>Total Results</h6>
                        <h2>{{ $results->count() }}</h2>
                    </div>
                </div>

                <div class="col-md-3">
                    <div class="dashboard-card">
                        <h6>Completed</h6>
                        <h2>
                            {{ $results->where('status', 'Completed')->count() }}
                        </h2>
                    </div>
                </div>

                <div class="col-md-3">
                    <div class="dashboard-card">
                        <h6>Gold Badges</h6>
                        <h2>
                            {{ $results->where('badge', 'Gold')->count() }}
                        </h2>
                    </div>
                </div>

                <div class="col-md-3">
                    <div class="dashboard-card">

                        <h6>Average Percentage</h6>

                        <h2>

                            @if($results->count() > 0)

                                {{ number_format($results->avg('percentage'), 1) }}%

                            @else

                                0%

                            @endif

                        </h2>

                    </div>
                </div>

            </div>

            <div class="row g-4 mb-4">

                <div class="col-md-4">

                    <div class="dashboard-card">

                        <h6>Top Performer</h6>

                        <h5>

                            @if($topPerformer && $topPerformer->student)

                                {{ $topPerformer->student->name }}

                            @else

                                N/A

                            @endif

                        </h5>

                        <small class="text-muted">

                            @if($topPerformer)

                                {{ number_format($topPerformer->percentage, 1) }}%

                            @endif

                        </small>

                    </div>

                </div>

                <div class="col-md-4">

                    <div class="dashboard-card">

                        <h6>Lowest Performer</h6>

                        <h5>

                            @if($lowestPerformer && $lowestPerformer->student)

                                {{ $lowestPerformer->student->name }}

                            @else

                                N/A

                            @endif

                        </h5>

                        <small class="text-muted">

                            @if($lowestPerformer)

                                {{ number_format($lowestPerformer->percentage, 1) }}%

                            @endif

                        </small>

                    </div>

                </div>

                <div class="col-md-4">

                    <div class="dashboard-card">

                        <h6>Pass Percentage</h6>

                        <h5>
                            {{ number_format($passPercentage, 1) }}%
                        </h5>

                    </div>

                </div>

            </div>

            <div class="card shadow border-0 mb-4">

                <div class="card-body">

                    <form method="GET"
                          action="{{ route('teacher.results') }}">

                        <div class="row g-3">

                            <div class="col-md-3">

                                <input type="text"
                                       name="search"
                                       class="form-control"
                                       placeholder="Search student/assessment"
                                       value="{{ request('search') }}">

                            </div>

                            <div class="col-md-2">

                                <select name="class" class="form-control">

                                    <option value="">
                                        All Classes
                                    </option>

                                    @foreach($classOptions as $classOption)
                                        <option value="{{ $classOption }}"
                                            {{ $selectedClass == $classOption ? 'selected' : '' }}>
                                            {{ $classOption }}
                                        </option>
                                    @endforeach

                                </select>

                            </div>

                            <div class="col-md-2">

                                <select name="badge" class="form-control">

                                    <option value="">
                                        All Badges
                                    </option>

                                    <option value="Gold"
                                        {{ request('badge') == 'Gold' ? 'selected' : '' }}>
                                        Gold
                                    </option>

                                    <option value="Silver"
                                        {{ request('badge') == 'Silver' ? 'selected' : '' }}>
                                        Silver
                                    </option>

                                    <option value="Bronze"
                                        {{ request('badge') == 'Bronze' ? 'selected' : '' }}>
                                        Bronze
                                    </option>

                                </select>

                            </div>

                            <div class="col-md-2">

                                <select name="status" class="form-control">

                                    <option value="">
                                        All Status
                                    </option>

                                    <option value="Completed"
                                        {{ request('status') == 'Completed' ? 'selected' : '' }}>
                                        Completed
                                    </option>

                                </select>

                            </div>

                            <div class="col-md-2">

                                <select name="sort" class="form-control">

                                    <option value="">
                                        Sort By
                                    </option>

                                    <option value="highest"
                                        {{ request('sort') == 'highest' ? 'selected' : '' }}>
                                        Highest Percentage
                                    </option>

                                    <option value="lowest"
                                        {{ request('sort') == 'lowest' ? 'selected' : '' }}>
                                        Lowest Percentage
                                    </option>

                                    <option value="latest"
                                        {{ request('sort') == 'latest' ? 'selected' : '' }}>
                                        Latest Results
                                    </option>

                                    <option value="oldest"
                                        {{ request('sort') == 'oldest' ? 'selected' : '' }}>
                                        Oldest Results
                                    </option>

                                </select>

                            </div>

                            <div class="col-md-1">

                                <button type="submit"
                                        class="btn btn-primary w-100">
                                    Find
                                </button>

                            </div>

                            <div class="col-md-1">

                                <a href="{{ route('teacher.results') }}"
                                   class="btn btn-outline-secondary w-100">

                                    Clear

                                </a>

                            </div>

                        </div>

                    </form>

                </div>

            </div>

            <div class="card shadow border-0">

                <div class="card-body">

                    <table class="table table-bordered table-hover align-middle">

                        <thead class="table-light">

                            <tr>

                                <th>Sl. No</th>

                                <th>Student</th>

                                <th>Assessment</th>

                                <th>Category</th>

                                <th>Score</th>

                                <th>Percentage</th>

                                <th>Status</th>

                                <th>Badge</th>

                                <th>Date</th>

                                <th>Action</th>

                            </tr>

                        </thead>

                        <tbody>

                            @php $rowNumber = 1; @endphp
                            @forelse($results->groupBy(fn ($result) => $result->student ? trim($result->student->class . ' ' . $result->student->section) : 'Unassigned Class') as $classLabel => $classResults)
                                <tr class="table-primary">
                                    <td colspan="10" class="fw-semibold">{{ $classLabel }}</td>
                                </tr>
                                @foreach($classResults as $result)

                                <tr>

                                    <td>
                                        {{ $rowNumber++ }}
                                    </td>

                                    <td>

                                        @if($result->student)

                                            {{ $result->student->name }}

                                        @else

                                            Student Deleted

                                        @endif

                                    </td>

                                    <td>

                                        @if($result->assessment)

                                            {{ $result->assessment->assessment_title }}

                                        @else

                                            Assessment Deleted

                                        @endif

                                    </td>

                                    <td>
                                        {{ $result->assessment->assessment_category ?? 'N/A' }}
                                    </td>

                                    <td>
                                        {{ $result->score }}/{{ $result->total_marks }}
                                    </td>

                                    <td>
                                        {{ number_format($result->percentage, 1) }}%
                                    </td>

                                    <td>

                                        <span class="badge bg-success">
                                            {{ $result->status }}
                                        </span>

                                    </td>

                                    <td>

                                        @if($result->badge == 'Gold')

                                            <span class="badge bg-warning text-dark">
                                                Gold
                                            </span>

                                        @elseif($result->badge == 'Silver')

                                            <span class="badge bg-secondary">
                                                Silver
                                            </span>

                                        @elseif($result->badge == 'Bronze')

                                            <span class="badge bg-danger">
                                                Bronze
                                            </span>

                                        @else

                                            <span class="badge bg-light text-dark">
                                                No Badge
                                            </span>

                                        @endif

                                    </td>

                                    <td>
                                        {{ $result->assessment && $result->assessment->assessment_date ? \Carbon\Carbon::parse($result->assessment->assessment_date)->format('d-m-Y') : $result->created_at->format('d-m-Y') }}
                                    </td>

                                    <td>
                                        <form method="POST"
                                            action="{{ route('teacher.results.disqualify', $result->id) }}"
                                            onsubmit="return confirm('Disqualify this result? This will remove history, badges, and certificate eligibility.')">

                                            @csrf
                                            @method('DELETE')

                                            <button type="submit" class="btn btn-sm btn-outline-danger">
                                                Disqualify
                                            </button>
                                        </form>
                                    </td>

                                </tr>
                                @endforeach

                            @empty

                                <tr>

                                    <td colspan="10"
                                        class="text-center text-muted">

                                        No results found.

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

