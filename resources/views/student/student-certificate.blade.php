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
    background: #101626;
    color: #f8fbff;
}

.certificate {
    position: absolute;
    top: 11mm;
    left: 35mm;
    width: 227mm;
    height: 178mm;
    overflow: hidden;
    border: 0;
    background: #080819;
    box-shadow: 4mm 5mm 8mm rgba(0, 0, 0, 0.42);
}

.outer-frame {
    position: absolute;
    top: 0;
    left: 0;
    width: 63mm;
    height: 178mm;
    border: 0;
    background: #172a3c;
}

.outer-frame::before {
    content: "";
    position: absolute;
    top: 0;
    left: -8mm;
    width: 8mm;
    height: 178mm;
    background: #a300ff;
}

.left-strip {
    position: absolute;
    top: 0;
    left: -8mm;
    width: 8mm;
    height: 178mm;
    background: #a300ff;
}

.inner-frame {
    position: absolute;
    top: 0;
    left: 63mm;
    right: 0;
    bottom: 0;
    border: 0;
    background: #110628;
}

.inner-frame::after {
    content: "";
    position: absolute;
    right: 0;
    bottom: 0;
    width: 80mm;
    height: 64mm;
    background: #007a6f;
    opacity: 0.7;
}

.top-rule {
    position: absolute;
    top: 0;
    right: 0;
    width: 48mm;
    height: 34mm;
    border-top: 1mm solid #16f1ff;
    border-left: 1mm solid rgba(22, 241, 255, 0.65);
    background: linear-gradient(135deg, rgba(22, 241, 255, 0.26), rgba(129, 53, 255, 0.38));
}

.footer-rule {
    position: absolute;
    bottom: 0;
    left: 63mm;
    width: 164mm;
    height: 24mm;
    border-top: 0;
    background: linear-gradient(105deg, transparent 0%, transparent 23%, rgba(255, 255, 255, 0.72) 23%, rgba(255, 255, 255, 0.72) 84%, transparent 84%);
}

.corner {
    position: absolute;
    width: 50mm;
    height: 32mm;
    opacity: 0.9;
}

.corner-tl {
    top: -10mm;
    left: -34mm;
    border-top: 7mm solid #b51dff;
    border-right: 7mm solid transparent;
    transform: rotate(-42deg);
}

.corner-tr {
    top: -16mm;
    right: -20mm;
    border-top: 9mm solid #7b35ff;
    border-left: 9mm solid transparent;
    transform: rotate(48deg);
}

.corner-bl {
    bottom: -9mm;
    left: -20mm;
    border-bottom: 9mm solid #13d6e8;
    border-right: 9mm solid transparent;
    transform: rotate(43deg);
}

.corner-br {
    bottom: -13mm;
    right: -18mm;
    border-bottom: 7mm solid #16f1ff;
    border-left: 7mm solid transparent;
    transform: rotate(-44deg);
}

.brand {
    position: absolute;
    top: 24mm;
    left: 17mm;
    width: 36mm;
    text-align: left;
    font-size: 18px;
    line-height: 1;
    font-weight: bold;
    color: #ffffff;
}

.brand span {
    color: #22f3ff;
}

.brand img {
    width: 34mm !important;
    background: #f4f7fb;
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
    top: 21mm;
    left: 78mm;
    width: 136mm;
    text-align: left;
    font-family: DejaVu Sans, sans-serif;
    font-size: 34px;
    line-height: 1.02;
    letter-spacing: 1.5px;
    font-weight: bold;
    color: #ffffff;
    text-shadow: 0 0 3mm rgba(34, 243, 255, 0.55);
}

.subtitle {
    position: absolute;
    top: 36mm;
    left: 78mm;
    width: 140mm;
    text-align: left;
    font-family: DejaVu Sans, sans-serif;
    font-size: 30px;
    line-height: 1;
    letter-spacing: 1px;
    font-weight: bold;
    color: #22f3ff;
}

.subtitle-left,
.subtitle-right {
    position: absolute;
    display: none;
}

.subtitle-left {
    left: 70mm;
}

.subtitle-right {
    right: 70mm;
}

.presented {
    position: absolute;
    top: 60mm;
    left: 78mm;
    width: 134mm;
    text-align: center;
    font-size: 13px;
    letter-spacing: 1px;
    color: #dce9ff;
}

.student-name {
    position: absolute;
    top: 69mm;
    left: 78mm;
    width: 138mm;
    min-height: 22mm;
    text-align: center;
    font-family: DejaVu Sans, sans-serif;
    font-style: normal;
    font-size: 31px;
    line-height: 22mm;
    color: #22f3ff;
    background: #2b2b3c;
    border: 0.32mm solid #7d8190;
    border-radius: 3mm;
    padding: 0 5mm;
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
    top: 98mm;
    left: 78mm;
    width: 138mm;
    text-align: center;
    font-size: 11px;
    line-height: 1.45;
    color: #edf6ff;
}

.metrics {
    position: absolute;
    top: 115mm;
    left: 78mm;
    width: 138mm;
    border-collapse: collapse;
}

.metrics td {
    width: 33.33%;
    text-align: center;
    color: #05111e;
    vertical-align: top;
}

.metrics .middle {
    border-left: 2mm solid transparent;
    border-right: 2mm solid transparent;
}

.metric-label {
    display: inline-block;
    min-width: 36mm;
    padding: 2.8mm 2mm 0.8mm;
    border-radius: 5mm 5mm 0 0;
    background: #18f2f2;
    font-size: 9px;
    line-height: 1;
    font-weight: bold;
    color: #05111e;
}

.metric-value {
    display: inline-block;
    min-width: 36mm;
    padding: 0.8mm 2mm 2.8mm;
    border-radius: 0 0 5mm 5mm;
    background: #18f2f2;
    font-size: 12px;
    line-height: 1;
    font-weight: bold;
    color: #05111e;
    box-shadow: 0 0 6mm rgba(34, 243, 255, 0.25);
}

.metrics td.middle .metric-label,
.metrics td.middle .metric-value {
    background: #eac43a;
    color: #121212;
}

.metrics td:last-child .metric-label,
.metrics td:last-child .metric-value {
    background: #a743f2;
    color: #ffffff;
}

.footer {
    position: absolute;
    top: 144mm;
    left: 78mm;
    width: 120mm;
    height: 26mm;
}

.footer-table {
    width: 120mm;
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
    text-align: center;
    background: #2b2b3c;
    border: 0.25mm solid #7d8190;
    border-radius: 4mm;
}

.signature-box {
    width: 38mm;
    height: 10mm;
    border: 0;
    border-radius: 0;
    background: transparent;
    text-align: center;
    font-size: 7px;
    line-height: 12mm;
    color: #dce9ff;
    box-shadow: 0 0 5mm rgba(34, 243, 255, 0.2);
}

.signature-line {
    width: 34mm;
    margin: 1mm auto 0;
    border-top: 0.32mm solid #ffffff;
}

.director {
    padding-top: 1.4mm;
    font-size: 11px;
    line-height: 1.2;
    font-weight: bold;
    color: #ffffff;
}

.company {
    padding-top: 0.6mm;
    font-size: 9px;
    line-height: 1.2;
    color: #22f3ff;
}

.stamp-cell {
    text-align: center;
}

.stamp {
    width: 20mm;
    height: 20mm;
    margin: 0 auto;
    border: 0.28mm solid #d7a51e;
    border-radius: 11mm;
    text-align: center;
    line-height: 22mm;
    color: #ffffff;
    background: #101626;
    box-shadow: 0 0 6mm rgba(255, 218, 72, 0.28);
}

.date-cell {
    display: none;
}

.side-meta {
    position: absolute;
    top: 70mm;
    left: 18mm;
    width: 34mm;
    color: #ffffff;
    text-align: left;
}

.side-meta-block {
    margin-bottom: 9mm;
}

.side-meta-label {
    font-size: 11px;
    line-height: 1.3;
    color: #f4f7fb;
}

.side-meta-value {
    padding-top: 1mm;
    font-size: 10px;
    line-height: 1.25;
    font-weight: bold;
    color: #ffffff;
}

.date-title {
    font-size: 10px;
    line-height: 1.4;
    font-weight: bold;
    color: #ffffff;
}

.date-value,
.certificate-code {
    font-size: 9px;
    line-height: 1.35;
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
        <div class="left-strip"></div>
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

        <div class="side-meta">
            <div class="side-meta-block">
                <div class="side-meta-label">Certificate ID:</div>
                <div class="side-meta-value">{{ $certificateCode }}</div>
            </div>
            <div class="side-meta-block">
                <div class="side-meta-label">Date of Issue:</div>
                <div class="side-meta-value">{{ strtoupper($issueDate) }}</div>
            </div>
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
