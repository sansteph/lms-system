<!DOCTYPE html>
<html>
<head>
<meta charset="utf-8">
<style>
@page { margin: 0; size: A4 landscape; }
html, body { margin: 0; padding: 0; font-family: DejaVu Sans, sans-serif; color: #fff; }
.page { position: relative; width: 297mm; height: 209mm; overflow: hidden; }
.backdrop { position: absolute; top: 0; left: 0; width: 297mm; height: 210mm; }
.brand { position: absolute; top: 12mm; left: 75mm; width: 147mm; text-align: center; font-size: 58pt; line-height: 1.1; font-weight: bold; color: #f3ce60; }
.title { position: absolute; top: 47mm; left: 65mm; width: 167mm; text-align: center; font-size: 21pt; font-weight: bold; line-height: 1.15; }
.subtitle { position: absolute; top: 56mm; left: 65mm; width: 167mm; text-align: center; font-size: 12pt; line-height: 1.2; }
.presented { position: absolute; top: 66mm; left: 75mm; width: 147mm; text-align: center; font-size: 10pt; line-height: 1.2; }
.student-name { position: absolute; top: 78mm; left: 78mm; width: 141mm; height: 16mm; text-align: center; font-weight: bold; line-height: 1.15; color: #f3ce60; }
.description { position: absolute; top: 103mm; left: 75mm; width: 147mm; text-align: center; font-size: 10pt; line-height: 1.45; }
.metric { position: absolute; top: 129mm; width: 45mm; height: 29mm; border: 1mm solid #d9ac49; border-radius: 5mm; background: #092a43; text-align: center; }
.metric-one { left: 74mm; }
.metric-two { left: 125mm; }
.metric-three { left: 176mm; }
.metric-label { margin-top: 3mm; font-size: 10.5pt; line-height: 1.3; color: #fff; }
.metric-value { margin-top: 1mm; font-size: 26pt; line-height: 1.3; font-weight: bold; color: #f3ce60; }
.classification { margin-top: 5mm; font-size: 12pt; }
.seal { position: absolute; left: 23mm; top: 91mm; width: 34mm; height: 34mm; border: 1.4mm solid #dbb44c; border-radius: 19mm; background: #fff; padding: 1.5mm; }
.qr-panel { position: absolute; left: 239mm; top: 87mm; width: 32mm; height: 32mm; padding: 2mm; border: 1mm solid #d9ac49; border-radius: 3mm; background: #092a43; }
.qr-image { display: block; width: 32mm; height: 32mm; }
.side-meta { position: absolute; left: 234mm; top: 128mm; width: 45mm; text-align: center; font-size: 8pt; line-height: 1.5; }
.side-meta strong { font-size: 8pt; color: #f3ce60; }
.signature { position: absolute; left: 56mm; top: 168mm; width: 68mm; text-align: center; }
.signature-image { width: 55mm; height: 12mm; }
.signature-space { height: 12mm; }
.signature-line { border-top: .3mm solid #d9ac49; margin-top: 1mm; }
.director { font-size: 12pt; margin-top: 1mm; line-height: 1.2; }
.company { font-size: 10pt; color: #f3ce60; line-height: 1.3; }
.date { position: absolute; left: 180mm; top: 172mm; width: 68mm; text-align: center; }
.date-value { font-family: DejaVu Serif, serif; font-style: italic; font-size: 16pt; color: #f3ce60; height: 10mm; }
.date-title { font-size: 12pt; line-height: 1.4; margin-top: 1mm; }
.certificate-code { font-size: 8pt; color: #f3ce60; line-height: 1.4; }
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
@php
    // Fit longer recipient names within the fixed certificate name plate.
    $nameLength = mb_strlen($studentName);
    $nameSize = $nameLength > 42 ? 15 : ($nameLength > 30 ? 20 : ($nameLength > 22 ? 25 : 30));
@endphp
<div class="page">
    <img class="backdrop" src="{{ public_path('images/certificate-blue-gold.svg') }}" alt="">
    <div class="brand">TinkEdge</div>
    <div class="title">CERTIFICATE</div>
    <div class="subtitle">OF ACHIEVEMENT</div>
    <div class="presented">This certificate is proudly presented to</div>
    <div class="student-name" style="font-size: {{ $nameSize }}pt">{{ $studentName }}</div>
    <div class="description">
        for successfully completing the {{ $programName }} and demonstrating consistent performance,
        dedication, and applied STEM learning.
    </div>

    <img class="seal" src="{{ public_path('images/company-stamp.jpeg') }}" alt="Company stamp">
    @if($qrSvg)
        <div class="qr-panel">
            <img class="qr-image" src="data:image/svg+xml;base64,{{ $qrSvg }}" alt="Certificate verification QR">
        </div>
    @endif
    <div class="side-meta">
        Certificate ID:<br><strong>{{ $certificateCode }}</strong><br>
        Date of Issue:<br><strong>{{ strtoupper($issueDate) }}</strong>
    </div>

    <div class="metric metric-one">
        <div class="metric-label">Final Percentage</div>
        <div class="metric-value">{{ number_format($percentage, 2) }}%</div>
    </div>
    <div class="metric metric-two">
        <div class="metric-label">Grade</div>
        <div class="metric-value">{{ $grade }}</div>
    </div>
    <div class="metric metric-three">
        <div class="metric-label">Classification</div>
        <div class="metric-value classification">{{ strtoupper($classification) }}</div>
    </div>

    <div class="signature">
        @if(is_file(public_path('images/signature.png')))
            <img class="signature-image" src="{{ public_path('images/signature.png') }}" alt="Director's Signature">
        @else
            <div class="signature-space"></div>
        @endif
        <div class="signature-line"></div>
        <div class="director">Director</div>
        <div class="company">TinkEdge</div>
    </div>
    <div class="date">
        <div class="date-value">{{ $issueDate }}</div>
        <div class="signature-line"></div>
        <div class="date-title">Date of Issue</div>
        <div class="certificate-code">Certificate ID: {{ $certificateCode }}</div>
    </div>
</div>
</body>
</html>
