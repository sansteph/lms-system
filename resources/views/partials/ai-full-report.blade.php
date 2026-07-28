@php
    $statusClass = function ($color) {
        return match ($color) {
            'success' => 'bg-success',
            'warning' => 'bg-warning text-dark',
            'danger' => 'bg-danger',
            'info' => 'bg-info text-dark',
            default => 'bg-secondary',
        };
    };

    $scorecards = collect($aiInsights['scorecards'] ?? []);
    $findings = collect($aiInsights['metric_findings'] ?? []);
    $charts = collect($aiInsights['chart_suggestions'] ?? []);
    $actions = collect($aiInsights['priority_actions'] ?? []);
    $sections = collect($aiInsights['narrative_sections'] ?? []);
@endphp

<div class="card shadow border-0 mb-4 ai-full-report-card">
    <div class="card-body">
        <div class="d-flex justify-content-between align-items-start flex-wrap gap-2 mb-4">
            <div>
                <h5 class="mb-1">{{ $reportTitle ?? 'AI Generated Report' }}</h5>
                <p class="text-muted mb-0">
                    Generated from current live LMS data{{ isset($aiInsights['generated_at']) ? ' at ' . $aiInsights['generated_at'] : '' }}.
                </p>
            </div>
            @if(!empty($aiInsights['model']))
                <span class="badge bg-info">{{ $aiInsights['model'] }}</span>
            @endif
        </div>

        <div class="ai-report-summary mb-4">
            <div class="text-muted small fw-semibold text-uppercase mb-2">Executive Summary</div>
            <p class="mb-0">{{ $aiInsights['executive_summary'] ?? $aiInsights['summary'] ?? 'No summary returned.' }}</p>
        </div>

        @if($scorecards->isNotEmpty())
            <div class="row g-3 mb-4">
                @foreach($scorecards as $card)
                    <div class="col-md-6 col-xl-3">
                        <div class="ai-scorecard h-100">
                            <div class="d-flex justify-content-between align-items-start gap-2 mb-2">
                                <h6 class="mb-0">{{ $card['label'] ?? 'Metric' }}</h6>
                                <span class="badge {{ $statusClass($card['status_color'] ?? 'secondary') }}">
                                    {{ $card['status'] ?? 'Tracked' }}
                                </span>
                            </div>
                            <div class="ai-scorecard-value">{{ $card['value'] ?? '-' }}</div>
                            <p class="text-muted small mb-0">{{ $card['interpretation'] ?? '' }}</p>
                        </div>
                    </div>
                @endforeach
            </div>
        @endif

        @if($charts->isNotEmpty())
            <div class="row g-3 mb-4">
                @foreach($charts as $chart)
                    <div class="col-lg-6">
                        <div class="ai-chart-panel h-100">
                            <h6 class="fw-bold mb-2">{{ $chart['title'] ?? 'Data View' }}</h6>
                            <p class="text-muted small mb-3">{{ $chart['insight'] ?? '' }}</p>
                            @php
                                $labels = collect($chart['labels'] ?? []);
                                $values = collect($chart['values'] ?? [])->map(fn ($value) => max(0, (float) $value));
                                $maxValue = max($values->max() ?: 1, 1);
                            @endphp
                            @foreach($labels as $index => $label)
                                @php
                                    $value = (float) ($values[$index] ?? 0);
                                    $width = min(100, round(($value / $maxValue) * 100, 1));
                                @endphp
                                <div class="ai-bar-row">
                                    <div class="d-flex justify-content-between small mb-1">
                                        <span>{{ $label }}</span>
                                        <strong>{{ rtrim(rtrim(number_format($value, 2), '0'), '.') }}</strong>
                                    </div>
                                    <div class="ai-bar-track">
                                        <div class="ai-bar-fill" style="width: {{ $width }}%;"></div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endforeach
            </div>
        @endif

        @if($findings->isNotEmpty())
            <div class="table-responsive mb-4">
                <table class="table table-bordered table-hover align-middle">
                    <thead class="table-light">
                        <tr>
                            <th>Area</th>
                            <th>Metric</th>
                            <th>Value</th>
                            <th>Status</th>
                            <th>Interpretation</th>
                            <th>Recommended Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($findings as $finding)
                            <tr>
                                <td>{{ $finding['area'] ?? 'General' }}</td>
                                <td>{{ $finding['metric'] ?? '-' }}</td>
                                <td><strong>{{ $finding['value'] ?? '-' }}</strong></td>
                                <td>{{ $finding['status'] ?? 'Tracked' }}</td>
                                <td>{{ $finding['interpretation'] ?? '-' }}</td>
                                <td>{{ $finding['recommended_action'] ?? '-' }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif

        <div class="row g-3">
            <div class="col-lg-7">
                <div class="ai-report-section h-100">
                    <h6 class="fw-bold mb-3">Detailed Analysis</h6>
                    @forelse($sections as $section)
                        <div class="mb-3">
                            <div class="fw-semibold">{{ $section['heading'] ?? 'Analysis' }}</div>
                            <p class="text-muted mb-0">{{ $section['body'] ?? '' }}</p>
                        </div>
                    @empty
                        <p class="text-muted mb-0">{{ $aiInsights['summary'] ?? 'No detailed analysis returned.' }}</p>
                    @endforelse
                </div>
            </div>

            <div class="col-lg-5">
                <div class="ai-report-section h-100">
                    <h6 class="fw-bold mb-3">Priority Actions</h6>
                    @forelse($actions as $action)
                        <div class="ai-action-item">
                            <div class="d-flex justify-content-between gap-2 mb-1">
                                <strong>{{ $action['action'] ?? 'Action required' }}</strong>
                                <span class="badge {{ ($action['priority'] ?? '') == 'High' ? 'bg-danger' : (($action['priority'] ?? '') == 'Medium' ? 'bg-warning text-dark' : 'bg-info text-dark') }}">
                                    {{ $action['priority'] ?? 'Priority' }}
                                </span>
                            </div>
                            <div class="text-muted small">Owner: {{ $action['owner'] ?? 'Admin' }}</div>
                            <div class="small mt-1">{{ $action['reason'] ?? '' }}</div>
                            @if(!empty($action['metric_reference']))
                                <div class="text-muted small mt-1">Metric: {{ $action['metric_reference'] }}</div>
                            @endif
                        </div>
                    @empty
                        <ul class="mb-0">
                            @forelse($aiInsights['recommendations'] ?? [] as $item)
                                <li>{{ $item }}</li>
                            @empty
                                <li>No priority actions returned.</li>
                            @endforelse
                        </ul>
                    @endforelse
                </div>
            </div>
        </div>
    </div>
</div>

<style>
    .ai-report-summary,
    .ai-scorecard,
    .ai-chart-panel,
    .ai-report-section {
        border: 1px solid #e2e8f0;
        border-radius: 12px;
        background: #ffffff;
        padding: 18px;
    }

    .ai-report-summary {
        background: #f8fbff;
    }

    .ai-scorecard-value {
        color: #0f3b7a;
        font-size: 1.7rem;
        font-weight: 800;
        line-height: 1.1;
        margin-bottom: 8px;
    }

    .ai-bar-row {
        margin-bottom: 12px;
    }

    .ai-bar-track {
        width: 100%;
        height: 10px;
        border-radius: 999px;
        background: #e8eef7;
        overflow: hidden;
    }

    .ai-bar-fill {
        height: 100%;
        border-radius: 999px;
        background: linear-gradient(90deg, #0f3b7a, #0ea5e9);
    }

    .ai-action-item {
        border-bottom: 1px solid #eef2f7;
        padding-bottom: 12px;
        margin-bottom: 12px;
    }

    .ai-action-item:last-child {
        border-bottom: 0;
        padding-bottom: 0;
        margin-bottom: 0;
    }
</style>
