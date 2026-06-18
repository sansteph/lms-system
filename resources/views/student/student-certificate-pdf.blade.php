<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">

    <style>
        @page {
            margin: 18px;
        }

        body {
            font-family: DejaVu Sans, sans-serif;
            margin: 0;
            padding: 0;
            background: #ffffff;
        }

        .certificate {
            position: relative;
            height: 515px;
            border: 7px solid #1e3a8a;
            padding: 42px 60px;
            box-sizing: border-box;
            text-align: center;
        }

        .corner {
            position: absolute;
            width: 42px;
            height: 42px;
        }

        .corner-top-left {
            top: 22px;
            left: 22px;
            border-top: 3px solid #d4af37;
            border-left: 3px solid #d4af37;
        }

        .corner-top-right {
            top: 22px;
            right: 22px;
            border-top: 3px solid #d4af37;
            border-right: 3px solid #d4af37;
        }

        .corner-bottom-left {
            bottom: 22px;
            left: 22px;
            border-bottom: 3px solid #d4af37;
            border-left: 3px solid #d4af37;
        }

        .corner-bottom-right {
            bottom: 22px;
            right: 22px;
            border-bottom: 3px solid #d4af37;
            border-right: 3px solid #d4af37;
        }

        .content {
            padding-top: 8px;
        }

        .brand {
            font-size: 13px;
            letter-spacing: 4px;
            color: #1e3a8a;
            font-weight: bold;
            margin-bottom: 20px;
        }

        .title {
            font-size: 39px;
            color: #111827;
            margin: 0 0 18px;
            font-weight: bold;
        }

        .subtitle {
            font-size: 15px;
            color: #374151;
            margin-bottom: 18px;
        }

        .student-name {
            font-size: 34px;
            color: #1e3a8a;
            margin: 0 0 22px;
            font-weight: bold;
        }

        .gold-line {
            width: 170px;
            height: 3px;
            background: #d4af37;
            margin: 0 auto 26px;
        }

        .description {
            font-size: 16px;
            color: #374151;
            line-height: 1.7;
            width: 82%;
            margin: 0 auto 30px;
        }

        .details {
            width: 100%;
            margin-top: 18px;
            font-size: 13px;
            border-collapse: collapse;
        }

        .details td {
            width: 33.33%;
            text-align: center;
            vertical-align: top;
        }

        .label {
            color: #6b7280;
            font-size: 11px;
            text-transform: uppercase;
            letter-spacing: 1px;
            margin-bottom: 5px;
        }

        .value {
            color: #111827;
            font-weight: bold;
            font-size: 13px;
        }

        .footer-note {
            margin-top: 28px;
            font-size: 11px;
            color: #6b7280;
        }
    </style>
</head>

<body>

<div class="certificate">

    <div class="corner corner-top-left"></div>
    <div class="corner corner-top-right"></div>
    <div class="corner corner-bottom-left"></div>
    <div class="corner corner-bottom-right"></div>

    <div class="content">

        <div class="brand">
            TINKEDGE LMS ACHIEVEMENT CERTIFICATE
        </div>

        <h1 class="title">
            Certificate of Achievement
        </h1>

        <div class="subtitle">
            This certificate is proudly presented to
        </div>

        <h2 class="student-name">
            {{ $student->name }}
        </h2>

        <div class="gold-line"></div>

        <p class="description">
            For successfully earning achievement badges and demonstrating
            outstanding learning performance in the LMS platform.
        </p>

        <table class="details">
            <tr>
                <td>
                    <div class="label">Certificate Code</div>
                    <div class="value">{{ $certificate->certificate_code }}</div>
                </td>

                <td>
                    <div class="label">Issued Date</div>
                    <div class="value">
                        {{ \Carbon\Carbon::parse($certificate->issued_date)->format('d M Y') }}
                    </div>
                </td>

                <td>
                    <div class="label">Verification</div>
                    <div class="value">Officially Verifiable</div>
                </td>
            </tr>
        </table>

        <div class="footer-note">
            Verify this certificate using the certificate code on the TinkEdge LMS verification page.
        </div>

    </div>

</div>

</body>
</html>