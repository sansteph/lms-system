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
    font-family: DejaVu Sans, sans-serif;
    background: #ffffff;
    page-break-after: avoid;
}

* {
    box-sizing: border-box;
}

.certificate {
    width: 260mm;
    height: 180mm;
    margin: 10mm auto;
    position: relative;
    overflow: hidden;
    color: #061d55;
    text-align: center;
    background: #ffffff;
    border: 0.22mm solid #e2c886;
    page-break-inside: avoid;
    page-break-after: avoid;
}

.certificate::before {
    content: "";
    position: absolute;
    top: 1.8mm;
    left: 1.8mm;
    right: 1.8mm;
    bottom: 1.8mm;
    border: 0.12mm solid #f0e3c3;
    z-index: 1;
}

.outer-blue {
    position: absolute;
    top: 3.5mm;
    left: 3.5mm;
    right: 3.5mm;
    bottom: 3.5mm;
    border: 0.48mm solid #082e68;
    z-index: 2;
}

.inner-gold {
    position: absolute;
    top: 6.3mm;
    left: 6.3mm;
    right: 6.3mm;
    bottom: 6.3mm;
    border: 0.22mm solid #d4ad62;
    z-index: 2;
}

.inner-fine {
    position: absolute;
    top: 9mm;
    left: 9mm;
    right: 9mm;
    bottom: 9mm;
    border: 0.12mm solid #efe4c8;
    z-index: 2;
}

.corner {
    position: absolute;
    width: 19mm;
    height: 16mm;
    z-index: 3;
}

.corner .blue-line,
.corner .gold-line {
    position: absolute;
    border-color: #0b356f;
    border-style: solid;
}

.corner .gold-line {
    border-color: #d4ad62;
}

.corner-tl {
    top: 3.8mm;
    left: 3.8mm;
}

.corner-tr {
    top: 3.8mm;
    right: 3.8mm;
}

.corner-bl {
    bottom: 3.8mm;
    left: 3.8mm;
}

.corner-br {
    bottom: 3.8mm;
    right: 3.8mm;
}

.corner-tl .blue-line,
.corner-bl .blue-line {
    left: 0;
    border-left-width: 0.42mm;
}

.corner-tr .blue-line,
.corner-br .blue-line {
    right: 0;
    border-right-width: 0.42mm;
}

.corner-tl .blue-line,
.corner-tr .blue-line {
    top: 0;
    width: 17mm;
    height: 11mm;
    border-top-width: 0.42mm;
}

.corner-bl .blue-line,
.corner-br .blue-line {
    bottom: 0;
    width: 17mm;
    height: 11mm;
    border-bottom-width: 0.42mm;
}

.corner-tl .gold-line,
.corner-bl .gold-line {
    left: 4mm;
    border-left-width: 0.18mm;
}

.corner-tr .gold-line,
.corner-br .gold-line {
    right: 4mm;
    border-right-width: 0.18mm;
}

.corner-tl .gold-line,
.corner-tr .gold-line {
    top: 4mm;
    width: 12mm;
    height: 7mm;
    border-top-width: 0.18mm;
}

.corner-bl .gold-line,
.corner-br .gold-line {
    bottom: 4mm;
    width: 12mm;
    height: 7mm;
    border-bottom-width: 0.18mm;
}

.content {
    position: absolute;
    top: 0;
    left: 28mm;
    right: 28mm;
    z-index: 5;
    height: 126mm;
    padding-top: 19mm;
    text-align: center;
}

.logo-row {
    height: 18mm;
    text-align: center;
}

.logo-img {
    height: 14mm;
    max-width: 62mm;
}

.logo-text {
    font-size: 18px;
    line-height: 1;
    font-weight: bold;
}

.logo-text span {
    color: #0097d8;
}

.tagline {
    font-size: 7px;
    letter-spacing: 1.8px;
    font-weight: bold;
    margin-top: 1mm;
}

.title {
    font-family: DejaVu Serif, serif;
    font-size: 34px;
    letter-spacing: 8px;
    line-height: 1;
    margin: 0;
    color: #061d55;
}

.achievement-row {
    margin-top: 3mm;
    margin-bottom: 5.5mm;
    color: #b78320;
    font-family: DejaVu Serif, serif;
    font-size: 13px;
    letter-spacing: 3px;
}

.achievement-row:before,
.achievement-row:after {
    content: "";
    display: inline-block;
    width: 20mm;
    border-top: 0.35mm solid #c8942f;
    vertical-align: middle;
    margin: 0 4mm;
}

.presented {
    font-size: 10px;
    color: #111827;
    margin-bottom: 3mm;
}

.student-name {
    font-family: DejaVu Serif, serif;
    font-size: 27px;
    font-style: italic;
    font-weight: normal;
    color: #061d55;
    margin: 0 auto 2mm;
    max-width: 160mm;
    line-height: 1.1;
}

.student-name.small {
    font-size: 23px;
}

.name-line {
    width: 112mm;
    border-top: 0.4mm solid #c8942f;
    margin: 0 auto 4mm;
}

.description {
    width: 158mm;
    height: 13mm;
    margin: 0 auto 5.5mm;
    font-size: 9px;
    line-height: 1.45;
    color: #111827;
}

.metrics {
    width: 138mm;
    margin: 0 auto;
    border-collapse: collapse;
    table-layout: fixed;
}

.metrics td {
    width: 33.33%;
    padding: 0 7mm;
    text-align: center;
    border-right: 0.38mm solid #d4a041;
}

.metrics td:last-child {
    border-right: none;
}

.metric-label {
    font-size: 7.5px;
    font-weight: bold;
    color: #061d55;
}

.metric-value {
    font-size: 11px;
    font-weight: bold;
    color: #061d55;
    margin-top: 1mm;
    text-transform: uppercase;
}

.footer {
    position: absolute;
    left: 44mm;
    right: 44mm;
    bottom: 15mm;
    height: 34mm;
    z-index: 6;
}

.footer-table {
    width: 100%;
    border-collapse: collapse;
    table-layout: fixed;
}

.footer-table td {
    width: 33.33%;
    vertical-align: bottom;
}

.signature {
    text-align: left;
    padding-left: 0;
}

.signature-placeholder {
    width: 40mm;
    height: 11mm;
    border: 0.28mm dashed #c2ccda;
    color: #7b8798;
    font-size: 6.5px;
    line-height: 11mm;
    text-align: center;
    margin-bottom: 1.5mm;
}

.signature-line {
    width: 40mm;
    border-top: 0.45mm solid #061d55;
    margin-bottom: 1mm;
}

.sign-name {
    font-size: 8px;
    font-weight: bold;
}

.sign-role {
    font-size: 7px;
    color: #111827;
    margin-top: 0.5mm;
}

.seal-wrap {
    text-align: center;
}

.stamp-placeholder {
    display: inline-block;
    width: 22mm;
    height: 22mm;
    border-radius: 50%;
    border: 0.35mm dashed #c2ccda;
    color: #7b8798;
    text-align: center;
    line-height: 22mm;
    font-size: 7px;
    font-weight: bold;
    background: #fbfcff;
}

.issue {
    text-align: right;
    font-size: 7.4px;
    line-height: 1.55;
    color: #111827;
    padding-right: 0;
}

.issue strong {
    color: #061d55;
    font-size: 8px;
}
</style>
</head>

<body>
@php
    $studentName = $student->name ?? 'Student Name';

    $finalPercentage = $certificate->final_percentage
        ?? $certificate->final_score
        ?? $certificate->badge_count
        ?? 0;

    if ($certificate->final_grade && $certificate->final_classification) {
        $grade = $certificate->final_grade;
        $classification = $certificate->final_classification;
    } elseif ($finalPercentage >= 90) {
        $grade = 'A+';
        $classification = 'Outstanding';
    } elseif ($finalPercentage >= 80) {
        $grade = 'A';
        $classification = 'Distinction';
    } elseif ($finalPercentage >= 70) {
        $grade = 'B+';
        $classification = 'First Class';
    } elseif ($finalPercentage >= 60) {
        $grade = 'B';
        $classification = 'Second Class';
    } elseif ($finalPercentage >= 50) {
        $grade = 'C+';
        $classification = 'Pass';
    } elseif ($finalPercentage >= 40) {
        $grade = 'C';
        $classification = 'Satisfactory';
    } else {
        $grade = 'F';
        $classification = 'Fail';
    }

    $issueDate = $certificate->issued_date
        ? \Carbon\Carbon::parse($certificate->issued_date)->format('d F Y')
        : now()->format('d F Y');

    $certificateCode = $certificate->certificate_code ?? 'TE-2026-000000';
    $logoPath = public_path('images/TinkEdgeLogo.png');
@endphp

<div class="certificate">
    <div class="outer-blue"></div>
    <div class="inner-gold"></div>
    <div class="inner-fine"></div>

    <div class="corner corner-tl"><div class="blue-line"></div><div class="gold-line"></div></div>
    <div class="corner corner-tr"><div class="blue-line"></div><div class="gold-line"></div></div>
    <div class="corner corner-bl"><div class="blue-line"></div><div class="gold-line"></div></div>
    <div class="corner corner-br"><div class="blue-line"></div><div class="gold-line"></div></div>

    <div class="content">
        <div class="logo-row">
            @if(file_exists($logoPath))
                <img src="{{ $logoPath }}" class="logo-img" alt="TinkEdge">
            @else
                <div class="logo-text">Tink<span>Edge</span></div>
                <div class="tagline">STEM EDUCATION</div>
            @endif
        </div>

        <h1 class="title">CERTIFICATE</h1>
        <div class="achievement-row">OF ACHIEVEMENT</div>

        <div class="presented">This is proudly presented to</div>

        <h2 class="student-name {{ strlen($studentName) > 28 ? 'small' : '' }}">
            {{ $studentName }}
        </h2>

        <div class="name-line"></div>

        <div class="description">
            &nbsp;
        </div>

        <table class="metrics">
            <tr>
                <td>
                    <div class="metric-label">Final Percentage</div>
                    <div class="metric-value">{{ number_format($finalPercentage, 2) }}%</div>
                </td>
                <td>
                    <div class="metric-label">Grade</div>
                    <div class="metric-value">{{ $grade }}</div>
                </td>
                <td>
                    <div class="metric-label">Classification</div>
                    <div class="metric-value">{{ strtoupper($classification) }}</div>
                </td>
            </tr>
        </table>
    </div>

    <div class="footer">
        <table class="footer-table">
            <tr>
                <td class="signature">
                    <img src="{{ public_path('images/signature.png') }}" alt="Signature" class="signature-placeholder">
                    <div class="signature-line"></div>
                    <div class="sign-name"><h2>Director</h2></div>
                    <div class="sign-role"><h3>TinkEdge</h3></div>
                </td>

                <td class="seal-wrap">
                    <img src="{{ public_path('images/seal.png') }}" alt="Seal" class="stamp-placeholder">
                </td>

                <td class="issue">
                    <strong><h2>Date of Issue</h2></strong><br>
                    <h3>{{ $issueDate }}</h3><br>
                    <h4>Certificate ID: {{ $certificateCode }}</h4>
                </td>
            </tr>
        </table>
    </div>
</div>
</body>
</html>
