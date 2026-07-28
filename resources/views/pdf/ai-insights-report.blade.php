<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>{{ $title }}</title>
    <style>
        @page {
            margin: 24px;
        }

        body {
            font-family: DejaVu Sans, Arial, sans-serif;
            color: #0f172a;
            font-size: 12px;
            line-height: 1.5;
        }

        .header {
            border-bottom: 3px solid #0b2f6b;
            padding-bottom: 12px;
            margin-bottom: 18px;
        }

        .brand {
            font-size: 22px;
            font-weight: bold;
            color: #0b2f6b;
        }

        .brand span {
            color: #0ea5e9;
        }

        .title {
            font-size: 18px;
            font-weight: bold;
            margin-top: 8px;
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        .meta {
            color: #475569;
            margin-top: 4px;
        }

        .section {
            margin-top: 16px;
        }

        .section-title {
            background: #0b2f6b;
            color: #ffffff;
            font-weight: bold;
            padding: 7px 9px;
            font-size: 12px;
            text-transform: uppercase;
        }

        .section-body {
            border: 1px solid #dbe3ef;
            border-top: 0;
            padding: 10px;
        }

        ul {
            margin: 0;
            padding-left: 18px;
        }

        li {
            margin-bottom: 5px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        th,
        td {
            border: 1px solid #dbe3ef;
            padding: 7px;
            vertical-align: top;
        }

        th {
            background: #f1f5f9;
            color: #0b2f6b;
            text-align: left;
        }

        .footer {
            margin-top: 18px;
            color: #64748b;
            font-size: 10px;
            border-top: 1px solid #dbe3ef;
            padding-top: 8px;
        }

        .scorecards {
            width: 100%;
        }

        .scorecard {
            border: 1px solid #dbe3ef;
            background: #f8fbff;
            padding: 9px;
            margin-bottom: 8px;
        }

        .scorecard-label {
            color: #475569;
            font-size: 10px;
            text-transform: uppercase;
        }

        .scorecard-value {
            color: #0b2f6b;
            font-size: 18px;
            font-weight: bold;
        }

        .status {
            display: inline-block;
            padding: 2px 5px;
            border-radius: 4px;
            color: #ffffff;
            background: #64748b;
            font-size: 9px;
        }

        .status.success { background: #15803d; }
        .status.warning { background: #b45309; }
        .status.danger { background: #b91c1c; }
        .status.info { background: #0369a1; }

        .bar-track {
            height: 8px;
            background: #e2e8f0;
            border-radius: 10px;
            overflow: hidden;
        }

        .bar-fill {
            height: 8px;
            background: #0b2f6b;
        }

        .action {
            border-bottom: 1px solid #e2e8f0;
            padding: 7px 0;
        }
    </style>
</head>
<body>
    @php
        $scorecards = collect($insights['scorecards'] ?? []);
        $findings = collect($insights['metric_findings'] ?? []);
        $charts = collect($insights['chart_suggestions'] ?? []);
        $actions = collect($insights['priority_actions'] ?? []);
        $sections = collect($insights['narrative_sections'] ?? []);
    @endphp

    <div class="header">
        <div class="brand">Innovat<span>Edge</span></div>
        <div class="title">{{ $title }}</div>
        <div class="meta">
            Scope: {{ $scope }} | Generated: {{ $insights['generated_at'] ?? now()->format('d M Y h:i A') }}
        </div>
        @if(!empty($insights['model']))
            <div class="meta">AI Model: {{ $insights['model'] }}</div>
        @endif
    </div>

    <div class="section">
        <div class="section-title">Executive Summary</div>
        <div class="section-body">
            {{ $insights['executive_summary'] ?? $insights['summary'] ?? 'No summary returned.' }}
        </div>
    </div>

    @if($scorecards->isNotEmpty())
        <div class="section">
            <div class="section-title">Scorecards</div>
            <div class="section-body">
                <table class="scorecards">
                    <tbody>
                        @foreach($scorecards->chunk(2) as $row)
                            <tr>
                                @foreach($row as $card)
                                    <td style="width:50%; border:0;">
                                        <div class="scorecard">
                                            <div class="scorecard-label">{{ $card['label'] ?? 'Metric' }}</div>
                                            <div class="scorecard-value">{{ $card['value'] ?? '-' }}</div>
                                            <div>
                                                <span class="status {{ $card['status_color'] ?? '' }}">{{ $card['status'] ?? 'Tracked' }}</span>
                                            </div>
                                            <div style="margin-top:5px;">{{ $card['interpretation'] ?? '' }}</div>
                                        </div>
                                    </td>
                                @endforeach
                                @if($row->count() == 1)
                                    <td style="width:50%; border:0;"></td>
                                @endif
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    @endif

    @if($charts->isNotEmpty())
        <div class="section">
            <div class="section-title">Visual Data Summary</div>
            <div class="section-body">
                @foreach($charts as $chart)
                    <div style="margin-bottom:12px;">
                        <strong>{{ $chart['title'] ?? 'Data View' }}</strong>
                        <div style="color:#475569; margin-bottom:5px;">{{ $chart['insight'] ?? '' }}</div>
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
                            <div style="margin-bottom:5px;">
                                <div>{{ $label }}: <strong>{{ rtrim(rtrim(number_format($value, 2), '0'), '.') }}</strong></div>
                                <div class="bar-track"><div class="bar-fill" style="width: {{ $width }}%;"></div></div>
                            </div>
                        @endforeach
                    </div>
                @endforeach
            </div>
        </div>
    @endif

    @if($findings->isNotEmpty())
        <div class="section">
            <div class="section-title">Metric Findings</div>
            <div class="section-body">
                <table>
                    <thead>
                        <tr>
                            <th>Area</th>
                            <th>Metric</th>
                            <th>Value</th>
                            <th>Status</th>
                            <th>Interpretation</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($findings as $finding)
                            <tr>
                                <td>{{ $finding['area'] ?? 'General' }}</td>
                                <td>{{ $finding['metric'] ?? '-' }}</td>
                                <td>{{ $finding['value'] ?? '-' }}</td>
                                <td>{{ $finding['status'] ?? 'Tracked' }}</td>
                                <td>{{ $finding['interpretation'] ?? '-' }}</td>
                                <td>{{ $finding['recommended_action'] ?? '-' }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    @endif

    <div class="section">
        <div class="section-title">Detailed Analysis</div>
        <div class="section-body">
            @forelse($sections as $section)
                <div style="margin-bottom:9px;">
                    <strong>{{ $section['heading'] ?? 'Analysis' }}</strong>
                    <div>{{ $section['body'] ?? '' }}</div>
                </div>
            @empty
                {{ $insights['summary'] ?? 'No detailed analysis returned.' }}
            @endforelse
        </div>
    </div>

    <div class="section">
        <div class="section-title">Priority Actions</div>
        <div class="section-body">
            @forelse($actions as $action)
                <div class="action">
                    <strong>{{ $action['priority'] ?? 'Priority' }}:</strong>
                    {{ $action['action'] ?? 'Action required' }}
                    <br>
                    Owner: {{ $action['owner'] ?? 'Admin' }}
                    <br>
                    Reason: {{ $action['reason'] ?? '' }}
                    @if(!empty($action['metric_reference']))
                        <br>Metric: {{ $action['metric_reference'] }}
                    @endif
                </div>
            @empty
                <ul>
                    @forelse($insights['recommendations'] ?? [] as $item)
                        <li>{{ $item }}</li>
                    @empty
                        <li>No priority actions returned.</li>
                    @endforelse
                </ul>
            @endforelse
        </div>
    </div>

    <div class="section">
        <div class="section-title">Live Metrics Used</div>
        <div class="section-body">
            <table>
                <thead>
                    <tr>
                        <th>Metric</th>
                        <th>Value</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($metrics as $key => $value)
                        <tr>
                            <td>{{ ucwords(str_replace('_', ' ', $key)) }}</td>
                            <td>
                                @if(is_array($value))
                                    {{ json_encode($value, JSON_UNESCAPED_SLASHES) }}
                                @else
                                    {{ $value }}
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>

    <div class="footer">
        This report is generated from live InnovatEdge LMS data. AI insights are advisory and do not modify scores, badges, certificates, or records.
    </div>
</body>
</html>
