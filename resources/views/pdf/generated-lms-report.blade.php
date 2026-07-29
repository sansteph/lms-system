<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>{{ $title }}</title>
    <style>
        @page { margin: 24px; }
        body {
            font-family: DejaVu Sans, Arial, sans-serif;
            color: #0f172a;
            font-size: 11px;
            line-height: 1.45;
        }
        .header {
            border-bottom: 3px solid #0f3b7a;
            padding-bottom: 12px;
            margin-bottom: 16px;
        }
        .brand {
            color: #0f3b7a;
            font-size: 22px;
            font-weight: bold;
        }
        .brand span { color: #0ea5e9; }
        .title {
            margin-top: 6px;
            color: #0f172a;
            font-size: 17px;
            font-weight: bold;
            text-transform: uppercase;
            letter-spacing: 1px;
        }
        .meta { color: #64748b; margin-top: 3px; }
        .section { margin-top: 14px; page-break-inside: avoid; }
        .section-title {
            background: #0f3b7a;
            color: #ffffff;
            font-weight: bold;
            padding: 7px 9px;
            text-transform: uppercase;
        }
        .section-body {
            border: 1px solid #dbe3ef;
            border-top: 0;
            padding: 10px;
        }
        table { width: 100%; border-collapse: collapse; }
        th, td {
            border: 1px solid #dbe3ef;
            padding: 6px;
            vertical-align: top;
        }
        th {
            background: #f1f5f9;
            color: #0f3b7a;
            text-align: left;
        }
        .metric-table td:first-child { width: 42%; color: #475569; }
        .score-grid td { border: 0; width: 25%; }
        .score-card {
            border: 1px solid #dbe3ef;
            background: #f8fbff;
            padding: 9px;
            min-height: 58px;
        }
        .score-label {
            color: #64748b;
            font-size: 9px;
            text-transform: uppercase;
            font-weight: bold;
        }
        .score-value {
            color: #0f3b7a;
            font-size: 16px;
            font-weight: bold;
            margin-top: 4px;
        }
        .bar-row { margin-bottom: 8px; }
        .bar-label { margin-bottom: 3px; }
        .bar-track {
            height: 9px;
            background: #e2e8f0;
            border-radius: 20px;
        }
        .bar-fill {
            height: 9px;
            background: #0f3b7a;
            border-radius: 20px;
        }
        .gold-fill { background: #d99b21; }
        .muted { color: #64748b; }
        ul { margin: 0; padding-left: 18px; }
        li { margin-bottom: 4px; }
        .footer {
            margin-top: 18px;
            color: #64748b;
            font-size: 9px;
            border-top: 1px solid #dbe3ef;
            padding-top: 8px;
        }
    </style>
</head>
<body>
    @php
        $scorecards = collect($insights['scorecards'] ?? []);
        $findings = collect($insights['metric_findings'] ?? []);
        $actions = collect($insights['priority_actions'] ?? []);
        $sections = collect($insights['narrative_sections'] ?? []);
    @endphp

    <div class="header">
        <div class="brand">Innovat<span>Edge</span></div>
        <div class="title">{{ $title }}</div>
        <div class="meta">Scope: {{ $scope }} | Period: {{ $periodLabel }} | Generated: {{ $insights['generated_at'] ?? now()->format('d M Y h:i A') }}</div>
        @if(!empty($insights['model']))
            <div class="meta">AI Model: {{ $insights['model'] }}</div>
        @endif
    </div>

    <div class="section">
        <div class="section-title">Key Metrics</div>
        <div class="section-body">
            <table class="score-grid">
                @foreach(collect($metrics)->except(['scope', 'period'])->chunk(4) as $row)
                    <tr>
                        @foreach($row as $key => $value)
                            <td>
                                <div class="score-card">
                                    <div class="score-label">{{ ucwords(str_replace('_', ' ', $key)) }}</div>
                                    <div class="score-value">{{ is_numeric($value) ? rtrim(rtrim(number_format((float) $value, 2), '0'), '.') : $value }}</div>
                                </div>
                            </td>
                        @endforeach
                    </tr>
                @endforeach
            </table>
        </div>
    </div>

    <div class="section">
        <div class="section-title">Visual Summary</div>
        <div class="section-body">
            @forelse($visuals as $visual)
                <div style="margin-bottom: 11px;">
                    <strong>{{ $visual['title'] }}</strong>
                    @php
                        $labels = collect($visual['labels'] ?? []);
                        $values = collect($visual['values'] ?? [])->map(fn ($value) => max(0, (float) $value));
                        $maxValue = max($values->max() ?: 1, 1);
                    @endphp
                    @foreach($labels as $index => $label)
                        @php
                            $value = (float) ($values[$index] ?? 0);
                            $width = min(100, round(($value / $maxValue) * 100, 1));
                        @endphp
                        <div class="bar-row">
                            <div class="bar-label">{{ $label }}: <strong>{{ rtrim(rtrim(number_format($value, 2), '0'), '.') }}</strong></div>
                            <div class="bar-track">
                                <div class="bar-fill {{ $index % 2 ? 'gold-fill' : '' }}" style="width: {{ $width }}%;"></div>
                            </div>
                        </div>
                    @endforeach
                </div>
            @empty
                <div class="muted">No visual metrics available for the selected filters.</div>
            @endforelse
        </div>
    </div>

    <div class="section">
        <div class="section-title">{{ $tableTitle }}</div>
        <div class="section-body">
            <table>
                <thead>
                    <tr>
                        @foreach($tableHeaders as $header)
                            <th>{{ $header }}</th>
                        @endforeach
                    </tr>
                </thead>
                <tbody>
                    @forelse($tableRows as $row)
                        <tr>
                            @foreach($row as $cell)
                                <td>{{ $cell }}</td>
                            @endforeach
                        </tr>
                    @empty
                        <tr>
                            <td colspan="{{ count($tableHeaders) }}" class="muted">No data available for the selected filters.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="section">
        <div class="section-title">AI Insights</div>
        <div class="section-body">
            <strong>Executive Summary</strong>
            <p>{{ $insights['executive_summary'] ?? $insights['summary'] ?? 'No summary returned.' }}</p>

            @if($findings->isNotEmpty())
                <table>
                    <thead>
                        <tr>
                            <th>Area</th>
                            <th>Metric</th>
                            <th>Value</th>
                            <th>Interpretation</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($findings->take(8) as $finding)
                            <tr>
                                <td>{{ $finding['area'] ?? 'General' }}</td>
                                <td>{{ $finding['metric'] ?? '-' }}</td>
                                <td>{{ $finding['value'] ?? '-' }}</td>
                                <td>{{ $finding['interpretation'] ?? '-' }}</td>
                                <td>{{ $finding['recommended_action'] ?? '-' }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            @endif

            @if($sections->isNotEmpty())
                @foreach($sections->take(4) as $section)
                    <p><strong>{{ $section['heading'] ?? 'Analysis' }}:</strong> {{ $section['body'] ?? '' }}</p>
                @endforeach
            @endif

            <strong>Priority Actions</strong>
            <ul>
                @forelse($actions->take(6) as $action)
                    <li>{{ $action['action'] ?? 'Action required' }} @if(!empty($action['reason'])) - {{ $action['reason'] }} @endif</li>
                @empty
                    @forelse($insights['recommendations'] ?? [] as $item)
                        <li>{{ $item }}</li>
                    @empty
                        <li>No priority actions returned.</li>
                    @endforelse
                @endforelse
            </ul>
        </div>
    </div>

    <div class="footer">
        Generated from live InnovatEdge LMS data. AI insights are advisory and do not modify LMS records.
    </div>
</body>
</html>
