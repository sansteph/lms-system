<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <style>
        @page { margin: 22mm 18mm; }
        body {
            font-family: DejaVu Sans, sans-serif;
            color: #111827;
            font-size: 12px;
            line-height: 1.45;
        }
        .header {
            text-align: center;
            border-bottom: 2px solid #0f3b7a;
            padding-bottom: 12px;
            margin-bottom: 18px;
        }
        .brand {
            font-size: 18px;
            font-weight: bold;
            color: #0f3b7a;
        }
        .title {
            font-size: 20px;
            font-weight: bold;
            margin-top: 8px;
            text-transform: uppercase;
        }
        .meta {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 16px;
        }
        .meta td {
            border: 1px solid #d1d5db;
            padding: 7px 8px;
        }
        .label {
            font-weight: bold;
            color: #374151;
            width: 18%;
        }
        .section-title {
            margin: 18px 0 7px;
            padding: 7px 9px;
            background: #eef6ff;
            border-left: 4px solid #0f3b7a;
            font-weight: bold;
            color: #0f3b7a;
        }
        .question {
            margin: 0 0 12px;
            page-break-inside: avoid;
        }
        .question-head {
            font-weight: bold;
        }
        .marks {
            float: right;
            font-weight: bold;
        }
        ul {
            margin: 6px 0 10px 18px;
            padding: 0;
        }
        .footer-note {
            margin-top: 20px;
            color: #6b7280;
            font-size: 10px;
            text-align: center;
        }
    </style>
</head>
<body>
    <div class="header">
        <div class="brand">InnovatEdge</div>
        <div class="title">{{ $paper['title'] ?? $meta['assessment_title'] }}</div>
    </div>

    <table class="meta">
        <tr>
            <td class="label">Institute</td>
            <td>{{ $meta['institute'] }}</td>
            <td class="label">Class</td>
            <td>{{ $meta['assigned_class'] }}</td>
        </tr>
        <tr>
            <td class="label">Category</td>
            <td>{{ $meta['assessment_category'] }}</td>
            <td class="label">Date</td>
            <td>{{ $meta['assessment_date'] }}</td>
        </tr>
        <tr>
            <td class="label">Duration</td>
            <td>{{ $meta['duration'] }} minutes</td>
            <td class="label">Total Marks</td>
            <td>{{ $meta['total_marks'] }}</td>
        </tr>
    </table>

    @if(!empty($paper['instructions']))
        <div class="section-title">Instructions</div>
        <ul>
            @foreach($paper['instructions'] as $instruction)
                <li>{{ $instruction }}</li>
            @endforeach
        </ul>
    @endif

    @foreach(($paper['sections'] ?? []) as $section)
        <div class="section-title">{{ $section['heading'] ?? 'Section' }}</div>
        @if(!empty($section['description']))
            <p>{{ $section['description'] }}</p>
        @endif

        @foreach(($section['questions'] ?? []) as $question)
            <div class="question">
                <div class="question-head">
                    {{ $question['number'] ?? $loop->iteration }}.
                    {{ $question['question'] ?? '' }}
                    <span class="marks">[{{ $question['marks'] ?? 0 }} marks]</span>
                </div>

            </div>
        @endforeach
    @endforeach

    <div class="footer-note">
        AI-generated draft question paper. Admin approval is required before student access.
    </div>
</body>
</html>
