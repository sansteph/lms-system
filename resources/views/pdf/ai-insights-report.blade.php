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
    </style>
</head>
<body>
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
            {{ $insights['summary'] ?? 'No summary returned.' }}
        </div>
    </div>

    <div class="section">
        <div class="section-title">Highlights</div>
        <div class="section-body">
            <ul>
                @forelse($insights['highlights'] ?? [] as $item)
                    <li>{{ $item }}</li>
                @empty
                    <li>No highlights returned.</li>
                @endforelse
            </ul>
        </div>
    </div>

    <div class="section">
        <div class="section-title">Risks</div>
        <div class="section-body">
            <ul>
                @forelse($insights['risks'] ?? [] as $item)
                    <li>{{ $item }}</li>
                @empty
                    <li>No risks returned.</li>
                @endforelse
            </ul>
        </div>
    </div>

    <div class="section">
        <div class="section-title">Recommendations</div>
        <div class="section-body">
            <ul>
                @forelse($insights['recommendations'] ?? [] as $item)
                    <li>{{ $item }}</li>
                @empty
                    <li>No recommendations returned.</li>
                @endforelse
            </ul>
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
