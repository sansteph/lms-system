<!DOCTYPE html>
<html>
<head>
<meta charset="utf-8">
<style>
@page {
    margin: 0;
    size: 297mm 210mm;
}

html,
body {
    margin: 0;
    padding: 0;
    width: 297mm;
    height: 210mm;
    overflow: hidden;
    background: #070719;
    font-family: DejaVu Sans, sans-serif;
}

* {
    box-sizing: border-box;
}

.page {
    position: relative;
    width: 297mm;
    height: 210mm;
    overflow: hidden;
    background: linear-gradient(135deg, #3a0b88 0%, #081329 38%, #021020 58%, #078477 100%);
    color: #f8fbff;
}

.certificate {
    position: absolute;
    top: 10mm;
    left: 11mm;
    width: 275mm;
    height: 188mm;
    overflow: hidden;
    border: 0.45mm solid #20e7f2;
    background: linear-gradient(135deg, #2a064f 0%, #070719 44%, #051331 68%, #028276 100%);
    box-shadow: 0 0 14mm rgba(26, 233, 247, 0.32);
}

.outer-frame {
    position: absolute;
    top: 5mm;
    left: 5mm;
    right: 5mm;
    bottom: 5mm;
    border: 0.55mm solid rgba(34, 237, 248, 0.92);
    box-shadow: inset 0 0 8mm rgba(179, 61, 255, 0.26);
}

.inner-frame {
    position: absolute;
    top: 10mm;
    left: 10mm;
    right: 10mm;
    bottom: 10mm;
    border: 0.25mm solid rgba(255, 255, 255, 0.22);
    background: rgba(255, 255, 255, 0.03);
}

.top-rule {
    position: absolute;
    top: 37mm;
    left: 83mm;
    width: 109mm;
    border-top: 0.35mm solid #22f3ff;
    box-shadow: 0 0 4mm rgba(34, 243, 255, 0.7);
}

.footer-rule {
    position: absolute;
    top: 143mm;
    left: 48mm;
    width: 179mm;
    border-top: 0.22mm solid rgba(255, 255, 255, 0.28);
}

.corner {
    position: absolute;
    width: 25mm;
    height: 18mm;
}

.corner-tl {
    top: 10mm;
    left: 10mm;
    border-top: 0.55mm solid #bd35ff;
    border-left: 0.55mm solid #22f3ff;
}

.corner-tr {
    top: 10mm;
    right: 10mm;
    border-top: 0.55mm solid #22f3ff;
    border-right: 0.55mm solid #bd35ff;
}

.corner-bl {
    bottom: 10mm;
    left: 10mm;
    border-bottom: 0.55mm solid #22f3ff;
    border-left: 0.55mm solid #bd35ff;
}

.corner-br {
    bottom: 10mm;
    right: 10mm;
    border-bottom: 0.55mm solid #bd35ff;
    border-right: 0.55mm solid #22f3ff;
}

.brand {
    position: absolute;
    top: 20mm;
    left: 45mm;
    width: 185mm;
    text-align: center;
    font-size: 27px;
    line-height: 1;
    font-weight: bold;
    color: #ffffff;
}

.brand span {
    color: #22f3ff;
}

.brand img {
    background: rgba(255, 255, 255, 0.92);
    border-radius: 2mm;
    padding: 2mm;
}

.brand-subtitle {
    position: absolute;
    top: 30mm;
    left: 45mm;
    width: 185mm;
    text-align: center;
    font-size: 9px;
    letter-spacing: 1.8px;
    color: #bfefff;
}

.title {
    position: absolute;
    top: 44mm;
    left: 36mm;
    width: 203mm;
    text-align: center;
    font-family: DejaVu Sans, sans-serif;
    font-size: 38px;
    line-height: 1;
    letter-spacing: 4px;
    font-weight: bold;
    color: #ffffff;
    text-shadow: 0 0 3mm rgba(34, 243, 255, 0.55);
}

.subtitle {
    position: absolute;
    top: 61mm;
    left: 76mm;
    width: 123mm;
    text-align: center;
    font-family: DejaVu Sans, sans-serif;
    font-size: 18px;
    letter-spacing: 2.5px;
    font-weight: bold;
    color: #22f3ff;
}

.subtitle-left,
.subtitle-right {
    position: absolute;
    top: 63mm;
    width: 35mm;
    border-top: 0.32mm solid #22f3ff;
}

.subtitle-left {
    left: 70mm;
}

.subtitle-right {
    right: 70mm;
}

.presented {
    position: absolute;
    top: 72mm;
    left: 50mm;
    width: 175mm;
    text-align: center;
    font-size: 13px;
    letter-spacing: 1px;
    color: #dce9ff;
}

.student-name {
    position: absolute;
    top: 82mm;
    left: 48mm;
    width: 179mm;
    min-height: 16mm;
    text-align: center;
    font-family: DejaVu Sans, sans-serif;
    font-style: normal;
    font-size: 35px;
    line-height: 1.12;
    color: #22f3ff;
    background: rgba(255, 255, 255, 0.12);
    border: 0.25mm solid rgba(255, 255, 255, 0.35);
    border-radius: 3mm;
    padding: 3mm 5mm;
    box-shadow: 0 0 8mm rgba(34, 243, 255, 0.24);
    word-wrap: break-word;
}

.name-line {
    position: absolute;
    top: 98mm;
    left: 63mm;
    width: 149mm;
    border-top: 0;
}

.description {
    position: absolute;
    top: 102mm;
    left: 57mm;
    width: 161mm;
    text-align: center;
    font-size: 14px;
    line-height: 1.55;
    color: #edf6ff;
}

.metrics {
    position: absolute;
    top: 124mm;
    left: 45mm;
    width: 185mm;
    border-collapse: collapse;
}

.metrics td {
    width: 33.33%;
    text-align: center;
    color: #061123;
    vertical-align: top;
}

.metrics .middle {
    border-left: 4mm solid transparent;
    border-right: 4mm solid transparent;
}

.metric-label {
    display: inline-block;
    min-width: 40mm;
    padding: 3mm 4mm 1mm;
    border-radius: 4mm 4mm 0 0;
    background: linear-gradient(135deg, #1cf4ff 0%, #14bfdc 100%);
    font-size: 11px;
    line-height: 1;
    font-weight: bold;
    color: #061123;
}

.metric-value {
    display: inline-block;
    min-width: 40mm;
    padding: 1mm 4mm 3mm;
    border-radius: 0 0 4mm 4mm;
    background: linear-gradient(135deg, #1cf4ff 0%, #14bfdc 100%);
    font-size: 17px;
    line-height: 1;
    font-weight: bold;
    color: #061123;
    box-shadow: 0 0 6mm rgba(34, 243, 255, 0.25);
}

.metrics td.middle .metric-label,
.metrics td.middle .metric-value {
    background: linear-gradient(135deg, #ffe06b 0%, #e0ae22 100%);
}

.metrics td:last-child .metric-label,
.metrics td:last-child .metric-value {
    background: linear-gradient(135deg, #c751ff 0%, #8135ff 100%);
    color: #ffffff;
}

.footer {
    position: absolute;
    top: 150mm;
    left: 42mm;
    width: 191mm;
    height: 27mm;
}

.footer-table {
    width: 191mm;
    height: 27mm;
    border-collapse: collapse;
    table-layout: fixed;
}

.footer-table td {
    width: 33.33%;
    vertical-align: bottom;
    color: #f8fbff;
}

.signature-cell {
    text-align: left;
}

.signature-box {
    width: 50mm;
    height: 12mm;
    border: 0.22mm solid rgba(255, 255, 255, 0.35);
    border-radius: 3mm;
    background: rgba(255, 255, 255, 0.11);
    text-align: center;
    font-size: 7px;
    line-height: 12mm;
    color: #dce9ff;
    box-shadow: 0 0 5mm rgba(34, 243, 255, 0.2);
}

.signature-line {
    width: 50mm;
    margin-top: 1.6mm;
    border-top: 0.32mm solid #ffffff;
}

.director {
    padding-top: 1.4mm;
    font-size: 15px;
    line-height: 1.2;
    font-weight: bold;
    color: #ffffff;
}

.company {
    padding-top: 0.6mm;
    font-size: 12px;
    line-height: 1.2;
    color: #22f3ff;
}

.stamp-cell {
    text-align: center;
}

.stamp {
    width: 25mm;
    height: 25mm;
    margin: 0 auto;
    border: 0.28mm solid #d7a51e;
    border-radius: 11mm;
    text-align: center;
    line-height: 22mm;
    color: #ffffff;
    background: rgba(255, 218, 72, 0.12);
    box-shadow: 0 0 6mm rgba(255, 218, 72, 0.28);
}

.date-cell {
    text-align: right;
}

.date-title {
    font-size: 15px;
    line-height: 1.4;
    font-weight: bold;
    color: #ffffff;
}

.date-value,
.certificate-code {
    font-size: 12px;
    line-height: 1.55;
    color: #dce9ff;
}
</style>
</head>
<body>
@php
    $studentName = $student->name ?? 'Student Name';
    $programName = $certificate->certificate_type === 'Component Mastery'
        ? $certificate->final_classification
        : ($certificate->course->course_title ?? 'STEM Robotics & AI Program');
    $percentage = round(
        $certificate->final_score
        ?? $certificate->final_percentage
        ?? $certificate->badge_count
        ?? 0,
        2
    );

    if ($percentage >= 90) {
        $grade = 'A+';
        $classification = 'Outstanding';
    } elseif ($percentage >= 80) {
        $grade = 'A';
        $classification = 'Distinction';
    } elseif ($percentage >= 70) {
        $grade = 'B+';
        $classification = 'First Class';
    } elseif ($percentage >= 60) {
        $grade = 'B';
        $classification = 'Second Class';
    } elseif ($percentage >= 50) {
        $grade = 'C+';
        $classification = 'Pass';
    } elseif ($percentage >= 40) {
        $grade = 'C';
        $classification = 'Satisfactory';
    } else {
        $grade = 'F';
        $classification = 'Fail';
    }

    $issueDate = $certificate->issued_date
        ? \Carbon\Carbon::parse($certificate->issued_date)->format('d F Y')
        : now()->format('d F Y');

    $certificateCode = $certificate->certificate_code ?? 'TE-CERTIFICATE';
@endphp

<div class="page">
    <div class="certificate">
        <div class="outer-frame"></div>
        <div class="inner-frame"></div>

        <div class="corner corner-tl"></div>
        <div class="corner corner-tr"></div>
        <div class="corner corner-bl"></div>
        <div class="corner corner-br"></div>

        <div class="top-rule"></div>
        <div class="footer-rule"></div>

        <div class="brand">
            <img src="{{ public_path('images/InnovatEdgeLogo.png') }}" alt="InnovatEdge Logo" style="top: 20mm; left: 20mm; width: 50mm">
        </div>

        <div class="title">CERTIFICATE</div>
        <div class="subtitle-left"></div>
        <div class="subtitle">OF ACHIEVEMENT</div>
        <div class="subtitle-right"></div>

        <div class="presented">This certificate is proudly presented to</div>
        <div class="student-name">{{ $studentName }}</div>
        <div class="name-line"></div>

        <div class="description">
            for successfully completing the {{ $programName }} and demonstrating consistent performance,
            dedication, and applied STEM learning.
        </div>

        <table class="metrics">
            <tr>
                <td>
                    <div class="metric-label">Final Percentage</div>
                    <div class="metric-value">{{ number_format($percentage, 2) }}%</div>
                </td>
                <td class="middle">
                    <div class="metric-label">Grade</div>
                    <div class="metric-value">{{ $grade }}</div>
                </td>
                <td>
                    <div class="metric-label">Classification</div>
                    <div class="metric-value">{{ strtoupper($classification) }}</div>
                </td>
            </tr>
        </table>

        <div class="footer">
            <table class="footer-table">
                <tr>
                    <td class="signature-cell">
                        <img src="{{ public_path('images/signature.png') }}" alt="Director's Signature" class="signature-box">
                        <div class="signature-line"></div>
                        <div class="director">Director</div>
                        <div class="company">TinkEdge</div>
                    </td>
                    <td class="stamp-cell">
                        <img src="{{ public_path('images/company-stamp.jpeg') }}" alt="Stamp" class="stamp">
                    </td>
                    <td class="date-cell">
                        <div class="date-title">Date of Issue</div>
                        <div class="date-value">{{ $issueDate }}</div>
                        <div class="certificate-code">Certificate ID: {{ $certificateCode }}</div>
                    </td>
                </tr>
            </table>
        </div>
    </div>
</div>
</body>
</html>
