<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">

    <style> 

        body {
            font-family: DejaVu Sans, sans-serif;
            margin: 0;
            padding: 0;
            background: #ffffff;
        }

        .certificate {
            position: relative;      
            height:520px;
            box-sizing:border-box;
            padding: 28px 54px;
            border: 8px solid #0b2f78;
            text-align: center;
        }

        .corner {
            position: absolute;
            width: 46px;
            height: 46px;
            border-color: #d4af37;
        }

        .corner-tl { top: 28px; left: 28px; border-top: 3px solid; border-left: 3px solid; }
        .corner-tr { top: 28px; right: 28px; border-top: 3px solid; border-right: 3px solid; }
        .corner-bl { bottom: 28px; left: 28px; border-bottom: 3px solid; border-left: 3px solid; }
        .corner-br { bottom: 28px; right: 28px; border-bottom: 3px solid; border-right: 3px solid; }

        .brand {
            font-size: 12px;
            letter-spacing: 6px;
            color: #0b2f78;
            font-weight: bold;
            margin-bottom: 12px;
        }

        .title {
            font-size: 35px;
            color: #111827;
            margin: 0 0 8px;
            font-weight: bold;
        }

        .subtitle {
            font-size: 14px;
            color: #374151;
            margin-bottom: 10px;
        }

        .student-name {
            font-size: 31px;
            color: #0b2f78;
            font-weight: bold;
            margin: 0 0 7px;
        }

        .gold-line {
            width: 190px;
            height: 3px;
            background: #d4af37;
            margin: 0 auto 17px;
        }

        .description {
            width: 76%;
            margin: 0 auto 15px;
            font-size: 12.5px;
            line-height: 1.55;
            color: #1f2937;
        }

        .details {
            width: 74%;
            margin: 0 auto 18px;
            border-collapse: collapse;
        }

        .details td {
            width: 33.33%;
            text-align: center;
            vertical-align: top;
            border-right: 1px solid #d1d5db;
            padding: 0 18px;
        }

        .details td:last-child {
            border-right: none;
        }

        .detail-icon {
            font-size: 18px;
            color: #d4af37;
            margin-bottom: 6px;
        }

        .label {
            font-size: 9.5px;
            color: #6b7280;
            text-transform: uppercase;
            letter-spacing: 1.2px;
            margin-bottom: 5px;
        }

        .value {
            font-size: 11.5px;
            font-weight: bold;
            color: #111827;
        }

        .bottom-table {
            width: 82%;
            margin: 24px auto 0;
            border-collapse: collapse;
        }

        .bottom-table td {
            width: 50%;
            vertical-align: bottom;
        }

        .issued-cell {
            text-align: left;
        }

        .signature-cell {
            text-align: right;
        }

        .issued-table {
            border-collapse: collapse;
        }

        .issued-table td {
            vertical-align: middle;
        }

        .logo-cell {
            width: 68px;
            text-align: center;
            padding-right: 16px;
        }

        .logo-cell img {
            max-width: 54px;
            max-height: 54px;
        }

        .issued-content {
            border-left: 1px solid #d1d5db;
            padding-left: 16px;
        }

        .issued-small {
            font-size: 12px;
            color: #6b7280;
            margin-bottom: 3px;
        }

        .issued-name {
            font-size: 18px;
            font-weight: bold;
            color: #0b2f78;
            margin-bottom: 3px;
        }

        .issued-tagline {
            font-size: 10px;
            color: #6b7280;
        }

        .signature-box {
            display: inline-block;
            text-align: center;
            width: 280px;
        }

        .signature-line {
            width: 250px;
            border-top: 2px solid #d4af37;
            margin: 0 auto 8px;
            margin-top: 50px;
        }

        .signature-title {
            font-size: 14px;
            font-weight: bold;
            color: #0b2f78;
        }

        .signature-sub {
            font-size: 10.5px;
            color: #6b7280;
            margin-top: 4px;
        }
    </style>
</head>

<body>

<div class="certificate">

    <div class="corner corner-tl"></div>
    <div class="corner corner-tr"></div>
    <div class="corner corner-bl"></div>
    <div class="corner corner-br"></div>

    <div class="brand">TINKEDGE LEARNING COURSE CERTIFICATE</div>

    <h1 class="title">
        Certificate of {{ $certificate->certificate_type ?? 'Completion' }}
    </h1>

    <div class="subtitle">This certificate is proudly presented to</div>

    <h2 class="student-name">{{ $student->name }}</h2>

    <div class="gold-line"></div>

    <p class="description">
        This certificate is awarded for successfully completing the course
        <strong>{{ $certificate->course->course_title ?? 'Completed Course' }}</strong>
        on the TinkEdge LMS platform. The learner has completed all required learning
        content and course-linked assessments, achieving an average assessment score of
        <strong>{{ $certificate->badge_count ?? 0 }}%</strong>. This certificate recognizes
        the learner’s course completion, assessment performance, and demonstrated learning progress.
    </p>

    <table class="details">
        <tr>
            <td>
                <div class="detail-icon">▣</div>
                <div class="label">Issued Date</div>
                <div class="value">
                    {{ \Carbon\Carbon::parse($certificate->issued_date)->format('d M Y') }}
                </div>
            </td>

            <td>
                <div class="detail-icon">✦</div>
                <div class="label">Certificate Code</div>
                <div class="value">{{ $certificate->certificate_code }}</div>
            </td>

            <td>
                <div class="detail-icon">◉</div>
                <div class="label">Certificate Type</div>
                <div class="value">{{ $certificate->certificate_type ?? 'Completion' }}</div>
            </td>
        </tr>
    </table>

    <table class="bottom-table">
        <tr>
            <td class="issued-cell">
                <table class="issued-table">
                    <tr>
                        <td class="logo-cell">
                            <img src="{{ public_path('images/TinkEdgeLogo.png') }}">
                        </td>

                        <td class="issued-content">
                            <div class="issued-small">Issued by</div>
                            <div class="issued-name">TinkEdge LMS</div>
                            <div class="issued-tagline">
                                Empowering Learners. Building Futures.
                            </div>
                        </td>
                    </tr>
                </table>
            </td>

            <td class="signature-cell">
                <div class="signature-box">
                    <div class="signature-line"></div>
                    <div class="signature-title">Director's Signature</div>
                    <div class="signature-sub">TinkEdge Learning</div>
                </div>
            </td>
        </tr>
    </table>

</div>

</body>
</html>