<!DOCTYPE html>
<html>
<head>
<meta charset="utf-8">
<style>
@page { margin: 0; size: A4 landscape; }
html, body { margin: 0; padding: 0; font-family: DejaVu Sans, sans-serif; color: #092a43; }
.page { position: relative; width: 297mm; height: 209mm; overflow: hidden; }
.backdrop { position: absolute; top: 0; left: 0; width: 297mm; height: 210mm; }
.brand { position: absolute; top: 14mm; left: 116mm; width: 65mm; height: 15mm; }
.social-icons { position: absolute; top: 18mm; left: 225mm; width: 49mm; height: 7.74mm; }
.cup-robot { position: absolute; top: 70mm; left: 19mm; width: 49mm; height: 49mm; }
.title { position: absolute; top: 39mm; left: 55mm; width: 187mm; text-align: center; font-size: 32pt; font-weight: bold; line-height: 1.15; }
.subtitle { position: absolute; top: 53mm; left: 55mm; width: 187mm; text-align: center; font-size: 20pt; line-height: 1.2; }
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
.seal { position: absolute; left: 19mm; top: 168mm; width: 23mm; height: 23mm; border: 1mm solid #c79830; border-radius: 16mm; background: #d8f3ed; padding: 1.2mm; }
.seal-image { width: 22mm; height: 22mm; border: .5mm solid #007d8a; border-radius: 13mm; }
.qr-panel { position: absolute; left: 239mm; top: 87mm; width: 32mm; height: 32mm; padding: 2mm; border: 1mm solid #d9ac49; border-radius: 3mm; background: #092a43; }
.qr-image { display: block; width: 32mm; height: 32mm; }
.side-meta { position: absolute; left: 234mm; top: 128mm; width: 45mm; text-align: center; font-size: 8pt; line-height: 1.5; }
.side-meta strong { font-size: 8pt; color: #805d13; }
.signature { position: absolute; left: 54mm; top: 169mm; width: 53mm; text-align: center; }
.signature-image { width: 50mm; height: 12mm; }
.signature-space { height: 12mm; }
.signature-line { border-top: .3mm solid #d9ac49; margin-top: 1mm; }
.director { font-size: 12pt; margin-top: 1mm; line-height: 1.2; }
.company { font-size: 10pt; color: #805d13; line-height: 1.3; }
.date { position: absolute; left: 160mm; top: 173mm; width: 66mm; text-align: center; }
.date-value { font-family: DejaVu Serif, serif; font-style: italic; font-size: 14pt; color: #805d13; height: 8mm; }
.date-title { font-size: 12pt; line-height: 1.4; margin-top: 1mm; }
.certificate-code { font-size: 7pt; color: #805d13; line-height: 1.4; }
.contact { position: absolute; right: 19mm; top: 169mm; width: 55mm; text-align: right; font-size: 7.5pt; line-height: 1.2; }
.contact strong { font-size: 10pt; }
.contact a { color: #006d99; text-decoration: none; }
.contact sup { font-size: 5.5pt; vertical-align: super; line-height: 0; }
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
    <img class="backdrop" src="{{ public_path('images/certificate-white-gold.svg') }}" alt="">
    <img class="brand" src="{{ public_path('images/InnovatEdgeLogo.png') }}" alt="TinkEdge">
    <img class="social-icons" src="{{ public_path('images/certificate-social.svg') }}" alt="LinkedIn, Instagram, Maps, Website, YouTube">
    <img class="cup-robot" src="{{ public_path('images/CupRobot(2).png') }}" alt="Waving cup robot">
    <div class="title">CERTIFICATE</div>
    <div class="subtitle">OF ACHIEVEMENT</div>
    <div class="presented">This certificate is proudly presented to</div>
    <div class="student-name" style="font-size: {{ $nameSize }}pt">{{ $studentName }}</div>
    <div class="description">
        for successfully completing the {{ $programName }} and demonstrating consistent performance,
        dedication, and applied STEM learning.
    </div>

    <div class="seal"><img class="seal-image" src="{{ public_path('images/company-stamp.jpeg') }}" alt="Company stamp"></div>
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
    <div class="contact">
        <strong>TinkEdge</strong><br>
        #98, No. 102, 1st Floor, 3<sup>rd</sup> Main,<br>
        Margosa Rd, Malleshwaram,<br>
        Bengaluru - 560003.<br>
        <a href="https://tinkedge.com/">https://tinkedge.com/</a><br>
        <a href="tel:+919606932923">91 9606932923</a>
    </div>
</div>
</body>
</html>
