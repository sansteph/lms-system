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
    background: #ffffff;
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
    background: #ffffff;
    color: #08295f;
}

.certificate {
    position: absolute;
    top: 10mm;
    left: 11mm;
    width: 275mm;
    height: 188mm;
    overflow: hidden;
    border: 0.45mm solid #c99532;
    background: #ffffff;
}

.outer-frame {
    position: absolute;
    top: 5mm;
    left: 5mm;
    right: 5mm;
    bottom: 5mm;
    border: 0.9mm solid #08295f;
}

.inner-frame {
    position: absolute;
    top: 10mm;
    left: 10mm;
    right: 10mm;
    bottom: 10mm;
    border: 0.22mm solid #d8b35f;
}

.top-rule {
    position: absolute;
    top: 37mm;
    left: 83mm;
    width: 109mm;
    border-top: 0.18mm solid #d8b35f;
}

.footer-rule {
    position: absolute;
    top: 143mm;
    left: 48mm;
    width: 179mm;
    border-top: 0.22mm solid #d8b35f;
}

.corner {
    position: absolute;
    width: 25mm;
    height: 18mm;
}

.corner-tl {
    top: 10mm;
    left: 10mm;
    border-top: 0.35mm solid #d8b35f;
    border-left: 0.35mm solid #d8b35f;
}

.corner-tr {
    top: 10mm;
    right: 10mm;
    border-top: 0.35mm solid #d8b35f;
    border-right: 0.35mm solid #d8b35f;
}

.corner-bl {
    bottom: 10mm;
    left: 10mm;
    border-bottom: 0.35mm solid #d8b35f;
    border-left: 0.35mm solid #d8b35f;
}

.corner-br {
    bottom: 10mm;
    right: 10mm;
    border-bottom: 0.35mm solid #d8b35f;
    border-right: 0.35mm solid #d8b35f;
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
    color: #08295f;
}

.brand span {
    color: #0096d6;
}

.brand-subtitle {
    position: absolute;
    top: 30mm;
    left: 45mm;
    width: 185mm;
    text-align: center;
    font-size: 9px;
    letter-spacing: 1.8px;
    color: #08295f;
}

.title {
    position: absolute;
    top: 44mm;
    left: 36mm;
    width: 203mm;
    text-align: center;
    font-family: DejaVu Serif, serif;
    font-size: 34px;
    line-height: 1;
    letter-spacing: 7px;
    font-weight: bold;
    color: #08295f;
}

.subtitle {
    position: absolute;
    top: 61mm;
    left: 76mm;
    width: 123mm;
    text-align: center;
    font-family: DejaVu Serif, serif;
    font-size: 13px;
    letter-spacing: 4px;
    color: #b77b1f;
}

.subtitle-left,
.subtitle-right {
    position: absolute;
    top: 63mm;
    width: 35mm;
    border-top: 0.22mm solid #d8b35f;
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
    font-size: 15px;
    color: #111827;
}

.student-name {
    position: absolute;
    top: 82mm;
    left: 48mm;
    width: 179mm;
    min-height: 16mm;
    text-align: center;
    font-family: DejaVu Serif, serif;
    font-style: italic;
    font-size: 32px;
    line-height: 1.12;
    color: #08295f;
    word-wrap: break-word;
}

.name-line {
    position: absolute;
    top: 98mm;
    left: 63mm;
    width: 149mm;
    border-top: 0.35mm solid #d8b35f;
}

.description {
    position: absolute;
    top: 102mm;
    left: 57mm;
    width: 161mm;
    text-align: center;
    font-size: 15px;
    line-height: 1.55;
    color: #111827;
}

.metrics {
    position: absolute;
    top: 125mm;
    left: 58mm;
    width: 159mm;
    border-collapse: collapse;
}

.metrics td {
    width: 33.33%;
    text-align: center;
    color: #08295f;
    vertical-align: top;
}

.metrics .middle {
    border-left: 0.3mm solid #d8b35f;
    border-right: 0.3mm solid #d8b35f;
}

.metric-label {
    font-size: 12px;
    line-height: 1;
    font-weight: bold;
}

.metric-value {
    padding-top: 3mm;
    font-size: 16px;
    line-height: 1;
    font-weight: bold;
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
    color: #08295f;
}

.signature-cell {
    text-align: left;
}

.signature-box {
    width: 50mm;
    height: 12mm;
    border: 0.22mm dashed #b7c5da;
    text-align: center;
    font-size: 7px;
    line-height: 12mm;
    color: #8797af;
}

.signature-line {
    width: 50mm;
    margin-top: 1.6mm;
    border-top: 0.32mm solid #08295f;
}

.director {
    padding-top: 1.4mm;
    font-size: 15px;
    line-height: 1.2;
    font-weight: bold;
    color: #08295f;
}

.company {
    padding-top: 0.6mm;
    font-size: 12px;
    line-height: 1.2;
    color: #111827;
}

.stamp-cell {
    text-align: center;
}

.stamp {
    width: 25mm;
    height: 25mm;
    margin: 0 auto;
    border: 0.28mm dashed #b7c5da;
    border-radius: 11mm;
    text-align: center;
    line-height: 22mm;
    color: #8797af;
}

.date-cell {
    text-align: right;
}

.date-title {
    font-size: 15px;
    line-height: 1.4;
    font-weight: bold;
    color: #08295f;
}

.date-value,
.certificate-code {
    font-size: 12px;
    line-height: 1.55;
    color: #111827;
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
                        <div class="company">InnovatEdge</div>
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
