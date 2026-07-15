<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>InnovatEdge Feedback</title>
</head>
<body style="font-family: Arial, sans-serif; color: #111827; line-height: 1.5;">
    <h2 style="margin-bottom: 8px;">New Feedback Received</h2>
    <p style="margin-top: 0;">A {{ $senderType }} submitted feedback through InnovatEdge.</p>

    <h3 style="margin-bottom: 8px;">Sender Details</h3>
    <table cellpadding="8" cellspacing="0" border="1" style="border-collapse: collapse; width: 100%; max-width: 720px;">
        @foreach($senderDetails as $label => $value)
            <tr>
                <th align="left" style="width: 180px; background: #f3f4f6;">{{ $label }}</th>
                <td>{{ $value }}</td>
            </tr>
        @endforeach
        <tr>
            <th align="left" style="background: #f3f4f6;">Submitted At</th>
            <td>{{ $submittedAt }}</td>
        </tr>
    </table>

    <h3 style="margin-bottom: 8px;">Feedback</h3>
    <p><strong>Category:</strong> {{ $category }}</p>
    <p><strong>Subject:</strong> {{ $feedbackSubject }}</p>
    <div style="padding: 14px; border: 1px solid #d1d5db; background: #f9fafb; white-space: pre-line;">
        {{ $feedbackMessage }}
    </div>
</body>
</html>
