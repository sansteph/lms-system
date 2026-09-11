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
    background: #08243d;
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
    background: #08243d;
    color: #f8fbff;
}

.certificate {
    position: absolute;
    top: 8mm;
    left: 8mm;
    width: 281mm;
    height: 194mm;
    overflow: hidden;
    border: 0;
    background: #0a2c49;
    border: 5mm solid #d7a234;
    box-shadow: inset 0 0 0 1mm #f6d45c, 3mm 4mm 8mm rgba(0, 0, 0, 0.38);
}

.outer-frame {
    position: absolute;
    top: 2mm;
    left: 2mm;
    right: 2mm;
    bottom: 2mm;
    width: auto;
    height: auto;
    border: 0.35mm solid rgba(255, 224, 111, 0.72);
    background: #0b3152;
}

.outer-frame::before {
    display: none;
}

.left-strip {
    display: none;
}

.inner-frame {
    position: absolute;
    top: 7mm;
    left: 7mm;
    right: 7mm;
    bottom: 7mm;
    border: 0;
    background: repeating-linear-gradient(
        -45deg,
        #0b3152 0,
        #0b3152 5mm,
        #082944 5mm,
        #082944 10mm
    );
}

.inner-frame::after {
    content: "";
    position: absolute;
    left: 0;
    right: 0;
    bottom: 0;
    width: auto;
    height: 36mm;
    background: #20241f;
    opacity: 0.92;
}

.top-rule {
    position: absolute;
    top: -7mm;
    left: 84mm;
    width: 113mm;
    height: 49mm;
    border: 0.9mm solid #d7a234;
    border-top: 0;
    border-radius: 0 0 4mm 4mm;
    background: #092946;
    box-shadow: 0 2mm 4mm rgba(0, 0, 0, 0.32);
}

.footer-rule {
    position: absolute;
    bottom: 37mm;
    left: 7mm;
    right: 7mm;
    width: auto;
    height: 0;
    border-top: 0.22mm solid rgba(247, 214, 91, 0.42);
    background: transparent;
}

.corner {
    position: absolute;
    width: 50mm;
    height: 32mm;
    display: none;
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
    top: 10mm;
    left: 84mm;
    width: 113mm;
    height: 35mm;
    text-align: center;
    font-size: 36px;
    line-height: 1;
    font-weight: bold;
    color: #f6d45c;
    text-shadow: 0 1mm 1mm rgba(0, 0, 0, 0.55);
}

.brand span {
    color: #22f3ff;
}

.brand img {
    display: none;
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
    top: 43mm;
    left: 43mm;
    width: 195mm;
    text-align: center;
    font-family: DejaVu Sans, sans-serif;
    font-size: 24px;
    line-height: 1;
    letter-spacing: 1px;
    font-weight: bold;
    color: #ffffff;
    text-shadow: 0 0.7mm 0.8mm rgba(0, 0, 0, 0.65);
}

.subtitle {
    position: absolute;
    top: 53mm;
    left: 48mm;
    width: 185mm;
    text-align: center;
    font-family: DejaVu Sans, sans-serif;
    font-size: 13px;
    line-height: 1.2;
    letter-spacing: 0;
    font-weight: bold;
    color: #ffffff;
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
    top: 61mm;
    left: 50mm;
    width: 181mm;
    text-align: center;
    font-size: 10px;
    letter-spacing: 0;
    color: #f8fbff;
}

.student-name {
    position: absolute;
    top: 70mm;
    left: 72mm;
    width: 137mm;
    min-height: 18mm;
    text-align: center;
    font-family: DejaVu Sans, sans-serif;
    font-style: normal;
    font-size: 23px;
    line-height: 18mm;
    color: #f6d45c;
    background: #6a350b;
    border: 1.1mm solid #d7a234;
    border-radius: 0;
    padding: 0 5mm;
    box-shadow: inset 0 0 7mm rgba(0, 0, 0, 0.42), 0 1mm 2mm rgba(0, 0, 0, 0.35);
    word-wrap: break-word;
    z-index: 3;
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
    top: 96mm;
    left: 70mm;
    width: 140mm;
    text-align: center;
    font-size: 8px;
    line-height: 1.35;
    color: #f5f7fb;
    z-index: 2;
}

.metrics {
    position: absolute;
    top: 114mm;
    left: 65mm;
    width: 151mm;
    border-collapse: collapse;
}

.metrics td {
    width: 33.33%;
    text-align: center;
    color: #ffffff;
    vertical-align: top;
}

.metrics .middle {
    border-left: 3mm solid transparent;
    border-right: 3mm solid transparent;
}

.metric-label {
    display: inline-block;
    min-width: 40mm;
    padding: 4mm 2mm 1mm;
    border-radius: 4mm 4mm 0 0;
    background: #0b3152;
    border-top: 0.9mm solid #d7a234;
    border-left: 0.9mm solid #d7a234;
    border-right: 0.9mm solid #d7a234;
    font-size: 9px;
    line-height: 1;
    font-weight: bold;
    color: #ffffff;
}

.metric-value {
    display: inline-block;
    min-width: 40mm;
    padding: 1mm 2mm 4mm;
    border-radius: 0 0 4mm 4mm;
    background: #0b3152;
    border-bottom: 0.9mm solid #d7a234;
    border-left: 0.9mm solid #d7a234;
    border-right: 0.9mm solid #d7a234;
    font-size: 21px;
    line-height: 1;
    font-weight: bold;
    color: #f6d45c;
    box-shadow: 0 0 6mm rgba(34, 243, 255, 0.25);
}

.metrics td.middle .metric-label,
.metrics td.middle .metric-value {
    background: #0b3152;
    color: #f6d45c;
}

.metrics td:last-child .metric-label,
.metrics td:last-child .metric-value {
    background: #0b3152;
    color: #f6d45c;
}

.metrics td:last-child .metric-value {
    font-size: 14px;
}

.footer {
    position: absolute;
    top: 147mm;
    left: 49mm;
    width: 183mm;
    height: 31mm;
}

.footer-table {
    width: 183mm;
    height: 31mm;
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
    background: transparent;
    border: 0;
    border-radius: 0;
}

.signature-box {
    width: 49mm;
    height: 13mm;
    border: 0;
    border-radius: 0;
    background: transparent;
    text-align: center;
    font-size: 12px;
    line-height: 13mm;
    color: #f6d45c;
    box-shadow: none;
}

.signature-line {
    width: 56mm;
    margin: 1mm auto 0;
    border-top: 0.32mm solid #d7a234;
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
    color: #f6d45c;
}

.stamp-cell {
    position: absolute;
    top: -73mm;
    left: -32mm;
    width: 35mm !important;
    height: 35mm;
    text-align: center;
}

.stamp {
    width: 31mm;
    height: 31mm;
    margin: 0 auto;
    border: 0.8mm solid #d7a234;
    border-radius: 15.5mm;
    text-align: center;
    line-height: 31mm;
    color: #ffffff;
    background: #f7f3e8;
    box-shadow: 0 0 6mm rgba(255, 218, 72, 0.28);
}

.date-cell {
    display: table-cell;
    text-align: center;
}

.side-meta {
    position: absolute;
    top: 70mm;
    right: 25mm;
    left: auto;
    width: 30mm;
    color: #ffffff;
    text-align: center;
    border: 0.9mm solid #d7a234;
    border-radius: 3mm;
    padding: 3mm;
    background: #092946;
}

.qr-image {
    width: 24mm;
    height: 24mm;
    display: block;
    margin: 0 auto 2mm;
    background: #ffffff;
    padding: 1.5mm;
}

.side-meta-block {
    margin-bottom: 2.5mm;
}

.side-meta-label {
    font-size: 7px;
    line-height: 1.3;
    color: #f4f7fb;
}

.side-meta-value {
    padding-top: 1mm;
    font-size: 7px;
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
    font-size: 8px;
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
    $verificationUrl = route('certificate.verify');
    $qrSvg = null;
    try {
        $qrSvg = base64_encode(\SimpleSoftwareIO\QrCode\Facades\QrCode::format('svg')
            ->size(160)
            ->margin(1)
            ->generate($verificationUrl));
    } catch (\Throwable $error) {
        $qrSvg = null;
    }
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
            TinkEdge
        </div>

        <div class="side-meta">
            @if($qrSvg)
                <img class="qr-image" src="data:image/svg+xml;base64,{{ $qrSvg }}" alt="Certificate verification QR">
            @endif
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
